<?php
defined( 'ABSPATH' ) || exit;

function verdix_register_extra_migration_sources( $sources ) {
	$sources['ultimate_blocks'] = array(
		'label'         => __( 'Ultimate Blocks (Review block)', 'verdix-reviews' ),
		'detect'        => 'verdix_get_ultimate_blocks_posts',
		'migrate'       => 'verdix_migrate_post_ultimate_blocks',
		'columns'       => array( 'type', 'rating', 'schema' ),
		'row_data'      => 'verdix_migration_row_ultimate_blocks',
		'replace'       => 'verdix_replace_ultimate_blocks_content',
		'replace_label' => __( 'Replace the Ultimate Blocks review block in the post content with the Verdix block', 'verdix-reviews' ),
		'plugin_active' => defined( 'ULTIMATE_BLOCKS_VERSION' ),
		'plugin_name'   => 'Ultimate Blocks',
	);

	$sources['rank_math'] = array(
		'label'         => __( 'Rank Math (review snippet)', 'verdix-reviews' ),
		'detect'        => 'verdix_get_rank_math_posts',
		'migrate'       => 'verdix_migrate_post_rank_math',
		'columns'       => array( 'type', 'rating', 'schema' ),
		'row_data'      => 'verdix_migration_row_rank_math',
		'replace'       => 'verdix_replace_rank_math_content',
		'replace_label' => __( 'Replace the [rank_math_rich_snippet] shortcode in the post content with the Verdix shortcode', 'verdix-reviews' ),
		'plugin_active' => defined( 'RANK_MATH_VERSION' ),
		'plugin_name'   => 'Rank Math',
	);

	return $sources;
}
add_filter( 'verdix_migration_sources', 'verdix_register_extra_migration_sources' );

function verdix_post_has_verdix_data( $post_id ) {
	$existing = json_decode( (string) get_post_meta( $post_id, '_verdix_review', true ), true );
	return is_array( $existing ) && ! empty( $existing['rating'] );
}

function verdix_migration_post_statuses() {
	return array( 'publish', 'draft', 'pending', 'private' );
}

function verdix_scale_to_stars( $value, $max ) {
	$max   = (float) $max > 0 ? (float) $max : 5;
	$value = max( 0, min( 5, (float) $value * 5 / $max ) );
	return round( $value * 2 ) / 2;
}

// =========================================================================
// Ultimate Blocks
// =========================================================================

function verdix_find_blocks( $blocks, $name ) {
	$found = array();
	foreach ( $blocks as $block ) {
		if ( $name === $block['blockName'] ) {
			$found[] = $block;
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			$found = array_merge( $found, verdix_find_blocks( $block['innerBlocks'], $name ) );
		}
	}
	return $found;
}

function verdix_get_ultimate_blocks_posts() {
	$query = new WP_Query(
		array(
			'post_type'              => 'any',
			'post_status'            => verdix_migration_post_statuses(),
			'posts_per_page'         => -1,
			's'                      => 'wp:ub/review',
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		)
	);

	return array_values(
		array_filter(
			$query->posts,
			function ( $post ) {
				return has_block( 'ub/review', $post->post_content );
			}
		)
	);
}

