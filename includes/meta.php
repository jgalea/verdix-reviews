<?php
defined( 'ABSPATH' ) || exit;

/**
 * Register the _verdix_review meta key for the REST API and block editor.
 */
function verdix_register_meta() {
	foreach ( verdix_get_post_types() as $post_type ) {
		register_post_meta(
			$post_type,
			'_verdix_review',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
				'sanitize_callback' => 'verdix_sanitize_meta',
			)
		);
		add_filter( "rest_prepare_{$post_type}", 'verdix_rest_hide_protected_review', 10, 2 );
	}
}
add_action( 'init', 'verdix_register_meta' );

function verdix_rest_hide_protected_review( $response, $post ) {
	if ( ! verdix_can_view_review( $post ) && isset( $response->data['meta']['_verdix_review'] ) ) {
		$response->data['meta']['_verdix_review'] = '';
	}
	return $response;
}

function verdix_get_post_types() {
	$options = get_option( 'verdix_settings', array() );
	$types   = isset( $options['post_types'] ) && is_array( $options['post_types'] ) ? $options['post_types'] : array( 'post', 'page' );

	return apply_filters( 'verdix_post_types', $types );
}

function verdix_can_view_review( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return false;
	}
	if ( current_user_can( 'edit_post', $post->ID ) ) {
		return true;
	}
	if ( post_password_required( $post ) ) {
		return false;
	}
	return 'publish' === $post->post_status || current_user_can( 'read_post', $post->ID );
}

function verdix_box_is_displayed( $post, $review ) {
	$options  = get_option( 'verdix_settings', array() );
	$position = ! empty( $review['position'] ) ? $review['position'] : ( isset( $options['default_position'] ) ? $options['default_position'] : 'manual' );
	$shown    = 'manual' !== $position || has_block( 'verdix/review-box', $post ) || has_shortcode( $post->post_content, 'verdix' );

	return (bool) apply_filters( 'verdix_box_is_displayed', $shown, $post, $review );
}

function verdix_get_schema_review( $post_id ) {
	$post   = get_post( $post_id );
	$review = $post ? verdix_get_review( $post->ID ) : null;

	if ( ! $review || empty( $review['rating'] ) || ! verdix_can_view_review( $post ) || ! verdix_box_is_displayed( $post, $review ) ) {
		return null;
	}

	return $review;
}

/**
 * Sanitize the _verdix_review meta value.
 *
 * Accepts a JSON string, validates its structure, and returns sanitized JSON.
 *
 * @param string $value Raw meta value.
 * @return string Sanitized JSON string.
 */
function verdix_sanitize_meta( $value ) {
	if ( empty( $value ) ) {
		return '';
	}

	$data = json_decode( $value, true );
	if ( ! is_array( $data ) ) {
		return '';
	}

	$clean = verdix_sanitize_data( $data );
	return wp_json_encode( $clean );
}

/**
 * Sanitize review data array.
 *
 * @param array $data Raw review data.
 * @return array Sanitized review data.
 */
