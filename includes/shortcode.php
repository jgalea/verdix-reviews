<?php
defined( 'ABSPATH' ) || exit;

/**
 * Register the [verdix] shortcode.
 */
function verdix_register_shortcode() {
	add_shortcode( 'verdix', 'verdix_shortcode_handler' );
}
add_action( 'init', 'verdix_register_shortcode' );

/**
 * Handle the [verdix] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string Rendered review box HTML or empty string.
 */
function verdix_shortcode_handler( $atts ) {
	$atts = shortcode_atts(
		array(
			'id' => 0,
		),
		$atts,
		'verdix'
	);

	$post_id = absint( $atts['id'] );

	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	if ( ! $post_id ) {
		return '';
	}

	$review = verdix_get_review( $post_id );
	if ( ! $review || empty( $review['rating'] ) ) {
		return '';
	}

	// Enqueue frontend styles when shortcode is used.
	wp_enqueue_style(
		'verdix-frontend',
		VERDIX_URL . 'assets/css/review-box.css',
		array(),
		VERDIX_VERSION
	);

	return verdix_render_box( $review, $post_id );
}