function verdix_parse_ultimate_blocks_review( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return null;
	}

	$blocks = verdix_find_blocks( parse_blocks( $post->post_content ), 'ub/review' );
	if ( empty( $blocks ) ) {
		return null;
	}

	$block = $blocks[0];
	$attrs = $block['attrs'];

	if ( isset( $attrs['parts'] ) && is_array( $attrs['parts'] ) ) {
		$parts = $attrs['parts'];
	} elseif ( isset( $attrs['items'] ) ) {
		$parts = json_decode( $attrs['items'], true );
	} else {
		$parts = array();
	}

	$values = array();
	foreach ( (array) $parts as $part ) {
		if ( isset( $part['value'] ) ) {
			$values[] = (float) $part['value'];
		}
	}

	$value_type = isset( $attrs['valueType'] ) ? $attrs['valueType'] : 'star';
	$max        = 'percent' === $value_type ? 100 : ( isset( $attrs['starCount'] ) ? (float) $attrs['starCount'] : 5 );
	$average    = $values ? array_sum( $values ) / count( $values ) : 0;

	$cta_text = isset( $attrs['callToActionText'] ) ? $attrs['callToActionText'] : '';
	$cta_url  = isset( $attrs['callToActionURL'] ) ? $attrs['callToActionURL'] : '';
	if ( ! empty( $block['innerBlocks'][0]['attrs']['buttons'][0] ) ) {
		$button   = $block['innerBlocks'][0]['attrs']['buttons'][0];
		$cta_text = isset( $button['buttonText'] ) ? $button['buttonText'] : $cta_text;
		$cta_url  = isset( $button['url'] ) ? $button['url'] : $cta_url;
	}

	$use_summary = ! isset( $attrs['useSummary'] ) || $attrs['useSummary'];
	$summary     = $use_summary && ! empty( $attrs['summaryDescription'] ) ? $attrs['summaryDescription'] : ( isset( $attrs['description'] ) ? $attrs['description'] : '' );
	$item_type   = isset( $attrs['itemSubsubtype'] ) && $attrs['itemSubsubtype'] ? $attrs['itemSubsubtype'] : ( isset( $attrs['itemType'] ) ? $attrs['itemType'] : 'Product' );

	return array(
		'raw_average' => $average,
		'max'         => $max,
		'rating'      => verdix_scale_to_stars( $average, $max ),
		'item_type'   => $item_type,
		'item_name'   => isset( $attrs['itemName'] ) ? $attrs['itemName'] : '',
		'author'      => isset( $attrs['authorName'] ) ? $attrs['authorName'] : '',
		'summary'     => $summary,
		'price'       => isset( $attrs['offerPrice'] ) && $attrs['offerPrice'] ? $attrs['offerPrice'] : '',
		'currency'    => isset( $attrs['offerCurrency'] ) ? $attrs['offerCurrency'] : 'USD',
		'cta_text'    => $cta_text,
		'cta_url'     => $cta_url,
	);
}

function verdix_migrate_post_ultimate_blocks( $post_id ) {
	$parsed = verdix_parse_ultimate_blocks_review( $post_id );

	if ( ! $parsed || $parsed['rating'] <= 0 ) {
		return new WP_Error( 'no_review', __( 'No Ultimate Blocks review data found.', 'verdix-reviews' ) );
	}

	if ( verdix_post_has_verdix_data( $post_id ) ) {
		return new WP_Error( 'already_migrated', __( 'Already has Verdix Reviews data.', 'verdix-reviews' ) );
	}

	verdix_save_review(
		$post_id,
		array(
			'rating'       => $parsed['rating'],
			'rating_type'  => 'star',
			'position'     => '',
			'heading'      => $parsed['item_name'],
			'summary'      => $parsed['summary'],
			'pros'         => array(),
			'cons'         => array(),
			'schema_type'  => verdix_map_schema_type( $parsed['item_type'] ),
			'product_name' => $parsed['item_name'],
			'price'        => $parsed['price'],
			'currency'     => $parsed['currency'],
			'cta_text'     => $parsed['cta_text'],
			'cta_url'      => $parsed['cta_url'],
			'author_name'  => $parsed['author'],
		)
	);

	return true;
}

function verdix_migration_row_ultimate_blocks( $post ) {
	$parsed = verdix_parse_ultimate_blocks_review( $post->ID );
	if ( ! $parsed ) {
		return array();
	}

	$raw = round( $parsed['raw_average'], 1 ) . '/' . $parsed['max'];

	return array(
		'type'   => esc_html( 100 === (int) $parsed['max'] ? 'percent' : 'star' ),
		'rating' => esc_html( $raw . ' &rarr; ' . $parsed['rating'] ),
		'schema' => esc_html( $parsed['item_type'] ),
	);
}

function verdix_replace_blocks( $blocks, $name, $replacement, &$done ) {
	$result = array();
	foreach ( $blocks as $block ) {
		if ( ! $done && $name === $block['blockName'] ) {
			$result[] = $replacement;
			$done     = true;
			continue;
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			$block['innerBlocks'] = verdix_replace_blocks( $block['innerBlocks'], $name, $replacement, $done );
		}
		$result[] = $block;
	}
	return $result;
}