function verdix_sanitize_data( $data ) {
	$defaults = verdix_get_defaults();

	$clean = array();

	$valid_styles   = array_keys( verdix_get_styles() );
	$clean['style'] = '';
	if ( isset( $data['style'] ) && in_array( $data['style'], $valid_styles, true ) ) {
		$clean['style'] = $data['style'];
	}

	$rating_types         = verdix_get_rating_types();
	$valid_rating_types   = array_keys( $rating_types );
	$clean['rating_type'] = 'star';
	if ( isset( $data['rating_type'] ) && in_array( $data['rating_type'], $valid_rating_types, true ) ) {
		$clean['rating_type'] = $data['rating_type'];
	}

	// Clamp rating to the registered max/step for this type.
	$clean['rating'] = 0;
	if ( isset( $data['rating'] ) ) {
		$type_config     = isset( $rating_types[ $clean['rating_type'] ] )
			? $rating_types[ $clean['rating_type'] ]
			: $rating_types['star'];
		$max             = isset( $type_config['max'] ) ? (float) $type_config['max'] : 5;
		$step            = isset( $type_config['step'] ) && $type_config['step'] > 0 ? (float) $type_config['step'] : 0.5;
		$rating          = floatval( $data['rating'] );
		$rating          = round( $rating / $step ) * $step;
		$clean['rating'] = max( 0, min( $max, $rating ) );
	}

	$valid_positions    = array( 'bottom', 'top', 'both', 'manual' );
	$clean['position']  = isset( $data['position'] ) && in_array( $data['position'], $valid_positions, true )
		? $data['position']
		: '';

	$clean['heading'] = '';
	if ( isset( $data['heading'] ) ) {
		$clean['heading'] = sanitize_text_field( $data['heading'] );
	}

	$clean['summary'] = '';
	if ( isset( $data['summary'] ) ) {
		$clean['summary'] = sanitize_textarea_field( $data['summary'] );
	}

	$clean['pros'] = array();
	if ( isset( $data['pros'] ) && is_array( $data['pros'] ) ) {
		foreach ( $data['pros'] as $pro ) {
			$sanitized = sanitize_text_field( $pro );
			if ( '' !== $sanitized ) {
				$clean['pros'][] = $sanitized;
			}
		}
	}

	$clean['cons'] = array();
	if ( isset( $data['cons'] ) && is_array( $data['cons'] ) ) {
		foreach ( $data['cons'] as $con ) {
			$sanitized = sanitize_text_field( $con );
			if ( '' !== $sanitized ) {
				$clean['cons'][] = $sanitized;
			}
		}
	}

	$valid_types          = array_keys( verdix_get_schema_types() );
	$clean['schema_type'] = $defaults['schema_type'];
	if ( isset( $data['schema_type'] ) && in_array( $data['schema_type'], $valid_types, true ) ) {
		$clean['schema_type'] = $data['schema_type'];
	}

	$clean['type_fields'] = array();
	if ( isset( $data['type_fields'] ) && is_array( $data['type_fields'] ) ) {
		$registry = verdix_get_type_fields();
		foreach ( $data['type_fields'] as $type_key => $type_values ) {
			if ( ! is_string( $type_key ) || ! isset( $registry[ $type_key ] ) || ! is_array( $type_values ) ) {
				continue;
			}
			$clean_type = array();
			foreach ( $registry[ $type_key ] as $field_key => $field_def ) {
				if ( ! isset( $type_values[ $field_key ] ) ) {
					continue;
				}
				$raw   = $type_values[ $field_key ];
				$ftype = isset( $field_def['type'] ) ? $field_def['type'] : 'text';
				if ( 'textarea' === $ftype ) {
					$val = sanitize_textarea_field( $raw );
				} elseif ( 'number' === $ftype ) {
					$val = is_numeric( $raw ) ? (float) $raw : '';
				} else {
					$val = sanitize_text_field( $raw );
				}
				if ( '' !== $val && null !== $val ) {
					$clean_type[ $field_key ] = $val;
				}
			}
			if ( ! empty( $clean_type ) ) {
				$clean['type_fields'][ $type_key ] = $clean_type;
			}
		}
	}

	$clean['product_name'] = '';
	if ( isset( $data['product_name'] ) ) {
		$clean['product_name'] = sanitize_text_field( $data['product_name'] );
	}

	foreach ( array( 'brand', 'sku', 'mpn' ) as $identifier ) {
		$clean[ $identifier ] = isset( $data[ $identifier ] ) ? sanitize_text_field( $data[ $identifier ] ) : '';
	}

	$gtin          = isset( $data['gtin'] ) ? preg_replace( '/\D/', '', (string) $data['gtin'] ) : '';
	$clean['gtin'] = in_array( strlen( $gtin ), array( 8, 12, 13, 14 ), true ) ? $gtin : '';

	$clean['price'] = '';
	if ( isset( $data['price'] ) ) {
		$clean['price'] = sanitize_text_field( $data['price'] );
	}

	$clean['currency'] = 'USD';
	if ( isset( $data['currency'] ) ) {
		$clean['currency'] = verdix_sanitize_currency( $data['currency'] );
	}

	$clean['cta_text'] = '';
	if ( isset( $data['cta_text'] ) ) {
		$clean['cta_text'] = sanitize_text_field( $data['cta_text'] );
	}

	$clean['cta_url'] = '';
	if ( isset( $data['cta_url'] ) ) {
		$clean['cta_url'] = esc_url_raw( $data['cta_url'] );
	}

	$clean['offers'] = verdix_sanitize_offers( isset( $data['offers'] ) ? $data['offers'] : array() );

	$clean['disclosure'] = '';
	if ( isset( $data['disclosure'] ) && isset( verdix_get_disclosure_types()[ $data['disclosure'] ] ) ) {
		$clean['disclosure'] = $data['disclosure'];
	}

	$clean['disclosure_text'] = '';
	if ( isset( $data['disclosure_text'] ) ) {
		$clean['disclosure_text'] = sanitize_textarea_field( $data['disclosure_text'] );
	}

	$clean['last_tested'] = verdix_sanitize_last_tested( isset( $data['last_tested'] ) ? $data['last_tested'] : '' );

	$clean['author_name'] = '';
	if ( isset( $data['author_name'] ) ) {
		$clean['author_name'] = sanitize_text_field( $data['author_name'] );
	}

	return apply_filters( 'verdix_sanitize_review_data', $clean, $data );
}

