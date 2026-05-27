<?php
defined( 'ABSPATH' ) || exit;

/**
 * Render the review box HTML.
 *
 * @param array $review  Review data array.
 * @param int   $post_id Post ID (for CSS class uniqueness).
 * @return string Rendered HTML.
 */
function verdix_render_box( $review, $post_id = 0 ) {
	$options = get_option( 'verdix_settings', array() );

	$accent_color = ! empty( $options['accent_color'] ) ? $options['accent_color'] : '#1e73be';
	$bg_color     = ! empty( $options['bg_color'] ) ? $options['bg_color'] : '#fff';
	$border_color = ! empty( $options['border_color'] ) ? $options['border_color'] : '#e7e7e7';

	$accent_light = verdix_lighten_color( $accent_color, 0.4 );

	$inline_style = sprintf(
		'--sb-accent: %s; --sb-accent-light: %s; --sb-bg: %s; --sb-border: %s;',
		esc_attr( $accent_color ),
		esc_attr( $accent_light ),
		esc_attr( $bg_color ),
		esc_attr( $border_color )
	);

	$global_style = isset( $options['default_style'] ) ? $options['default_style'] : 'default';
	$box_style    = ! empty( $review['style'] ) ? $review['style'] : $global_style;

	$valid_styles = array_keys( verdix_get_styles() );
	if ( ! in_array( $box_style, $valid_styles, true ) ) {
		$box_style = 'default';
	}

	$rating_type = ! empty( $review['rating_type'] ) ? $review['rating_type'] : 'star';
	$valid_types = array_keys( verdix_get_rating_types() );
	if ( ! in_array( $rating_type, $valid_types, true ) ) {
		$rating_type = 'star';
	}

	$product_name = ! empty( $review['product_name'] ) ? $review['product_name'] : get_the_title( $post_id );

	ob_start();
	?>
	<div class="verdix-box verdix-style-<?php echo esc_attr( $box_style ); ?>" style="<?php echo esc_attr( $inline_style ); ?>" data-post-id="<?php echo esc_attr( $post_id ); ?>">
		<div class="verdix-box__header">
			<div class="verdix-box__header-left">
				<?php if ( ! empty( $review['heading'] ) ) : ?>
					<span class="verdix-box__heading"><?php echo esc_html( $review['heading'] ); ?></span>
				<?php endif; ?>
				<span class="verdix-box__product-name"><?php echo esc_html( $product_name ); ?></span>
			</div>
			<?php do_action( 'verdix_render_after_heading', $review, $post_id ); ?>
			<?php echo verdix_render_rating_display( $review['rating'], $rating_type ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>

		<?php do_action( 'verdix_render_after_rating', $review, $post_id ); ?>

		<?php if ( ! empty( $review['summary'] ) ) : ?>
			<div class="verdix-box__summary">
				<p class="verdix-box__summary-title"><strong><?php esc_html_e( 'Summary', 'verdix-reviews' ); ?></strong></p>
				<p><?php echo esc_html( $review['summary'] ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $review['pros'] ) || ! empty( $review['cons'] ) ) : ?>
			<div class="verdix-box__pros-cons">
				<?php if ( ! empty( $review['pros'] ) ) : ?>
					<div class="verdix-box__pros">
						<p class="verdix-box__list-heading"><strong><?php esc_html_e( 'Pros', 'verdix-reviews' ); ?></strong></p>
						<ul>
							<?php foreach ( $review['pros'] as $pro ) : ?>
								<li><?php echo esc_html( $pro ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $review['cons'] ) ) : ?>
					<div class="verdix-box__cons">
						<p class="verdix-box__list-heading"><strong><?php esc_html_e( 'Cons', 'verdix-reviews' ); ?></strong></p>
						<ul>
							<?php foreach ( $review['cons'] as $con ) : ?>
								<li><?php echo esc_html( $con ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $review['cta_url'] ) && ! empty( $review['cta_text'] ) ) : ?>
			<div class="verdix-box__cta">
				<a href="<?php echo esc_url( $review['cta_url'] ); ?>" class="verdix-box__cta-button" target="_blank" rel="noopener noreferrer nofollow">
					<?php echo esc_html( $review['cta_text'] ); ?>
				</a>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Render the rating display based on type.
 *
 * Looks up the render callback from the registered rating types. Falls back
 * to a plain score box span if the type is not registered or has no callable.
 *
 * @param float  $rating      Rating value.
 * @param string $rating_type Rating type slug.
 * @return string HTML for rating display.
 */
