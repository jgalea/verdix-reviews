<?php
defined( 'ABSPATH' ) || exit;

/**
 * Whether a schema type can produce a Google review snippet.
 *
 * @param string $type Schema type.
 * @return array{status: string, message: string} Status is eligible, conditional, ineligible or unknown.
 */
function verdix_schema_eligibility( $type ) {
	$eligible    = array( 'Product', 'SoftwareApplication', 'Book', 'Movie', 'TVSeries', 'VideoGame', 'MusicAlbum', 'Recipe', 'Course', 'Event' );
	$conditional = array( 'LocalBusiness', 'Restaurant', 'Organization' );
	$ineligible  = array( 'Thing', 'CreativeWork' );

	if ( in_array( $type, $eligible, true ) ) {
		$result = array(
			'status'  => 'eligible',
			'message' => '',
		);
	} elseif ( in_array( $type, $conditional, true ) ) {
		$result = array(
			'status'  => 'conditional',
			'message' => __( 'Google only shows stars for this type when the reviews are about other businesses. A site reviewing itself, or reviews written by the business owner, is not eligible.', 'verdix-reviews' ),
		);
	} elseif ( in_array( $type, $ineligible, true ) ) {
		$result = array(
			'status'  => 'ineligible',
			'message' => __( 'Google does not support review snippets for this type. Choose a more specific type such as Product, Book or Movie.', 'verdix-reviews' ),
		);
	} else {
		$result = array(
			'status'  => 'unknown',
			'message' => __( 'This type is not in the list Google supports for review snippets. Test it in the Rich Results Test.', 'verdix-reviews' ),
		);
	}

	return apply_filters( 'verdix_schema_eligibility', $result, $type );
}

/**
 * Names of active plugins that may print their own review or product schema.
 *
 * @param int $post_id Post ID.
 * @return array{review: string[], seo: string[]}
 */
function verdix_schema_detect_conflicts( $post_id ) {
	$active = (array) get_option( 'active_plugins', array() );
	if ( is_multisite() ) {
		$active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
	}
	$folders = array_map(
		function ( $file ) {
			return strtok( $file, '/' );
		},
		$active
	);

	$review_plugins = array(
		'wp-review'        => 'WP Review',
		'wp-review-pro'    => 'WP Review Pro',
		'taqyeem'          => 'Taqyeem',
		'ultimate-blocks'  => 'Ultimate Blocks',
		'site-reviews'     => 'Site Reviews',
	);
	$seo_plugins    = array(
		'wordpress-seo'          => 'Yoast SEO',
		'all-in-one-seo-pack'    => 'All in One SEO',
	);

	$found = array(
		'review' => array(),
		'seo'    => array(),
	);

	foreach ( $review_plugins as $folder => $name ) {
		if ( in_array( $folder, $folders, true ) ) {
			$found['review'][] = $name;
		}
	}
	foreach ( $seo_plugins as $folder => $name ) {
		if ( in_array( $folder, $folders, true ) ) {
			$found['seo'][] = $name;
		}
	}

	if ( in_array( 'seo-by-rank-math', $folders, true ) ) {
		$snippet = get_post_meta( $post_id, 'rank_math_rich_snippet', true );
		if ( $snippet && 'off' !== $snippet ) {
			$found['review'][] = 'Rank Math (' . sanitize_text_field( $snippet ) . ' schema)';
		} else {
			$found['seo'][] = 'Rank Math';
		}
	}

	return $found;
}

/**
 * Check a review's structured data against Google's review snippet requirements.
 *
 * @param int $post_id Post ID.
 * @return array<int, array{level: string, code: string, message: string}> Issues, errors first.
 */
