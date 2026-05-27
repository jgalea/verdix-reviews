<?php
defined( 'ABSPATH' ) || exit;

/**
 * Register the review-box block.
 */
function verdix_register_block() {
	$block_dir = VERDIX_DIR . 'blocks/review-box';

	if ( ! file_exists( $block_dir . '/block.json' ) ) {
		return;
	}

	// Register editor script with WP dependencies (plain JS, no build step).
	wp_register_script(
		'verdix-editor',
		VERDIX_URL . 'blocks/review-box/editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-core-data' ),
		VERDIX_VERSION,
		true
	);

	register_block_type(
		$block_dir,
		array(
			'render_callback' => 'verdix_render_block',
			'editor_script'   => 'verdix-editor',
		)
	);
}
add_action( 'init', 'verdix_register_block' );

/**
 * Render callback for the review-box block.
 *
 * This is a dynamic block — save.js returns null, and PHP renders on the frontend.
 * The block stores its data in post meta (_verdix_review), not in block attributes.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block inner content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered HTML.
 */
function verdix_render_block( $attributes, $content, $block ) {
	// Get the post ID from block context or global.
	$post_id = isset( $block->context['postId'] ) ? $block->context['postId'] : get_the_ID();

	if ( ! $post_id ) {
		return '';
	}

	$review = verdix_get_review( $post_id );
	if ( ! $review || empty( $review['rating'] ) ) {
		return '';
	}

	return verdix_render_box( $review, $post_id );
}

/**
 * Enqueue editor assets that aren't handled by block.json.
 */
function verdix_editor_assets() {
	$defaults = verdix_get_defaults();
	$config   = verdix_get_editor_config();
	wp_add_inline_script(
		'verdix-editor',
		'window.verdixDefaults = ' . wp_json_encode( $defaults ) . ';'
		. 'window.verdixEditorConfig = ' . wp_json_encode( $config ) . ';',
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', 'verdix_editor_assets' );
