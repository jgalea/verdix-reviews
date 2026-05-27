<?php
defined( 'ABSPATH' ) || exit;

// Menu registration is handled by admin/settings.php.

// =========================================================================
// AJAX handlers
// =========================================================================

/**
 * Handle single post migration AJAX request.
 */
function verdix_ajax_migrate() {
	check_ajax_referer( 'verdix_migrate', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'verdix-reviews' ) ) );
	}

	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$source  = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : 'wp_review_pro';

	if ( ! $post_id ) {
		wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'verdix-reviews' ) ) );
	}

	$result = verdix_migrate_post( $post_id, $source );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	wp_send_json_success(
		array(
			'message' => sprintf(
				/* translators: %s: post title */
				__( 'Migrated: %s', 'verdix-reviews' ),
				get_the_title( $post_id )
			),
			'post_id' => $post_id,
		)
	);
}
add_action( 'wp_ajax_verdix_migrate', 'verdix_ajax_migrate' );

/**
 * Handle bulk migration AJAX request.
 */
function verdix_ajax_migrate_all() {
	check_ajax_referer( 'verdix_migrate', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'verdix-reviews' ) ) );
	}

	$source = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : 'wp_review_pro';

	$sources = verdix_get_migration_sources();
	if ( ! isset( $sources[ $source ] ) ) {
		wp_send_json_error( array( 'message' => __( 'Unknown source.', 'verdix-reviews' ) ) );
	}
	$posts = call_user_func( $sources[ $source ]['detect'] );

	$migrated = 0;
	$errors   = array();

	foreach ( $posts as $post ) {
		$result = verdix_migrate_post( $post->ID, $source );
		if ( is_wp_error( $result ) ) {
			$errors[] = sprintf( '%s: %s', get_the_title( $post->ID ), $result->get_error_message() );
		} else {
			$migrated++;
		}
	}

	wp_send_json_success(
		array(
			'migrated' => $migrated,
			'errors'   => $errors,
			'message'  => sprintf(
				/* translators: %d: number of posts migrated */
				__( 'Migration complete. %d posts migrated.', 'verdix-reviews' ),
				$migrated
			),
		)
	);
}
add_action( 'wp_ajax_verdix_migrate_all', 'verdix_ajax_migrate_all' );

// =========================================================================
// Migration: WP Review Pro
// =========================================================================

/**
 * Migrate a single post from the given source.
 *
 * @param int    $post_id Post ID.
 * @param string $source  Migration source key.
 * @return true|WP_Error True on success, WP_Error on failure.
 */
function verdix_migrate_post( $post_id, $source = 'wp_review_pro' ) {
	$sources = verdix_get_migration_sources();
	if ( ! isset( $sources[ $source ] ) || ! is_callable( $sources[ $source ]['migrate'] ) ) {
		return new WP_Error( 'invalid_source', __( 'Unknown migration source.', 'verdix-reviews' ) );
	}
	return call_user_func( $sources[ $source ]['migrate'], $post_id );
}

/**
 * Migrate a single post from WP Review Pro.
 *
 * @param int $post_id Post ID.
 * @return true|WP_Error
 */
