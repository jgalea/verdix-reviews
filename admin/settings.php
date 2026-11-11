<?php
defined( 'ABSPATH' ) || exit;

// =========================================================================
// Top-level admin menu
// =========================================================================

function verdix_admin_menu() {
	// Main menu page — shows the dashboard/overview.
	add_menu_page(
		__( 'Verdix Reviews', 'verdix-reviews' ),
		__( 'Verdix Reviews', 'verdix-reviews' ),
		'manage_options',
		'verdix-reviews',
		'verdix_dashboard_page',
		'dashicons-star-filled',
		30
	);

	// Settings submenu.
	add_submenu_page(
		'verdix-reviews',
		__( 'Settings', 'verdix-reviews' ),
		__( 'Settings', 'verdix-reviews' ),
		'manage_options',
		'verdix-settings',
		'verdix_settings_page'
	);

	// Migration submenu.
	add_submenu_page(
		'verdix-reviews',
		__( 'Migration', 'verdix-reviews' ),
		__( 'Migration', 'verdix-reviews' ),
		'manage_options',
		'verdix-migration',
		'verdix_migration_page'
	);

	// Rename the auto-created first submenu from "Verdix Reviews" to "Dashboard".
	global $submenu;
	if ( isset( $submenu['verdix-reviews'] ) ) {
		$submenu['verdix-reviews'][0][0] = __( 'Dashboard', 'verdix-reviews' );
	}
}
add_action( 'admin_menu', 'verdix_admin_menu' );

// =========================================================================
// Settings registration
// =========================================================================

function verdix_register_settings() {
	register_setting(
		'verdix_settings_group',
		'verdix_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'verdix_sanitize_settings',
			'default'           => array(),
		)
	);

	// General section.
	add_settings_section(
		'verdix_general',
		'',
		'__return_null',
		'verdix-settings'
	);

	add_settings_field(
		'default_schema_type',
		__( 'Default schema type', 'verdix-reviews' ),
		'verdix_field_schema_type',
		'verdix-settings',
		'verdix_general'
	);

	add_settings_field(
		'default_author_name',
		__( 'Default author name', 'verdix-reviews' ),
		'verdix_field_author_name',
		'verdix-settings',
		'verdix_general'
	);

	add_settings_field(
		'default_disclosure',
		__( 'Default disclosure', 'verdix-reviews' ),
		'verdix_field_disclosure',
		'verdix-settings',
		'verdix_general'
	);

	add_settings_field(
		'schema_output',
		__( 'Schema output', 'verdix-reviews' ),
		'verdix_field_schema_output',
		'verdix-settings',
		'verdix_general'
	);

	add_settings_field(
		'default_position',
		__( 'Default review position', 'verdix-reviews' ),
		'verdix_field_position',
		'verdix-settings',
		'verdix_general'
	);

	// Appearance section.
	add_settings_section(
		'verdix_appearance',
		'',
		'__return_null',
		'verdix-settings-appearance'
	);

	add_settings_field(
		'default_style',
		__( 'Review box style', 'verdix-reviews' ),
		'verdix_field_default_style',
		'verdix-settings-appearance',
		'verdix_appearance'
	);

	add_settings_field(
		'accent_color',
		__( 'Star / accent color', 'verdix-reviews' ),
		'verdix_field_accent_color',
		'verdix-settings-appearance',
		'verdix_appearance'
	);

	add_settings_field(
		'bg_color',
		__( 'Background color', 'verdix-reviews' ),
		'verdix_field_bg_color',
		'verdix-settings-appearance',
		'verdix_appearance'
	);

	add_settings_field(
		'border_color',
		__( 'Border color', 'verdix-reviews' ),
		'verdix_field_border_color',
		'verdix-settings-appearance',
		'verdix_appearance'
	);
}
add_action( 'admin_init', 'verdix_register_settings' );

// =========================================================================
// Sanitize
// =========================================================================

