<?php
defined( 'ABSPATH' ) || exit;

function verdix_render_review_list( $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'type'        => 'top-rated',
			'count'       => 5,
			'schema_type' => '',
			'category'    => '',
			'image'       => true,
		)
	);

	$count = max( 1, min( 20, absint( $args['count'] ) ) );
	$query = array(
		'post_type'              => verdix_get_post_types(),
		'post_status'            => 'publish',
		'has_password'           => false,
		'posts_per_page'         => $count,
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
		'meta_key'               => '_verdix_rating_sort', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => '_verdix_rating_sort',
				'value'   => 0,
				'type'    => 'DECIMAL(3,1)',
				'compare' => '>',
			),
		),
	);

	if ( 'latest' === $args['type'] ) {
		$query['orderby'] = 'date';
		$query['order']   = 'DESC';
	} else {
		$query['orderby'] = array(
			'meta_value_num' => 'DESC',
			'date'           => 'DESC',
		);
	}

	if ( '' !== (string) $args['schema_type'] && isset( verdix_get_schema_types()[ $args['schema_type'] ] ) ) {
		$query['meta_query'][] = array(
			'key'     => '_verdix_review',
			'value'   => '"schema_type":' . wp_json_encode( $args['schema_type'] ),
			'compare' => 'LIKE',
		);
	}

	if ( '' !== (string) $args['category'] ) {
		$query['category_name'] = sanitize_text_field( $args['category'] );
	}

	$posts = get_posts( $query );
	if ( ! $posts ) {
		return defined( 'REST_REQUEST' ) && REST_REQUEST
			? '<p class="verdix-list__empty">' . esc_html__( 'No reviews match these settings yet.', 'verdix-reviews' ) . '</p>'
			: '';
	}

	wp_enqueue_style( 'verdix-review-list' );

	$options      = get_option( 'verdix_settings', array() );
	$accent       = ! empty( $options['accent_color'] ) ? $options['accent_color'] : '#1e73be';
	$style        = sprintf( '--sb-accent: %s; --sb-accent-light: %s;', esc_attr( $accent ), esc_attr( verdix_lighten_color( $accent, 0.4 ) ) );
	$star_svg     = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
	$five_stars   = str_repeat( $star_svg, 5 );
	$show_image   = filter_var( $args['image'], FILTER_VALIDATE_BOOLEAN );

	ob_start();
	?>
	<ul class="verdix-list" style="<?php echo esc_attr( $style ); ?>">
		<?php foreach ( $posts as $post ) : ?>
			<?php
			$rating = (float) get_post_meta( $post->ID, '_verdix_rating_sort', true );
			$thumb  = $show_image ? get_the_post_thumbnail( $post, 'thumbnail', array( 'loading' => 'lazy' ) ) : '';
			?>
			<li class="verdix-list__item">
				<?php if ( $thumb ) : ?>
					<a class="verdix-list__thumb" href="<?php echo esc_url( get_permalink( $post ) ); ?>" tabindex="-1" aria-hidden="true"><?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				<?php endif; ?>
				<div class="verdix-list__body">
					<a class="verdix-list__title" href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( wp_strip_all_tags( $post->post_title ) ); ?></a>
					<div class="verdix-list__rating">
						<span class="verdix-list__score"><?php echo esc_html( $rating ); ?></span>
						<span class="verdix-box__stars" role="img" aria-label="<?php /* translators: %s: rating value */ echo esc_attr( sprintf( __( 'Rating: %s out of 5', 'verdix-reviews' ), $rating ) ); ?>">
							<span class="verdix-box__stars-background"><?php echo $five_stars; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="verdix-box__stars-foreground" style="width:<?php echo esc_attr( $rating / 5 * 100 ); ?>%;"><?php echo $five_stars; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						</span>
					</div>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
	return ob_get_clean();
}

function verdix_review_list_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'type'        => 'top-rated',
			'count'       => 5,
			'schema_type' => '',
			'category'    => '',
			'image'       => 'true',
		),
		$atts,
		'verdix_list'
	);

	return verdix_render_review_list( $atts );
}
add_shortcode( 'verdix_list', 'verdix_review_list_shortcode' );

function verdix_register_review_list_block() {
	$block_dir = VERDIX_DIR . 'blocks/review-list';

	wp_register_style( 'verdix-review-list', VERDIX_URL . 'assets/css/review-list.css', array(), VERDIX_VERSION );

	wp_register_script(
		'verdix-list-editor',
		VERDIX_URL . 'blocks/review-list/editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
		VERDIX_VERSION,
		true
	);

	$schema_types = array();
	foreach ( verdix_get_schema_types() as $slug => $label ) {
		$schema_types[] = array(
			'value' => $slug,
			'label' => $label,
		);
	}
	wp_add_inline_script( 'verdix-list-editor', 'window.verdixListConfig = ' . wp_json_encode( array( 'schemaTypes' => $schema_types ) ) . ';', 'before' );

	register_block_type(
		$block_dir,
		array(
			'render_callback' => 'verdix_render_review_list_block',
			'editor_script'   => 'verdix-list-editor',
			'style'           => 'verdix-review-list',
		)
	);
}
add_action( 'init', 'verdix_register_review_list_block' );

function verdix_render_review_list_block( $attributes ) {
	$html = verdix_render_review_list( $attributes );

	return '' === $html ? '' : '<div ' . get_block_wrapper_attributes() . '>' . $html . '</div>';
}