/**
 * Get the review data for a post.
 *
 * @param int $post_id Post ID.
 * @return array|null Review data array or null if no review.
 */
function verdix_get_review( $post_id ) {
	$raw = get_post_meta( $post_id, '_verdix_review', true );

	if ( empty( $raw ) ) {
		return null;
	}

	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) ) {
		return null;
	}

	return $data;
}

/**
 * Save review data for a post.
 *
 * @param int   $post_id Post ID.
 * @param array $data    Review data array.
 * @return bool True on success.
 */
function verdix_save_review( $post_id, $data ) {
	$clean  = verdix_sanitize_data( $data );
	$result = update_post_meta( $post_id, '_verdix_review', wp_json_encode( $clean ) );

	// Companion meta in 0-5 scale so the admin Rating column can sort numerically.
	$rating_type = ! empty( $clean['rating_type'] ) ? $clean['rating_type'] : 'star';
	$rating_sort = verdix_normalize_to_star_scale( $clean['rating'], $rating_type );
	update_post_meta( $post_id, '_verdix_rating_sort', $rating_sort );

	return $result;
}

/**
 * Merge partial review fields into the saved review and save through the sanitizer.
 *
 * @param int   $post_id Post ID.
 * @param array $partial Review fields to change.
 * @return array Saved review data.
 */
function verdix_update_review( $post_id, $partial ) {
	$existing = verdix_get_review( $post_id );
	$merged   = is_array( $existing ) ? array_replace( $existing, $partial ) : $partial;

	verdix_save_review( $post_id, $merged );

	return verdix_get_review( $post_id );
}

/**
 * Get default review values from settings.
 *
 * @return array Default values.
 */
function verdix_get_defaults() {
	$options = get_option( 'verdix_settings', array() );

	return array(
		'rating'       => 0,
		'rating_type'  => 'star',
		'style'        => '',
		'position'     => '',
		'heading'      => '',
		'summary'      => '',
		'pros'         => array(),
		'cons'         => array(),
		'schema_type'  => isset( $options['default_schema_type'] ) ? $options['default_schema_type'] : 'Product',
		'product_name' => '',
		'brand'        => '',
		'sku'          => '',
		'gtin'         => '',
		'mpn'          => '',
		'price'        => '',
		'currency'     => 'USD',
		'cta_text'     => '',
		'cta_url'      => '',
		'offers'          => array(),
		'disclosure'      => '',
		'disclosure_text' => '',
		'last_tested'     => '',
		'author_name'  => isset( $options['default_author_name'] ) ? $options['default_author_name'] : '',
	);
}

/**
 * Normalize a rating to the 0-5 star scale for schema output.
 *
 * Reads the registered max from the rating type registry, so any rating type
 * registered (now or via a future add-on) is normalized correctly.
 *
 * @param float  $rating      The rating value.
 * @param string $rating_type The rating type slug.
 * @return float Rating on 0-5 scale.
 */
function verdix_normalize_to_star_scale( $rating, $rating_type ) {
	$types = verdix_get_rating_types();
	$max   = isset( $types[ $rating_type ]['max'] ) ? (float) $types[ $rating_type ]['max'] : 5;
	if ( $max <= 0 ) {
		$max = 5;
	}

	$value = floatval( $rating ) * 5 / $max;
	$value = max( 0, min( 5, $value ) );
	$value = round( $value * 2 ) / 2;

	return $value;
}