function verdix_sanitize_settings( $input ) {
	$input  = is_array( $input ) ? $input : array();
	$stored = get_option( 'verdix_settings', array() );
	$output = is_array( $stored ) ? $stored : array();
	$tab    = isset( $input['_tab'] ) ? sanitize_key( $input['_tab'] ) : '';

	if ( 'general' === $tab || isset( $input['default_schema_type'] ) ) {
		$valid_types                   = array_keys( verdix_get_schema_types() );
		$output['default_schema_type'] = isset( $input['default_schema_type'] ) && in_array( $input['default_schema_type'], $valid_types, true )
			? $input['default_schema_type']
			: 'Product';

		$output['default_author_name'] = isset( $input['default_author_name'] ) ? sanitize_text_field( $input['default_author_name'] ) : '';

		$output['default_disclosure'] = isset( $input['default_disclosure'] ) && isset( verdix_get_disclosure_types()[ $input['default_disclosure'] ] )
			? $input['default_disclosure']
			: 'none';

		$output['default_disclosure_text'] = isset( $input['default_disclosure_text'] ) ? sanitize_textarea_field( $input['default_disclosure_text'] ) : '';

		$output['schema_output'] = isset( $input['schema_output'] ) && isset( verdix_get_schema_output_modes()[ $input['schema_output'] ] )
			? $input['schema_output']
			: 'auto';

		$valid_positions            = array( 'bottom', 'top', 'both', 'manual' );
		$output['default_position'] = isset( $input['default_position'] ) && in_array( $input['default_position'], $valid_positions, true )
			? $input['default_position']
			: 'manual';

		$output['post_types'] = isset( $input['post_types'] ) && is_array( $input['post_types'] )
			? array_values( array_intersect( array_map( 'sanitize_key', $input['post_types'] ), get_post_types( array( 'public' => true ) ) ) )
			: array();
	}

	if ( 'appearance' === $tab || isset( $input['default_style'] ) ) {
		$valid_styles            = array_keys( verdix_get_styles() );
		$output['default_style'] = isset( $input['default_style'] ) && in_array( $input['default_style'], $valid_styles, true )
			? $input['default_style']
			: 'default';

		$output['accent_color'] = ( isset( $input['accent_color'] ) ? sanitize_hex_color( $input['accent_color'] ) : '' ) ?: '#1e73be';
		$output['bg_color']     = ( isset( $input['bg_color'] ) ? sanitize_hex_color( $input['bg_color'] ) : '' ) ?: '#fff';
		$output['border_color'] = ( isset( $input['border_color'] ) ? sanitize_hex_color( $input['border_color'] ) : '' ) ?: '#e7e7e7';
	}

	/**
	 * Filter the settings after the core fields are sanitized.
	 *
	 * @param array  $output Settings about to be saved, already merged with the stored ones.
	 * @param array  $input  Submitted settings.
	 * @param string $tab    Settings tab that was submitted, or an empty string.
	 */
	return apply_filters( 'verdix_sanitize_settings', $output, $input, $tab );
}

// =========================================================================
// Page render callbacks
// =========================================================================

function verdix_dashboard_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	include VERDIX_DIR . 'admin/views/dashboard-page.php';
}

function verdix_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	include VERDIX_DIR . 'admin/views/settings-page.php';
}

// =========================================================================
// Field callbacks
// =========================================================================

function verdix_field_schema_type() {
	$options = get_option( 'verdix_settings', array() );
	$current = isset( $options['default_schema_type'] ) ? $options['default_schema_type'] : 'Product';
	?>
	<select name="verdix_settings[default_schema_type]" id="verdix_default_schema_type">
		<?php foreach ( verdix_get_schema_types() as $type_value => $type_label ) : ?>
			<option value="<?php echo esc_attr( $type_value ); ?>" <?php selected( $current, $type_value ); ?>><?php echo esc_html( $type_label ); ?></option>
		<?php endforeach; ?>
	</select>
	<p class="description"><?php esc_html_e( 'Default schema.org type for new reviews. Can be overridden per post.', 'verdix-reviews' ); ?></p>
	<?php
}

function verdix_field_author_name() {
	$options = get_option( 'verdix_settings', array() );
	$value   = isset( $options['default_author_name'] ) ? $options['default_author_name'] : '';
	?>
	<input type="text" name="verdix_settings[default_author_name]" id="verdix_default_author_name"
		value="<?php echo esc_attr( $value ); ?>" class="regular-text"
		placeholder="<?php esc_attr_e( 'e.g., John Smith', 'verdix-reviews' ); ?>">
	<p class="description"><?php esc_html_e( 'Used in JSON-LD schema as review author. Falls back to post author if empty.', 'verdix-reviews' ); ?></p>
	<?php
}