function verdix_replace_ultimate_blocks_content( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || ! has_block( 'ub/review', $post->post_content ) ) {
		return false;
	}

	$replacement = array(
		'blockName'    => 'verdix/review-box',
		'attrs'        => array(),
		'innerBlocks'  => array(),
		'innerHTML'    => '',
		'innerContent' => array(),
	);

	$done   = false;
	$blocks = verdix_replace_blocks( parse_blocks( $post->post_content ), 'ub/review', $replacement, $done );

	return verdix_apply_content_replacement( $post_id, serialize_blocks( $blocks ) );
}

function verdix_apply_content_replacement( $post_id, $content ) {
	$updated = wp_update_post(
		wp_slash(
			array(
				'ID'           => $post_id,
				'post_content' => $content,
			)
		),
		true
	);

	if ( is_wp_error( $updated ) ) {
		return false;
	}

	$review = verdix_get_review( $post_id );
	if ( $review ) {
		$review['position'] = 'manual';
		verdix_save_review( $post_id, $review );
	}

	return true;
}

// =========================================================================
// Rank Math
// =========================================================================

function verdix_get_rank_math_schema( $post_id ) {
	$meta = get_post_meta( $post_id );
	foreach ( $meta as $key => $values ) {
		if ( 0 !== strpos( $key, 'rank_math_schema_' ) ) {
			continue;
		}
		$schema = maybe_unserialize( $values[0] );
		if ( is_array( $schema ) && ! empty( $schema['review']['reviewRating']['ratingValue'] ) ) {
			return $schema;
		}
	}

	if ( ! empty( $meta['rank_math_snippet_review_rating_value'][0] ) ) {
		$type = isset( $meta['rank_math_rich_snippet'][0] ) ? $meta['rank_math_rich_snippet'][0] : 'Product';
		return array(
			'@type'       => ucfirst( $type ),
			'name'        => isset( $meta['rank_math_snippet_name'][0] ) ? $meta['rank_math_snippet_name'][0] : '',
			'description' => isset( $meta['rank_math_snippet_desc'][0] ) ? $meta['rank_math_snippet_desc'][0] : '',
			'offers'      => array(
				'price'         => isset( $meta['rank_math_snippet_product_price'][0] ) ? $meta['rank_math_snippet_product_price'][0] : '',
				'priceCurrency' => isset( $meta['rank_math_snippet_product_currency'][0] ) ? $meta['rank_math_snippet_product_currency'][0] : '',
			),
			'review'      => array(
				'reviewRating' => array( 'ratingValue' => $meta['rank_math_snippet_review_rating_value'][0] ),
			),
		);
	}

	return null;
}

function verdix_get_rank_math_posts() {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$post_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT pm.post_id
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE ( pm.meta_key LIKE %s OR pm.meta_key = %s )
			AND p.post_status IN (%s, %s, %s, %s)
			ORDER BY p.post_date DESC",
			$wpdb->esc_like( 'rank_math_schema_' ) . '%',
			'rank_math_snippet_review_rating_value',
			'publish',
			'draft',
			'pending',
			'private'
		)
	);

	$posts = array();
	foreach ( $post_ids as $post_id ) {
		$post = get_post( absint( $post_id ) );
		if ( $post && verdix_get_rank_math_schema( $post->ID ) ) {
			$posts[] = $post;
		}
	}

	return $posts;
}

function verdix_rank_math_rating( $schema ) {
	$rating = $schema['review']['reviewRating'];
	$best   = ! empty( $rating['bestRating'] ) ? (float) $rating['bestRating'] : 5;
	return array(
		'raw'    => (float) $rating['ratingValue'],
		'best'   => $best,
		'rating' => verdix_scale_to_stars( $rating['ratingValue'], $best ),
	);
}

function verdix_resolve_rank_math_vars( $value, $post_id ) {
	if ( ! is_string( $value ) || false === strpos( $value, '%' ) ) {
		return $value;
	}

	$post   = get_post( $post_id );
	$author = $post ? get_the_author_meta( 'display_name', $post->post_author ) : '';
	$value  = strtr(
		$value,
		array(
			'%seo_title%'       => $post ? $post->post_title : '',
			'%title%'           => $post ? $post->post_title : '',
			'%name%'            => $author,
			'%seo_description%' => $post ? wp_trim_words( wp_strip_all_tags( $post->post_excerpt ), 30 ) : '',
		)
	);

	if ( false !== strpos( $value, '%' ) && class_exists( '\\RankMath\\Helper' ) ) {
		$value = \RankMath\Helper::replace_vars( $value, $post );
	}

	return trim( preg_replace( '/%[a-z_]+(\([^)]*\))?%/i', '', $value ) );
}

