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

	$post_types = verdix_get_post_types();

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

function verdix_meta_box_offer_row( $index, $offer, $katina_links ) {
	$name       = 'verdix_review_offers[' . $index . ']';
	$currencies = array_unique( array( 'USD', 'EUR', 'GBP', $offer['currency'] ) );
	?>
	<div class="verdix-offer">
		<input type="text" name="<?php echo esc_attr( $name ); ?>[merchant]" value="<?php echo esc_attr( $offer['merchant'] ); ?>" placeholder="<?php esc_attr_e( 'Merchant', 'verdix-reviews' ); ?>" aria-label="<?php esc_attr_e( 'Merchant', 'verdix-reviews' ); ?>">
		<input type="text" name="<?php echo esc_attr( $name ); ?>[price]" value="<?php echo esc_attr( $offer['price'] ); ?>" class="verdix-offer__price" placeholder="<?php esc_attr_e( 'Price', 'verdix-reviews' ); ?>" aria-label="<?php esc_attr_e( 'Price', 'verdix-reviews' ); ?>">
		<select name="<?php echo esc_attr( $name ); ?>[currency]" aria-label="<?php esc_attr_e( 'Currency', 'verdix-reviews' ); ?>">
			<?php foreach ( $currencies as $code ) : ?>
				<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $offer['currency'], $code ); ?>><?php echo esc_html( $code ); ?></option>
			<?php endforeach; ?>
		</select>
		<input type="text" name="<?php echo esc_attr( $name ); ?>[cta_text]" value="<?php echo esc_attr( $offer['cta_text'] ); ?>" placeholder="<?php esc_attr_e( 'Button text', 'verdix-reviews' ); ?>" aria-label="<?php esc_attr_e( 'Button text', 'verdix-reviews' ); ?>">
		<input type="url" name="<?php echo esc_attr( $name ); ?>[url]" value="<?php echo esc_url( $offer['url'] ); ?>" class="verdix-offer__url" placeholder="https://" aria-label="<?php esc_attr_e( 'Offer URL', 'verdix-reviews' ); ?>">
		<select name="<?php echo esc_attr( $name ); ?>[availability]" aria-label="<?php esc_attr_e( 'Availability', 'verdix-reviews' ); ?>">
			<option value=""><?php esc_html_e( 'Availability: in stock', 'verdix-reviews' ); ?></option>
			<?php foreach ( verdix_get_availability_options() as $availability_value => $availability_label ) : ?>
				<?php if ( 'InStock' === $availability_value ) { continue; } ?>
				<option value="<?php echo esc_attr( $availability_value ); ?>" <?php selected( $offer['availability'], $availability_value ); ?>><?php echo esc_html( $availability_label ); ?></option>
			<?php endforeach; ?>
		</select>
		<input type="date" name="<?php echo esc_attr( $name ); ?>[price_valid_until]" value="<?php echo esc_attr( $offer['price_valid_until'] ); ?>" aria-label="<?php esc_attr_e( 'Price valid until', 'verdix-reviews' ); ?>" title="<?php esc_attr_e( 'Price valid until', 'verdix-reviews' ); ?>">
		<?php if ( $katina_links ) : ?>
			<select name="<?php echo esc_attr( $name ); ?>[katina_link]" class="verdix-offer__katina" aria-label="<?php esc_attr_e( 'Katina link', 'verdix-reviews' ); ?>">
				<option value="0"><?php esc_html_e( 'No Katina link', 'verdix-reviews' ); ?></option>
				<?php foreach ( $katina_links as $link ) : ?>
					<option value="<?php echo esc_attr( $link->ID ); ?>" <?php selected( (int) $offer['katina_link'], $link->ID ); ?>><?php echo esc_html( $link->post_title ); ?></option>
				<?php endforeach; ?>
			</select>
		<?php endif; ?>
		<button type="button" class="button verdix-offer__remove" aria-label="<?php esc_attr_e( 'Remove', 'verdix-reviews' ); ?>">&times;</button>
	</div>
	<?php
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
		'brand'        => isset( $_POST['verdix_review_brand'] ) ? sanitize_text_field( wp_unslash( $_POST['verdix_review_brand'] ) ) : '',
		'sku'          => isset( $_POST['verdix_review_sku'] ) ? sanitize_text_field( wp_unslash( $_POST['verdix_review_sku'] ) ) : '',
		'gtin'         => isset( $_POST['verdix_review_gtin'] ) ? sanitize_text_field( wp_unslash( $_POST['verdix_review_gtin'] ) ) : '',
		'mpn'          => isset( $_POST['verdix_review_mpn'] ) ? sanitize_text_field( wp_unslash( $_POST['verdix_review_mpn'] ) ) : '',
		'price'        => isset( $_POST['verdix_review_price'] ) ? sanitize_text_field( wp_unslash( $_POST['verdix_review_price'] ) ) : '',
		'currency'     => isset( $_POST['verdix_review_currency'] ) ? sanitize_text_field( wp_unslash( $_POST['verdix_review_currency'] ) ) : 'USD',
		'cta_text'     => isset( $_POST['verdix_review_cta_text'] ) ? sanitize_text_field( wp_unslash( $_POST['verdix_review_cta_text'] ) ) : '',
		'cta_url'      => isset( $_POST['verdix_review_cta_url'] ) ? esc_url_raw( wp_unslash( $_POST['verdix_review_cta_url'] ) ) : '',
		'disclosure'      => isset( $_POST['verdix_review_disclosure'] ) ? sanitize_text_field( wp_unslash( $_POST['verdix_review_disclosure'] ) ) : '',
		'disclosure_text' => isset( $_POST['verdix_review_disclosure_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['verdix_review_disclosure_text'] ) ) : '',
		'last_tested'     => isset( $_POST['verdix_review_last_tested'] ) ? sanitize_text_field( wp_unslash( $_POST['verdix_review_last_tested'] ) ) : '',
		'author_name'  => isset( $_POST['verdix_review_author_name'] ) ? sanitize_text_field( wp_unslash( $_POST['verdix_review_author_name'] ) ) : '',
	);

	// Per-type schema fields (Book author, Recipe ingredients, etc.). Deep
	// sanitization happens in verdix_sanitize_review() against the
	// verdix_get_type_fields() registry — here we just capture the raw array.
	$data['type_fields'] = array();
	if ( isset( $_POST['verdix_review_type_fields'] ) && is_array( $_POST['verdix_review_type_fields'] ) ) {
		$data['type_fields'] = wp_unslash( $_POST['verdix_review_type_fields'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized downstream.
	}

	$data['offers'] = array();
	if ( isset( $_POST['verdix_review_offers'] ) && is_array( $_POST['verdix_review_offers'] ) ) {
		$data['offers'] = wp_unslash( $_POST['verdix_review_offers'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized downstream in verdix_sanitize_offers().
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