function verdix_field_disclosure() {
	$options = get_option( 'verdix_settings', array() );
	$current = isset( $options['default_disclosure'] ) ? $options['default_disclosure'] : 'none';
	$text    = isset( $options['default_disclosure_text'] ) ? $options['default_disclosure_text'] : '';
	?>
	<select name="verdix_settings[default_disclosure]" id="verdix_default_disclosure">
		<?php foreach ( verdix_get_disclosure_types() as $type_value => $type_label ) : ?>
			<option value="<?php echo esc_attr( $type_value ); ?>" <?php selected( $current, $type_value ); ?>><?php echo esc_html( $type_label ); ?></option>
		<?php endforeach; ?>
	</select>
	<p>
		<textarea name="verdix_settings[default_disclosure_text]" id="verdix_default_disclosure_text" rows="2" class="large-text"
			placeholder="<?php esc_attr_e( 'Text for the Custom option', 'verdix-reviews' ); ?>"><?php echo esc_textarea( $text ); ?></textarea>
	</p>
	<p class="description"><?php esc_html_e( 'Shown in every review box that does not set its own disclosure. Google requires disclosing incentivized reviews.', 'verdix-reviews' ); ?></p>
	<?php
}

function verdix_field_schema_output() {
	$options = get_option( 'verdix_settings', array() );
	$current = isset( $options['schema_output'] ) ? $options['schema_output'] : 'auto';
	?>
	<select name="verdix_settings[schema_output]" id="verdix_schema_output">
		<?php foreach ( verdix_get_schema_output_modes() as $value => $label ) : ?>
			<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>
	</select>
	<p class="description"><?php esc_html_e( 'Yoast SEO, Rank Math and All in One SEO print their own schema graph. Auto adds the review to that graph instead of printing a second script, which avoids duplicate review markup.', 'verdix-reviews' ); ?></p>
	<?php
}

function verdix_field_position() {
	$options = get_option( 'verdix_settings', array() );
	$current = isset( $options['default_position'] ) ? $options['default_position'] : 'manual';
	?>
	<select name="verdix_settings[default_position]" id="verdix_default_position">
		<option value="manual" <?php selected( $current, 'manual' ); ?>><?php esc_html_e( 'Manual (block/shortcode only)', 'verdix-reviews' ); ?></option>
		<option value="bottom" <?php selected( $current, 'bottom' ); ?>><?php esc_html_e( 'After content', 'verdix-reviews' ); ?></option>
		<option value="top" <?php selected( $current, 'top' ); ?>><?php esc_html_e( 'Before content', 'verdix-reviews' ); ?></option>
		<option value="both" <?php selected( $current, 'both' ); ?>><?php esc_html_e( 'Before and after content', 'verdix-reviews' ); ?></option>
	</select>
	<p class="description"><?php esc_html_e( 'Where to auto-insert the review box. Can be overridden per post.', 'verdix-reviews' ); ?></p>
	<?php
}

function verdix_field_accent_color() {
	$options = get_option( 'verdix_settings', array() );
	$value   = isset( $options['accent_color'] ) ? $options['accent_color'] : '#1e73be';
	?>
	<input type="text" name="verdix_settings[accent_color]" id="verdix_accent_color"
		value="<?php echo esc_attr( $value ); ?>" class="verdix-color-picker" data-default-color="#1e73be">
	<?php
}

function verdix_field_bg_color() {
	$options = get_option( 'verdix_settings', array() );
	$value   = isset( $options['bg_color'] ) ? $options['bg_color'] : '#fff';
	?>
	<input type="text" name="verdix_settings[bg_color]" id="verdix_bg_color"
		value="<?php echo esc_attr( $value ); ?>" class="verdix-color-picker" data-default-color="#ffffff">
	<?php
}

function verdix_field_border_color() {
	$options = get_option( 'verdix_settings', array() );
	$value   = isset( $options['border_color'] ) ? $options['border_color'] : '#e7e7e7';
	?>
	<input type="text" name="verdix_settings[border_color]" id="verdix_border_color"
		value="<?php echo esc_attr( $value ); ?>" class="verdix-color-picker" data-default-color="#e7e7e7">
	<?php
}