function verdix_migrate_post_rank_math( $post_id ) {
	$schema = verdix_get_rank_math_schema( $post_id );
	if ( ! $schema ) {
		return new WP_Error( 'no_review', __( 'No Rank Math review data found.', 'verdix-reviews' ) );
	}

	if ( verdix_post_has_verdix_data( $post_id ) ) {
		return new WP_Error( 'already_migrated', __( 'Already has Verdix Reviews data.', 'verdix-reviews' ) );
	}

	$rating = verdix_rank_math_rating( $schema );
	$type   = isset( $schema['@type'] ) && is_string( $schema['@type'] ) ? $schema['@type'] : 'Product';
	$name   = isset( $schema['name'] ) ? verdix_resolve_rank_math_vars( $schema['name'], $post_id ) : '';
	$offers = isset( $schema['offers'] ) && is_array( $schema['offers'] ) ? $schema['offers'] : array();
	$desc   = ! empty( $schema['description'] ) ? $schema['description'] : ( isset( $schema['review']['reviewBody'] ) ? $schema['review']['reviewBody'] : '' );
	$desc   = verdix_resolve_rank_math_vars( $desc, $post_id );

	verdix_save_review(
		$post_id,
		array(
			'rating'       => $rating['rating'],
			'rating_type'  => 'star',
			'position'     => '',
			'heading'      => $name,
			'summary'      => $desc,
			'pros'         => array(),
			'cons'         => array(),
			'schema_type'  => verdix_map_schema_type( $type ),
			'product_name' => $name,
			'price'        => isset( $offers['price'] ) ? $offers['price'] : '',
			'currency'     => ! empty( $offers['priceCurrency'] ) ? $offers['priceCurrency'] : 'USD',
			'cta_text'     => '',
			'cta_url'      => isset( $offers['url'] ) ? $offers['url'] : '',
			'author_name'  => isset( $schema['review']['author']['name'] ) ? verdix_resolve_rank_math_vars( $schema['review']['author']['name'], $post_id ) : '',
		)
	);

	return true;
}

function verdix_migration_row_rank_math( $post ) {
	$schema = verdix_get_rank_math_schema( $post->ID );
	if ( ! $schema ) {
		return array();
	}

	$rating = verdix_rank_math_rating( $schema );

	return array(
		'type'   => esc_html( 'snippet' ),
		'rating' => esc_html( $rating['raw'] . '/' . $rating['best'] . ' &rarr; ' . $rating['rating'] ),
		'schema' => esc_html( isset( $schema['@type'] ) && is_string( $schema['@type'] ) ? $schema['@type'] : '-' ),
	);
}

function verdix_replace_rank_math_content( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return false;
	}

	$content = preg_replace( '/\[rank_math_rich_snippet\b[^\]]*\]/', '[verdix]', $post->post_content, 1, $count );
	if ( ! $count ) {
		return false;
	}

	return verdix_apply_content_replacement( $post_id, $content );
}

// =========================================================================
// Schema suppression setting
// =========================================================================

function verdix_ajax_migration_stop_schema() {
	check_ajax_referer( 'verdix_migrate', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'verdix-reviews' ) ) );
	}

	$source  = isset( $_POST['source'] ) ? sanitize_key( wp_unslash( $_POST['source'] ) ) : '';
	$enabled = ! empty( $_POST['enabled'] );
	$sources = verdix_get_migration_sources();

	if ( ! isset( $sources[ $source ] ) || ! isset( $sources[ $source ]['plugin_active'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Unknown source.', 'verdix-reviews' ) ) );
	}

	$stopped = (array) get_option( 'verdix_migration_stop_schema', array() );
	$stopped = array_diff( $stopped, array( $source ) );
	if ( $enabled ) {
		$stopped[] = $source;
	}
	update_option( 'verdix_migration_stop_schema', array_values( $stopped ) );

	wp_send_json_success();
}
add_action( 'wp_ajax_verdix_migration_stop_schema', 'verdix_ajax_migration_stop_schema' );
