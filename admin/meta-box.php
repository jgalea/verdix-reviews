<?php
defined( 'ABSPATH' ) || exit;

/**
 * Register the meta box on post types that support reviews.
 */
function verdix_add_meta_box() {
	// Don't add meta box if block editor is active — the block handles it.
	if ( verdix_is_block_editor() ) {
		return;
	}

	$post_types = apply_filters( 'verdix_post_types', array( 'post', 'page' ) );

	foreach ( $post_types as $post_type ) {
		add_meta_box(
			'verdix_meta_box',
			__( 'Verdix Reviews', 'verdix-reviews' ),
			'verdix_meta_box_render',
			$post_type,
			'normal',
			'high'
		);
	}
}
add_action( 'add_meta_boxes', 'verdix_add_meta_box' );

/**
 * Check if the block editor is being used for the current screen.
 *
 * @return bool
 */
function verdix_is_block_editor() {
	$screen = get_current_screen();
	if ( $screen && method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor() ) {
		return true;
	}
	return false;
}

/**
 * Render the meta box contents.
 *
 * @param WP_Post $post Current post object.
 */
function verdix_meta_box_render( $post ) {
	wp_nonce_field( 'verdix_meta_box', 'verdix_meta_box_nonce' );

	$review  = verdix_get_review( $post->ID );
	$defaults = verdix_get_defaults();

	// Merge with defaults.
	$data = wp_parse_args( $review ? $review : array(), $defaults );

	include VERDIX_DIR . 'admin/views/meta-box.php';
}

/**
 * Save meta box data.
 *
 * @param int $post_id Post ID.
 */
function verdix_save_meta_box( $post_id ) {
	// Verify nonce.
	if ( ! isset( $_POST['verdix_meta_box_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['verdix_meta_box_nonce'] ) ), 'verdix_meta_box' ) ) {
		return;
	}

	// Check autosave.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check permissions.
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Only process if our fields are present.
	if ( ! isset( $_POST['verdix_review_rating'] ) ) {
		return;
	}

	$data = array(
		'rating'       => isset( $_POST['verdix_review_rating'] ) ? sanitize_text_field( wp_unslash( $_POST['verdix_review_rating'] ) ) : 0,
		'rating_type'  => isset( $_POST['verdix_review_rating_type'] ) ? sanitize_text_field( wp_unslash( $_POST['verdix_review_rating_type'] ) ) : 'star',
		'style'        => isset( $_POST['verdix_review_style'] ) ? sanitize_text_field( wp_unslash( $_POST['verdix_review_style'] ) ) : '',
		'position'     => isset( $_POST['verdix_review_position'] ) ? sanitize_text_field( wp_unslash( $_POST['verdix_review_position'] ) ) : '',
		'heading'      => isset( $_POST['verdix_review_heading'] ) ? sanitize_text_field( wp_unslash( $_POST['verdix_review_heading'] ) ) : '',
		'summary'      => isset( $_POST['verdix_review_summary'] ) ? sanitize_textarea_field( wp_unslash( $_POST['verdix_review_summary'] ) ) : '',
		'schema_type'  => isset( $_POST['verdix_review_schema_type'] ) ? sanitize_text_field( wp_unslash( $_POST['verdix_review_schema_type'] ) ) : 'Product',
		'product_name' => isset( $_POST['verdix_review_product_name'] ) ? sanitize_text_field( wp_unslash( $_POST['verdix_review_product_name'] ) ) : '',
		'price'        => isset( $_POST['verdix_review_price'] ) ? sanitize_text_field( wp_unslash( $_POST['verdix_review_price'] ) ) : '',
		'currency'     => isset( $_POST['verdix_review_currency'] ) ? sanitize_text_field( wp_unslash( $_POST['verdix_review_currency'] ) ) : 'USD',
		'cta_text'     => isset( $_POST['verdix_review_cta_text'] ) ? sanitize_text_field( wp_unslash( $_POST['verdix_review_cta_text'] ) ) : '',
		'cta_url'      => isset( $_POST['verdix_review_cta_url'] ) ? esc_url_raw( wp_unslash( $_POST['verdix_review_cta_url'] ) ) : '',
		'author_name'  => isset( $_POST['verdix_review_author_name'] ) ? sanitize_text_field( wp_unslash( $_POST['verdix_review_author_name'] ) ) : '',
	);

	// Per-type schema fields (Book author, Recipe ingredients, etc.). Deep
	// sanitization happens in verdix_sanitize_review() against the
	// verdix_get_type_fields() registry — here we just capture the raw array.
	$data['type_fields'] = array();
	if ( isset( $_POST['verdix_review_type_fields'] ) && is_array( $_POST['verdix_review_type_fields'] ) ) {
		$data['type_fields'] = wp_unslash( $_POST['verdix_review_type_fields'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized downstream.
	}

	// Parse pros from repeater input array.
	$data['pros']    = array();
	$raw_pros        = isset( $_POST['verdix_review_pros'] ) && is_array( $_POST['verdix_review_pros'] )
		? array_map( 'sanitize_text_field', wp_unslash( $_POST['verdix_review_pros'] ) )
		: array();
	foreach ( $raw_pros as $pro_item ) {
		$trimmed = trim( $pro_item );
		if ( '' !== $trimmed ) {
			$data['pros'][] = $trimmed;
		}
	}

	// Parse cons from repeater input array.
	$data['cons'] = array();
	$raw_cons     = isset( $_POST['verdix_review_cons'] ) && is_array( $_POST['verdix_review_cons'] )
		? array_map( 'sanitize_text_field', wp_unslash( $_POST['verdix_review_cons'] ) )
		: array();
	foreach ( $raw_cons as $con_item ) {
		$trimmed = trim( $con_item );
		if ( '' !== $trimmed ) {
			$data['cons'][] = $trimmed;
		}
	}

	$data = apply_filters( 'verdix_meta_box_save_data', $data, $post_id );

	// Only save if there's actually review data (rating > 0 or something filled in).
	$has_data = ( floatval( $data['rating'] ) > 0 )
		|| ! empty( $data['summary'] )
		|| ! empty( $data['pros'] )
		|| ! empty( $data['cons'] );

	if ( $has_data ) {
		verdix_save_review( $post_id, $data );
	} else {
		// Clear review data if everything is empty.
		delete_post_meta( $post_id, '_verdix_review' );
	}
}
add_action( 'save_post', 'verdix_save_meta_box' );

/**
 * Enqueue admin styles for the meta box.
 *
 * @param string $hook_suffix Current admin page.
 */
function verdix_meta_box_enqueue( $hook_suffix ) {
	if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
		return;
	}

	// Only on classic editor.
	if ( verdix_is_block_editor() ) {
		return;
	}

	wp_enqueue_style(
		'verdix-meta-box',
		VERDIX_URL . 'assets/css/meta-box.css',
		array(),
		VERDIX_VERSION
	);

	wp_enqueue_script(
		'verdix-meta-box',
		VERDIX_URL . 'assets/js/meta-box.js',
		array(),
		VERDIX_VERSION,
		true
	);
}
add_action( 'admin_enqueue_scripts', 'verdix_meta_box_enqueue' );
