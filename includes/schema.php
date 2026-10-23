<?php
defined( 'ABSPATH' ) || exit;

/**
 * Get all supported JSON-LD schema types.
 *
 * Returns an associative array of [ value => human label ]. Types without
 * dedicated field handlers in verdix_build_schema() fall through to a
 * generic Review structure (name, review, url, image) which is still valid
 * JSON-LD for Google.
 *
 * @return array<string, string>
 */
function verdix_get_schema_types() {
	$types = array(
		'Product'             => __( 'Product', 'verdix-reviews' ),
		'SoftwareApplication' => __( 'Software Application', 'verdix-reviews' ),
		'Book'                => __( 'Book', 'verdix-reviews' ),
		'Movie'               => __( 'Movie', 'verdix-reviews' ),
		'TVSeries'            => __( 'TV Series', 'verdix-reviews' ),
		'VideoGame'           => __( 'Video Game', 'verdix-reviews' ),
		'MusicAlbum'          => __( 'Music Album', 'verdix-reviews' ),
		'Recipe'              => __( 'Recipe', 'verdix-reviews' ),
		'Course'              => __( 'Course', 'verdix-reviews' ),
		'Event'               => __( 'Event', 'verdix-reviews' ),
		'LocalBusiness'       => __( 'Local Business', 'verdix-reviews' ),
		'Restaurant'          => __( 'Restaurant', 'verdix-reviews' ),
		'CreativeWork'        => __( 'Creative Work', 'verdix-reviews' ),
		'Thing'               => __( 'Thing (generic)', 'verdix-reviews' ),
	);

	return apply_filters( 'verdix_schema_types', $types );
}

/**
 * Get optional per-type fields that users can fill in to enrich the JSON-LD
 * output for a given schema type.
 *
 * Each type maps to an array of [ field_key => [ label, type ] ] where type
 * is one of: text, textarea, date, number. Fields that are omitted or empty
 * are simply not emitted in the schema.
 *
 * @return array<string, array<string, array{label: string, type: string}>>
 */
function verdix_get_type_fields() {
	$fields = array(
		'Book'          => array(
			'author' => array( 'label' => __( 'Author', 'verdix-reviews' ),           'type' => 'text' ),
			'isbn'   => array( 'label' => __( 'ISBN', 'verdix-reviews' ),             'type' => 'text' ),
		),
		'Movie'         => array(
			'director' => array( 'label' => __( 'Director', 'verdix-reviews' ),       'type' => 'text' ),
			'duration' => array( 'label' => __( 'Duration (e.g. PT2H15M)', 'verdix-reviews' ), 'type' => 'text' ),
		),
		'TVSeries'      => array(
			'actor'           => array( 'label' => __( 'Lead Actor', 'verdix-reviews' ),        'type' => 'text' ),
			'numberOfSeasons' => array( 'label' => __( 'Number of Seasons', 'verdix-reviews' ), 'type' => 'number' ),
		),
		'VideoGame'     => array(
			'gamePlatform' => array( 'label' => __( 'Platform(s)', 'verdix-reviews' ),  'type' => 'text' ),
		),
		'MusicAlbum'    => array(
			'byArtist' => array( 'label' => __( 'Artist', 'verdix-reviews' ),          'type' => 'text' ),
		),
		'Recipe'        => array(
			'recipeIngredient' => array( 'label' => __( 'Ingredients (one per line)', 'verdix-reviews' ), 'type' => 'textarea' ),
			'prepTime'         => array( 'label' => __( 'Prep Time (e.g. PT30M)', 'verdix-reviews' ),    'type' => 'text' ),
			'cookTime'         => array( 'label' => __( 'Cook Time (e.g. PT1H)', 'verdix-reviews' ),     'type' => 'text' ),
			'recipeYield'      => array( 'label' => __( 'Yield (e.g. 4 servings)', 'verdix-reviews' ),   'type' => 'text' ),
		),
		'Course'        => array(
			'provider' => array( 'label' => __( 'Provider', 'verdix-reviews' ),        'type' => 'text' ),
		),
		'Event'         => array(
			'startDate' => array( 'label' => __( 'Start Date', 'verdix-reviews' ),     'type' => 'date' ),
			'location'  => array( 'label' => __( 'Location', 'verdix-reviews' ),       'type' => 'text' ),
		),
		'LocalBusiness' => array(
			'address'    => array( 'label' => __( 'Address', 'verdix-reviews' ),       'type' => 'textarea' ),
			'telephone'  => array( 'label' => __( 'Telephone', 'verdix-reviews' ),     'type' => 'text' ),
			'priceRange' => array( 'label' => __( 'Price Range (e.g. $$)', 'verdix-reviews' ), 'type' => 'text' ),
		),
		'Restaurant'    => array(
			'address'       => array( 'label' => __( 'Address', 'verdix-reviews' ),    'type' => 'textarea' ),
			'telephone'     => array( 'label' => __( 'Telephone', 'verdix-reviews' ),  'type' => 'text' ),
			'servesCuisine' => array( 'label' => __( 'Cuisine', 'verdix-reviews' ),    'type' => 'text' ),
			'priceRange'    => array( 'label' => __( 'Price Range (e.g. $$)', 'verdix-reviews' ), 'type' => 'text' ),
		),
		'CreativeWork'  => array(
			'creator' => array( 'label' => __( 'Creator', 'verdix-reviews' ),          'type' => 'text' ),
		),
	);

	return apply_filters( 'verdix_type_fields', $fields );
}