function verdix_migrate_post_wp_review_pro( $post_id ) {
	$wp_review_type = get_post_meta( $post_id, 'wp_review_type', true );

	if ( empty( $wp_review_type ) || 'none' === $wp_review_type ) {
		return new WP_Error( 'no_review', __( 'No WP Review Pro data found.', 'verdix-reviews' ) );
	}

	// Already migrated?
	$existing = get_post_meta( $post_id, '_verdix_review', true );
	if ( ! empty( $existing ) ) {
		$existing_data = json_decode( $existing, true );
		if ( is_array( $existing_data ) && ! empty( $existing_data['rating'] ) ) {
			return new WP_Error( 'already_migrated', __( 'Already has Verdix Reviews data.', 'verdix-reviews' ) );
		}
	}

	// Read WP Review Pro meta.
	$rating      = get_post_meta( $post_id, 'wp_review_total', true );
	$heading     = get_post_meta( $post_id, 'wp_review_heading', true );
	$desc        = get_post_meta( $post_id, 'wp_review_desc', true );
	$pros_html   = get_post_meta( $post_id, 'wp_review_pros', true );
	$cons_html   = get_post_meta( $post_id, 'wp_review_cons', true );
	$product     = get_post_meta( $post_id, 'wp_review_product', true );
	$schema      = get_post_meta( $post_id, 'wp_review_schema', true );
	$price       = get_post_meta( $post_id, 'wp_review_price', true );
	$currency    = get_post_meta( $post_id, 'wp_review_currency', true );
	$url         = get_post_meta( $post_id, 'wp_review_url', true );
	$btn_txt     = get_post_meta( $post_id, 'wp_review_btn_txt', true );
	$author_name = get_post_meta( $post_id, 'wp_review_author', true );

	// Normalize the rating based on review type.
	$normalized_rating = verdix_normalize_wp_review_rating( $rating, $wp_review_type );

	// Parse pros/cons from HTML.
	$pros = verdix_parse_list_html( $pros_html );
	$cons = verdix_parse_list_html( $cons_html );

	// Map schema type.
	$schema_type = verdix_map_schema_type( $schema );

	$data = array(
		'rating'       => $normalized_rating,
		'rating_type'  => 'star',
		'position'     => '',
		'heading'      => $heading ?: '',
		'summary'      => $desc ?: '',
		'pros'         => $pros,
		'cons'         => $cons,
		'schema_type'  => $schema_type,
		'product_name' => $product ?: '',
		'price'        => $price ?: '',
		'currency'     => $currency ?: 'USD',
		'cta_text'     => $btn_txt ?: '',
		'cta_url'      => $url ?: '',
		'author_name'  => $author_name ?: '',
	);

	verdix_save_review( $post_id, $data );

	return true;
}

// =========================================================================
// Shared helpers
// =========================================================================

/**
 * Normalize an incoming WP Review Pro rating to the 0-5 star scale.
 *
 * The $review_type values 'point' and 'percentage' are the WP Review Pro source
 * format identifiers for migrated data, not Verdix Reviews rating modes —
 * everything is stored on the 0-5 star scale after import.
 *
 * @param string $rating      Raw WP Review Pro rating value.
 * @param string $review_type WP Review Pro review type ('star', 'point', or 'percentage').
 * @return float Normalized rating (0-5, half-star increments).
 */
function verdix_normalize_wp_review_rating( $rating, $review_type ) {
	$value = floatval( $rating );

	switch ( $review_type ) {
		case 'point':
			$value = $value / 2;
			break;
		case 'percentage':
			$value = $value / 20;
			break;
		case 'star':
		default:
			break;
	}

	$value = max( 0, min( 5, $value ) );
	$value = round( $value * 2 ) / 2;

	return $value;
}

/**
 * Parse HTML list items from WP Review Pro pros/cons HTML.
 *
 * @param string $html Raw HTML string.
 * @return array Array of clean text strings.
 */
function verdix_parse_list_html( $html ) {
	if ( empty( $html ) ) {
		return array();
	}

	$items = array();

	if ( preg_match_all( '/<li[^>]*>(.*?)<\/li>/si', $html, $matches ) ) {
		foreach ( $matches[1] as $match ) {
			$text = wp_strip_all_tags( $match );
			$text = trim( $text );
			if ( '' !== $text ) {
				$items[] = $text;
			}
		}
	}

	if ( empty( $items ) ) {
		$stripped = wp_strip_all_tags( $html );
		$lines   = preg_split( '/[\r\n]+/', $stripped );
		foreach ( $lines as $line ) {
			$text = trim( $line );
			if ( '' !== $text ) {
				$items[] = $text;
			}
		}
	}

	return $items;
}

/**
 * Map WP Review Pro schema type to Verdix Reviews schema type.
 *
 * @param string $schema WP Review Pro schema value.
 * @return string Verdix Reviews schema type.
 */