function verdix_field_default_style() {
	$options = get_option( 'verdix_settings', array() );
	$current = isset( $options['default_style'] ) ? $options['default_style'] : 'default';
	$styles  = verdix_get_styles();
	?>
	<select name="verdix_settings[default_style]" id="verdix_default_style">
		<?php foreach ( $styles as $value => $config ) : ?>
			<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>><?php echo esc_html( $config['label'] ); ?></option>
		<?php endforeach; ?>
	</select>
	<p class="description"><?php esc_html_e( 'Default style for new reviews.', 'verdix-reviews' ); ?></p>
	<?php
}

// =========================================================================
// Appearance preview — shared sample + AJAX refresh
// =========================================================================

/**
 * Sample review used for the appearance preview.
 */
function verdix_get_preview_review() {
	$review = array(
		'rating'       => 4.5,
		'rating_type'  => 'star',
		'heading'      => __( 'Our Verdict', 'verdix-reviews' ),
		'summary'      => __( 'An excellent product that delivers on its promises. Great value for the price.', 'verdix-reviews' ),
		'pros'         => array(
			__( 'Easy to use', 'verdix-reviews' ),
			__( 'Great performance', 'verdix-reviews' ),
			__( 'Good value', 'verdix-reviews' ),
		),
		'cons'         => array(
			__( 'Limited options', 'verdix-reviews' ),
			__( 'Could be cheaper', 'verdix-reviews' ),
		),
		'cta_text'     => __( 'Visit website', 'verdix-reviews' ),
		'cta_url'      => '#',
		'product_name' => __( 'Example product', 'verdix-reviews' ),
		'style'        => '',
		'position'     => '',
		'schema_type'  => 'Product',
		'price'        => '',
		'currency'     => 'USD',
		'author_name'  => '',
	);

	/**
	 * Filter the sample review used on the Appearance preview.
	 *
	 * @param array $review Sample review data.
	 */
	return apply_filters( 'verdix_preview_review', $review );
}

/**
 * AJAX: render the appearance preview with the provided style + colors,
 * without persisting any settings.
 */
function verdix_ajax_render_preview() {
	check_ajax_referer( 'verdix_preview', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
	}

	$style        = isset( $_POST['style'] ) ? sanitize_key( wp_unslash( $_POST['style'] ) ) : 'default';
	$accent_color = isset( $_POST['accent_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['accent_color'] ) ) : '';
	$bg_color     = isset( $_POST['bg_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['bg_color'] ) ) : '';
	$border_color = isset( $_POST['border_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['border_color'] ) ) : '';

	$override = function( $value ) use ( $accent_color, $bg_color, $border_color ) {
		$value = is_array( $value ) ? $value : array();
		if ( $accent_color ) {
			$value['accent_color'] = $accent_color;
		}
		if ( $bg_color ) {
			$value['bg_color'] = $bg_color;
		}
		if ( $border_color ) {
			$value['border_color'] = $border_color;
		}
		return $value;
	};
	add_filter( 'option_verdix_settings', $override );

	$review          = verdix_get_preview_review();
	$review['style'] = $style;

	$html = verdix_render_box( $review, 0 );

	remove_filter( 'option_verdix_settings', $override );

	wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_verdix_render_preview', 'verdix_ajax_render_preview' );

// =========================================================================
// Post list table — Rating column
// =========================================================================

function verdix_add_post_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['verdix_rating'] = __( 'Rating', 'verdix-reviews' );
		}
	}
	return $new;
}

function verdix_render_post_column( $column, $post_id ) {
	if ( 'verdix_rating' !== $column ) {
		return;
	}

	$review = verdix_get_review( $post_id );
	if ( ! $review || empty( $review['rating'] ) ) {
		echo '<span class="verdix-col-empty">&mdash;</span>';
		return;
	}

	$rating       = $review['rating'];
	$rating_type  = ! empty( $review['rating_type'] ) ? $review['rating_type'] : 'star';
	$rating_types = verdix_get_rating_types();
	$type_def     = isset( $rating_types[ $rating_type ] ) ? $rating_types[ $rating_type ] : $rating_types['star'];
	$format       = isset( $type_def['format'] ) ? $type_def['format'] : '%s/5';
	$display      = sprintf( $format, $rating );

	$stars_html = '';
	if ( 'star' === $rating_type ) {
		$full        = floor( $rating );
		$half        = ( $rating - $full ) >= 0.5 ? 1 : 0;
		$stars_html .= str_repeat( '<span class="verdix-col-star verdix-col-star--full"></span>', $full );
		if ( $half ) {
			$stars_html .= '<span class="verdix-col-star verdix-col-star--half"></span>';
		}
		$stars_html .= str_repeat( '<span class="verdix-col-star verdix-col-star--empty"></span>', 5 - $full - $half );
	}

	printf(
		'<span class="verdix-col-rating" title="%s">%s<strong>%s</strong></span>',
		esc_attr( $display ),
		$stars_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static HTML.
		esc_html( $display )
	);
}

function verdix_sortable_columns( $columns ) {
	$columns['verdix_rating'] = 'verdix_rating';
	return $columns;
}

function verdix_column_orderby( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( 'verdix_rating' !== $query->get( 'orderby' ) ) {
		return;
	}

	// Sort by the numeric _verdix_rating_sort companion meta (0-5 normalized).
	// Posts without a review are excluded — this is the expected behavior when the
	// user explicitly asked to sort by rating.
	$query->set( 'meta_key', '_verdix_rating_sort' );
	$query->set( 'orderby', 'meta_value_num' );
}

/**
 * One-shot sync: rebuild _verdix_rating_sort for every review, so reviews saved
 * through the block editor or removed earlier don't leave a stale value behind.
 */
function verdix_maybe_backfill_rating_sort() {
	if ( get_option( 'verdix_rating_sort_synced' ) ) {
		return;
	}
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	global $wpdb;
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$post_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != %s",
			'_verdix_review',
			''
		)
	);

	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id NOT IN ( SELECT post_id FROM ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != %s ) AS reviewed )",
			'_verdix_rating_sort',
			'_verdix_review',
			''
		)
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	foreach ( $post_ids as $pid ) {
		verdix_sync_rating_sort( 0, (int) $pid, '_verdix_review' );
	}

	update_option( 'verdix_rating_sort_synced', 1 );
}
add_action( 'admin_init', 'verdix_maybe_backfill_rating_sort' );