/**
 * Output JSON-LD schema on singular posts/pages that have review data.
 */
function verdix_output_schema() {
	if ( ! is_singular() ) {
		return;
	}

	$post_id = get_the_ID();
	$review  = verdix_get_review( $post_id );

	if ( ! $review || empty( $review['rating'] ) ) {
		return;
	}

	$schema = verdix_build_schema( $post_id, $review );
	if ( empty( $schema ) ) {
		return;
	}

	// Single script tag output — no duplicates.
	$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	// Prevent breaking out of the script tag (standard JSON-LD safety encoding).
	$json = str_replace( '</', '<\/', $json );
	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		$json // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded with script-tag-safe escaping.
	);
}
add_action( 'wp_head', 'verdix_output_schema', 1 );

/**
 * Build the JSON-LD schema array for a review.
 *
 * @param int   $post_id Post ID.
 * @param array $review  Review data.
 * @return array Schema array.
 */
function verdix_build_schema( $post_id, $review ) {
	$options     = get_option( 'verdix_settings', array() );
	$valid_types = array_keys( verdix_get_schema_types() );
	$schema_type = ! empty( $review['schema_type'] ) && in_array( $review['schema_type'], $valid_types, true )
		? $review['schema_type']
		: 'Product';
	$post        = get_post( $post_id );

	// Determine author name: review-level override > settings default > post author display name.
	$author_name = '';
	if ( ! empty( $review['author_name'] ) ) {
		$author_name = $review['author_name'];
	} elseif ( ! empty( $options['default_author_name'] ) ) {
		$author_name = $options['default_author_name'];
	} else {
		$author = get_userdata( $post->post_author );
		$author_name = $author ? $author->display_name : '';
	}

	// Final fallback so the Review author always has a name (required by Google).
	if ( '' === $author_name ) {
		$author_name = get_bloginfo( 'name' );
	}

	// Product name: review-level override > post title.
	$product_name = ! empty( $review['product_name'] ) ? $review['product_name'] : get_the_title( $post_id );

	// Normalize rating to 0-5 scale for schema output.
	$rating_type     = ! empty( $review['rating_type'] ) ? $review['rating_type'] : 'star';
	$schema_rating   = verdix_normalize_to_star_scale( $review['rating'], $rating_type );

	$review_name = get_the_title( $post_id );

	$schema = array(
		'@context'        => 'https://schema.org',
		'@type'           => $schema_type,
		'name'            => $product_name,
		'review'          => array(
			'@type'        => 'Review',
			'name'         => $review_name,
			'author'       => array(
				'@type' => 'Person',
				'name'  => $author_name,
			),
			'reviewRating' => array(
				'@type'       => 'Rating',
				'ratingValue' => (string) $schema_rating,
				'bestRating'  => '5',
				'worstRating' => '1',
			),
		),
	);

	/**
	 * Filter the aggregateRating for a review. Must come from visitor ratings, not the author's score.
	 *
	 * @param array|null $aggregate Schema.org AggregateRating array, or null for none.
	 * @param int        $post_id   Post ID.
	 * @param array      $review    Review data.
	 */
	$aggregate = apply_filters( 'verdix_schema_aggregate_rating', null, $post_id, $review );
	if ( ! empty( $aggregate ) && is_array( $aggregate ) ) {
		$schema['aggregateRating'] = $aggregate;
	}

	if ( ! empty( $review['summary'] ) ) {
		$schema['review']['reviewBody'] = $review['summary'];
	}

	// Add datePublished.
	$schema['review']['datePublished'] = get_the_date( 'c', $post_id );

	if ( ! empty( $review['last_tested'] ) ) {
		$schema['review']['dateModified'] = $review['last_tested'];
	}

	// Add description from post excerpt or summary.
	if ( ! empty( $review['summary'] ) ) {
		$schema['description'] = $review['summary'];
	} elseif ( has_excerpt( $post_id ) ) {
		$schema['description'] = get_the_excerpt( $post_id );
	}

	// Add URL.
	$schema['url'] = get_permalink( $post_id );

	// Add image if post has a featured image.
	if ( has_post_thumbnail( $post_id ) ) {
		$schema['image'] = get_the_post_thumbnail_url( $post_id, 'full' );
	}

	// Type-specific additions.
	switch ( $schema_type ) {
		case 'Product':
			$schema = verdix_schema_product( $schema, $review );
			break;

		case 'SoftwareApplication':
			$schema = verdix_schema_software( $schema, $review );
			break;
	}

	// Merge user-provided per-type fields (e.g. Book author, Recipe ingredients).
	$schema = verdix_schema_apply_type_fields( $schema, $schema_type, $review );

	/**
	 * Filter the complete JSON-LD schema array before output.
	 *
	 * @param array $schema  Schema array.
	 * @param int   $post_id Post ID.
	 * @param array $review  Review data.
	 */
	return apply_filters( 'verdix_schema', $schema, $post_id, $review );
}