function verdix_map_schema_type( $schema ) {
	// If we natively support this type, pass it through unchanged.
	$supported = array_keys( verdix_get_schema_types() );
	if ( in_array( $schema, $supported, true ) ) {
		return $schema;
	}

	// Remap legacy / unsupported types to the closest supported one.
	$map = array(
		'Game'           => 'VideoGame',
		'MusicRecording' => 'MusicAlbum',
		'WebSite'        => 'CreativeWork',
		'Place'          => 'LocalBusiness',
		'Organization'   => 'LocalBusiness',
		'HowTo'          => 'CreativeWork',
		'MediaObject'    => 'CreativeWork',
		'Store'          => 'LocalBusiness',
	);

	if ( isset( $map[ $schema ] ) ) {
		return $map[ $schema ];
	}

	return 'Product';
}

// =========================================================================
// Migration row data callbacks
// =========================================================================

/**
 * Return display row data for a WP Review Pro post.
 *
 * @param WP_Post $post Post object.
 * @return array
 */
function verdix_migration_row_wp_review_pro( $post ) {
	$review_type = get_post_meta( $post->ID, 'wp_review_type', true );
	$rating      = get_post_meta( $post->ID, 'wp_review_total', true );
	$schema      = get_post_meta( $post->ID, 'wp_review_schema', true );
	$pros_html   = get_post_meta( $post->ID, 'wp_review_pros', true );
	$cons_html   = get_post_meta( $post->ID, 'wp_review_cons', true );
	$normalized  = verdix_normalize_wp_review_rating( $rating, $review_type );

	return array(
		'type'   => $review_type,
		'rating' => $rating . ( (string) $rating !== (string) $normalized ? ' &rarr; ' . $normalized : '' ),
		'schema' => $schema ?: '-',
		'pros'   => count( verdix_parse_list_html( $pros_html ) ),
		'cons'   => count( verdix_parse_list_html( $cons_html ) ),
	);
}

// =========================================================================
// Queries for migratable posts
// =========================================================================

/**
 * Get all posts with WP Review Pro data that haven't been migrated yet.
 *
 * @return WP_Post[] Array of post objects.
 */
function verdix_get_migratable_posts() {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$post_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT pm.post_id
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = %s
			AND pm.meta_value != %s
			AND pm.meta_value != %s
			AND p.post_status IN (%s, %s, %s, %s)
			ORDER BY p.post_date DESC",
			'wp_review_type',
			'',
			'none',
			'publish',
			'draft',
			'pending',
			'private'
		)
	);

	if ( empty( $post_ids ) ) {
		return array();
	}

	$posts = array();
	foreach ( $post_ids as $post_id ) {
		$post = get_post( absint( $post_id ) );
		if ( $post ) {
			$posts[] = $post;
		}
	}

	return $posts;
}

// =========================================================================
// Enqueue and render
// =========================================================================

/**
 * Enqueue migration page scripts.
 *
 * @param string $hook_suffix Current admin page.
 */
function verdix_migration_enqueue( $hook_suffix ) {
	if ( 'verdix_page_verdix-migration' !== $hook_suffix ) {
		return;
	}

	wp_enqueue_style(
		'verdix-admin',
		VERDIX_URL . 'assets/css/admin.css',
		array(),
		VERDIX_VERSION
	);

	wp_enqueue_script(
		'verdix-migration',
		VERDIX_URL . 'assets/js/migration.js',
		array(),
		VERDIX_VERSION,
		true
	);

	wp_localize_script(
		'verdix-migration',
		'verdixMigration',
		array(
			'nonce'   => wp_create_nonce( 'verdix_migrate' ),
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'i18n'    => array(
				'migrating'  => __( 'Migrating...', 'verdix-reviews' ),
				'migrated'   => __( 'Migrated', 'verdix-reviews' ),
				'error'      => __( 'Error', 'verdix-reviews' ),
				'confirmAll' => __( 'Migrate all posts? This cannot be undone.', 'verdix-reviews' ),
				'retry'      => __( 'Retry Migration', 'verdix-reviews' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'verdix_migration_enqueue' );

/**
 * Render the migration page.
 */
function verdix_migration_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	include VERDIX_DIR . 'admin/views/migration-page.php';
}