function verdix_render_rating_display( $rating, $rating_type ) {
	$types = verdix_get_rating_types();

	if ( isset( $types[ $rating_type ]['render'] ) && is_callable( $types[ $rating_type ]['render'] ) ) {
		return call_user_func( $types[ $rating_type ]['render'], $rating );
	}

	return '<span class="verdix-box__score-box">' . esc_html( $rating ) . '</span>';
}

/**
 * Render star rating display with score box + overlay stars.
 *
 * @param float $rating Rating 0-5.
 * @return string HTML.
 */
function verdix_render_stars_display( $rating ) {
	$rating     = max( 0, min( 5, (float) $rating ) );
	$percentage = ( $rating / 5 ) * 100;
	$star_svg   = '<svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
	$five_stars = str_repeat( $star_svg, 5 );

	$html = '<span class="verdix-box__score-box">' . esc_html( $rating ) . '</span>';
	/* translators: %s: rating value */
	$aria_label = sprintf( __( 'Rating: %s out of 5', 'verdix-reviews' ), $rating );
	$html      .= '<div class="verdix-box__stars" aria-label="' . esc_attr( $aria_label ) . '">';
	$html      .= '<div class="verdix-box__stars-background">' . $five_stars . '</div>';
	$html      .= '<div class="verdix-box__stars-foreground" style="width:' . esc_attr( $percentage ) . '%;">' . $five_stars . '</div>';
	$html      .= '</div>';

	return $html;
}

/**
 * Lighten a hex color by mixing it with white.
 *
 * @param string $hex    Hex color.
 * @param float  $amount Amount to lighten (0 = no change, 1 = white).
 * @return string Lightened hex color.
 */
function verdix_lighten_color( $hex, $amount = 0.4 ) {
	$hex = ltrim( $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );

	$r = round( $r + ( 255 - $r ) * $amount );
	$g = round( $g + ( 255 - $g ) * $amount );
	$b = round( $b + ( 255 - $b ) * $amount );

	return sprintf( '#%02x%02x%02x', $r, $g, $b );
}

/**
 * Enqueue frontend styles on singular pages that have review data.
 */
function verdix_enqueue_frontend_styles() {
	if ( ! is_singular() ) {
		return;
	}

	$post_id = get_the_ID();
	$review  = verdix_get_review( $post_id );

	if ( ! $review || empty( $review['rating'] ) ) {
		return;
	}

	wp_enqueue_style(
		'verdix-frontend',
		VERDIX_URL . 'assets/css/review-box.css',
		array(),
		VERDIX_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'verdix_enqueue_frontend_styles' );

/**
 * Auto-insert review box into post content based on position setting.
 *
 * @param string $content Post content.
 * @return string Modified content.
 */
function verdix_auto_insert_content( $content ) {
	if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return $content;
	}

	$review = verdix_get_review( $post_id );
	if ( ! $review || empty( $review['rating'] ) ) {
		return $content;
	}

	$options         = get_option( 'verdix_settings', array() );
	$global_position = isset( $options['default_position'] ) ? $options['default_position'] : 'manual';
	$position        = ! empty( $review['position'] ) ? $review['position'] : $global_position;

	if ( 'manual' === $position ) {
		return $content;
	}

	$box = verdix_render_box( $review, $post_id );

	switch ( $position ) {
		case 'top':
			return $box . $content;
		case 'bottom':
			$related_pos = strpos( $content, 'searchwp-related' );
			if ( false === $related_pos ) {
				$related_pos = strpos( $content, 'related-posts' );
			}
			if ( false !== $related_pos ) {
				$insert_pos = strrpos( substr( $content, 0, $related_pos ), '<div' );
				if ( false !== $insert_pos ) {
					return substr( $content, 0, $insert_pos ) . $box . substr( $content, $insert_pos );
				}
			}
			return $content . $box;
		case 'both':
			return $box . $content . $box;
		default:
			return $content;
	}
}
add_filter( 'the_content', 'verdix_auto_insert_content', 20 );