/**
 * Add Product-specific schema fields.
 *
 * @param array $schema Schema array.
 * @param array $review Review data.
 * @return array Modified schema.
 */
function verdix_schema_product( $schema, $review ) {
	$offers = verdix_schema_offers( $schema['url'], $review, 'https://schema.org/InStock' );
	if ( $offers ) {
		$schema['offers'] = $offers;
	}

	if ( ! empty( $review['product_name'] ) ) {
		$schema['brand'] = array(
			'@type' => 'Brand',
			'name'  => $review['product_name'],
		);
	}

	return $schema;
}

function verdix_schema_offers( $default_url, $review, $availability = '' ) {
	$items = array();

	foreach ( verdix_get_offers( $review ) as $offer ) {
		if ( '' === (string) $offer['price'] ) {
			continue;
		}

		$item = array(
			'@type'         => 'Offer',
			'url'           => $offer['href'] ? $offer['href'] : $default_url,
			'price'         => $offer['price'],
			'priceCurrency' => ! empty( $offer['currency'] ) ? $offer['currency'] : 'USD',
		);
		if ( $availability ) {
			$item['availability'] = $availability;
		}
		if ( ! empty( $offer['merchant'] ) ) {
			$item['seller'] = array(
				'@type' => 'Organization',
				'name'  => $offer['merchant'],
			);
		}
		$items[] = $item;
	}

	if ( count( $items ) < 2 ) {
		return $items ? $items[0] : array();
	}

	$currency = $items[0]['priceCurrency'];
	$prices   = array();
	foreach ( $items as $item ) {
		if ( $item['priceCurrency'] === $currency && is_numeric( $item['price'] ) ) {
			$prices[] = (float) $item['price'];
		}
	}

	$aggregate = array(
		'@type'         => 'AggregateOffer',
		'priceCurrency' => $currency,
		'offerCount'    => count( $items ),
		'offers'        => $items,
	);
	if ( $prices ) {
		$aggregate['lowPrice']  = min( $prices );
		$aggregate['highPrice'] = max( $prices );
	}

	return $aggregate;
}

/**
 * Merge user-supplied per-type fields into the schema.
 *
 * Reads $review['type_fields'][ $schema_type ] and emits each non-empty field
 * as a top-level property on the schema object. Multi-line textarea values
 * (e.g. Recipe ingredients) become arrays, one item per non-empty line.
 *
 * @param array  $schema      Current schema array.
 * @param string $schema_type Schema type slug.
 * @param array  $review      Review data.
 * @return array Schema with type fields merged in.
 */
function verdix_schema_apply_type_fields( $schema, $schema_type, $review ) {
	if ( empty( $review['type_fields'][ $schema_type ] ) || ! is_array( $review['type_fields'][ $schema_type ] ) ) {
		return $schema;
	}

	$registry = verdix_get_type_fields();
	if ( empty( $registry[ $schema_type ] ) ) {
		return $schema;
	}

	foreach ( $review['type_fields'][ $schema_type ] as $field_key => $value ) {
		if ( ! isset( $registry[ $schema_type ][ $field_key ] ) ) {
			continue;
		}
		if ( '' === $value || null === $value ) {
			continue;
		}

		$field_type = isset( $registry[ $schema_type ][ $field_key ]['type'] ) ? $registry[ $schema_type ][ $field_key ]['type'] : 'text';

		if ( 'textarea' === $field_type ) {
			$lines = array_filter(
				array_map( 'trim', preg_split( "/\r\n|\r|\n/", (string) $value ) ),
				function ( $l ) {
					return '' !== $l;
				}
			);
			if ( ! empty( $lines ) ) {
				$schema[ $field_key ] = count( $lines ) === 1 ? array_values( $lines )[0] : array_values( $lines );
			}
		} elseif ( 'number' === $field_type ) {
			$schema[ $field_key ] = is_numeric( $value ) ? 0 + $value : $value;
		} else {
			$schema[ $field_key ] = $value;
		}
	}

	return $schema;
}

/**
 * Add SoftwareApplication-specific schema fields.
 *
 * @param array $schema Schema array.
 * @param array $review Review data.
 * @return array Modified schema.
 */
function verdix_schema_software( $schema, $review ) {
	$schema['applicationCategory'] = 'WebApplication';

	$offers = verdix_schema_offers( $schema['url'], $review );
	if ( $offers ) {
		$schema['offers'] = $offers;
	}

	return $schema;
}