function verdix_schema_check( $post_id ) {
	$issues = array();
	$add    = function ( $level, $code, $message ) use ( &$issues ) {
		$issues[] = array(
			'level'   => $level,
			'code'    => $code,
			'message' => $message,
		);
	};

	$review = verdix_get_review( $post_id );
	if ( ! $review ) {
		$add( 'error', 'no_review', __( 'No review data saved for this post. Save the post and try again.', 'verdix-reviews' ) );
		return $issues;
	}

	if ( empty( $review['rating'] ) ) {
		$add( 'error', 'rating_missing', __( 'No rating set. Verdix prints no schema until the rating is above zero.', 'verdix-reviews' ) );
		return $issues;
	}

	$schema = verdix_build_schema( $post_id, $review );
	$type   = isset( $schema['@type'] ) ? $schema['@type'] : '';

	$eligibility = verdix_schema_eligibility( $type );
	if ( 'ineligible' === $eligibility['status'] ) {
		$add( 'error', 'type_ineligible', $eligibility['message'] );
	} elseif ( 'eligible' !== $eligibility['status'] ) {
		$add( 'warning', 'type_' . $eligibility['status'], $eligibility['message'] );
	}

	if ( empty( $schema['name'] ) ) {
		$add( 'error', 'name_missing', __( 'The reviewed item has no name. Set the item name or a post title.', 'verdix-reviews' ) );
	}

	$author = isset( $schema['review']['author']['name'] ) ? $schema['review']['author']['name'] : '';
	if ( '' === $author ) {
		$add( 'error', 'author_missing', __( 'The review has no author name.', 'verdix-reviews' ) );
	} elseif ( mb_strlen( $author ) >= 100 ) {
		$add( 'error', 'author_too_long', __( 'The author name must be shorter than 100 characters.', 'verdix-reviews' ) );
	}

	if ( ! isset( $schema['review']['reviewRating']['ratingValue'] ) || '' === $schema['review']['reviewRating']['ratingValue'] ) {
		$add( 'error', 'rating_value_missing', __( 'The review has no ratingValue.', 'verdix-reviews' ) );
	}

	$missing = array(
		'Product'             => array(
			array( 'offers', 'info', 'offers_missing', __( 'No price set. Add one to be eligible for price information in Product results.', 'verdix-reviews' ) ),
			array( 'image', 'info', 'image_missing', __( 'No image. Set a featured image; Google recommends one for Product results.', 'verdix-reviews' ) ),
		),
		'SoftwareApplication' => array(
			array( 'offers', 'warning', 'offers_missing', __( 'Software app results require a price. Add one (use 0 for free apps).', 'verdix-reviews' ) ),
		),
		'Book'                => array(
			array( 'author', 'warning', 'book_author_missing', __( 'Add the book author in the type-specific fields.', 'verdix-reviews' ) ),
		),
		'Movie'               => array(
			array( 'image', 'warning', 'image_missing', __( 'Set a featured image; Movie results need one.', 'verdix-reviews' ) ),
		),
		'Recipe'              => array(
			array( 'image', 'error', 'image_missing', __( 'Recipes require an image. Set a featured image.', 'verdix-reviews' ) ),
			array( 'recipeIngredient', 'warning', 'ingredients_missing', __( 'Add the recipe ingredients.', 'verdix-reviews' ) ),
		),
		'Course'              => array(
			array( 'provider', 'error', 'provider_missing', __( 'Courses require a provider. Fill it in under the type-specific fields.', 'verdix-reviews' ) ),
		),
		'Event'               => array(
			array( 'startDate', 'error', 'start_date_missing', __( 'Events require a start date.', 'verdix-reviews' ) ),
			array( 'location', 'error', 'location_missing', __( 'Events require a location.', 'verdix-reviews' ) ),
		),
		'LocalBusiness'       => array(
			array( 'address', 'error', 'address_missing', __( 'Local businesses require an address.', 'verdix-reviews' ) ),
		),
		'Restaurant'          => array(
			array( 'address', 'error', 'address_missing', __( 'Restaurants require an address.', 'verdix-reviews' ) ),
		),
	);
	if ( isset( $missing[ $type ] ) ) {
		foreach ( $missing[ $type ] as $rule ) {
			if ( empty( $schema[ $rule[0] ] ) ) {
				$add( $rule[1], $rule[2], $rule[3] );
			}
		}
	}

	if ( 'publish' !== get_post_status( $post_id ) ) {
		$add( 'info', 'not_public', __( 'This post is not published yet, so Google cannot test it.', 'verdix-reviews' ) );
	}

	$conflicts = verdix_schema_detect_conflicts( $post_id );
	foreach ( $conflicts['review'] as $name ) {
		$add(
			'warning',
			'conflict_review_plugin',
			/* translators: %s: plugin name. */
			sprintf( __( '%s is active and may print its own review schema for this post. Two review nodes on one page can confuse Google; disable one.', 'verdix-reviews' ), $name )
		);
	}
	if ( $conflicts['seo'] ) {
		$add(
			'info',
			'conflict_seo_plugin',
			/* translators: %s: comma-separated plugin names. */
			sprintf( __( '%s is active. Check the Rich Results Test to make sure it does not print a second Product or Review node.', 'verdix-reviews' ), implode( ', ', $conflicts['seo'] ) )
		);
	}

	$weight = array(
		'error'   => 0,
		'warning' => 1,
		'info'    => 2,
	);
	usort(
		$issues,
		function ( $a, $b ) use ( $weight ) {
			return $weight[ $a['level'] ] - $weight[ $b['level'] ];
		}
	);

	return $issues;
}

/**
 * Print the schema check results as a list (classic meta box).
 *
 * @param int $post_id Post ID.
 */
function verdix_schema_check_render( $post_id ) {
	$issues = verdix_schema_check( $post_id );
	if ( empty( $issues ) ) {
		echo '<p class="verdix-schema-check__ok">' . esc_html__( 'No schema issues found.', 'verdix-reviews' ) . '</p>';
	} else {
		echo '<ul class="verdix-schema-check">';
		foreach ( $issues as $issue ) {
			printf(
				'<li class="verdix-schema-check__item verdix-schema-check__item--%1$s"><strong>%2$s</strong> %3$s</li>',
				esc_attr( $issue['level'] ),
				esc_html( ucfirst( $issue['level'] ) ),
				esc_html( $issue['message'] )
			);
		}
		echo '</ul>';
	}

	if ( 'publish' === get_post_status( $post_id ) ) {
		printf(
			'<p><a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a></p>',
			esc_url( verdix_rich_results_test_url( $post_id ) ),
			esc_html__( 'Test in Google Rich Results Test', 'verdix-reviews' )
		);
	}
}

function verdix_rich_results_test_url( $post_id ) {
	return 'https://search.google.com/test/rich-results?url=' . rawurlencode( get_permalink( $post_id ) );
}