function verdix_register_post_columns() {
	foreach ( verdix_get_post_types() as $post_type ) {
		add_filter( "manage_{$post_type}_posts_columns", 'verdix_add_post_columns' );
		add_action( "manage_{$post_type}_posts_custom_column", 'verdix_render_post_column', 10, 2 );
		add_filter( "manage_edit-{$post_type}_sortable_columns", 'verdix_sortable_columns' );
	}
}
add_action( 'admin_init', 'verdix_register_post_columns' );
add_action( 'pre_get_posts', 'verdix_column_orderby' );

// =========================================================================
// Admin assets
// =========================================================================

function verdix_admin_enqueue( $hook_suffix ) {
	// Admin CSS on all Verdix Reviews pages.
	$verdix_pages = array(
		'toplevel_page_verdix-reviews',
		'verdix-reviews_page_verdix-settings',
		'verdix-reviews_page_verdix-migration',
	);

	if ( in_array( $hook_suffix, $verdix_pages, true ) ) {
		wp_enqueue_style(
			'verdix-admin',
			VERDIX_URL . 'assets/css/admin.css',
			array(),
			VERDIX_VERSION
		);
	}

	// Color picker + frontend preview styles on settings page.
	if ( 'verdix-reviews_page_verdix-settings' === $hook_suffix ) {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style(
			'verdix-frontend',
			VERDIX_URL . 'assets/css/review-box.css',
			array(),
			VERDIX_VERSION
		);
		wp_enqueue_script(
			'verdix-admin-settings',
			VERDIX_URL . 'assets/js/admin-settings.js',
			array( 'wp-color-picker', 'jquery' ),
			VERDIX_VERSION,
			true
		);
		wp_localize_script(
			'verdix-admin-settings',
			'verdixPreview',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'verdix_preview' ),
			)
		);
	}

	// Post list column styles.
	if ( 'edit.php' === $hook_suffix ) {
		wp_enqueue_style(
			'verdix-admin-columns',
			VERDIX_URL . 'assets/css/admin-columns.css',
			array(),
			VERDIX_VERSION
		);
	}
}
add_action( 'admin_enqueue_scripts', 'verdix_admin_enqueue' );

// =========================================================================
// Plugin action links
// =========================================================================

function verdix_plugin_action_links( $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		admin_url( 'admin.php?page=verdix-settings' ),
		__( 'Settings', 'verdix-reviews' )
	);
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . VERDIX_BASENAME, 'verdix_plugin_action_links' );
