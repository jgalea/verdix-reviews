<?php
defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- File is included inside a class method; variables are function-scoped, not global.

$options    = get_option( 'verdix_settings', array() );
$post_types = apply_filters( 'verdix_post_types', isset( $options['post_types'] ) ? $options['post_types'] : array( 'post', 'page' ) );
// Count reviewed posts.
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$total_reviews = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != %s",
		'_verdix_review',
		''
	)
);

// Get recent reviews.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$recent_ids = $wpdb->get_col(
	$wpdb->prepare(
		"SELECT pm.post_id
		FROM {$wpdb->postmeta} pm
		INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		WHERE pm.meta_key = %s
		AND pm.meta_value != %s
		AND p.post_status IN (%s, %s, %s, %s)
		ORDER BY p.post_modified DESC
		LIMIT %d",
		'_verdix_review',
		'',
		'publish',
		'draft',
		'pending',
		'private',
		10
	)
);

// Calculate average rating.
$avg_rating = 0;
$rating_count = 0;
if ( $total_reviews > 0 ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$all_meta = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != %s",
			'_verdix_review',
			''
		)
	);
	$sum = 0;
	foreach ( $all_meta as $raw ) {
		$data = json_decode( $raw, true );
		if ( is_array( $data ) && ! empty( $data['rating'] ) ) {
			$type = ! empty( $data['rating_type'] ) ? $data['rating_type'] : 'star';
			$sum += verdix_normalize_to_star_scale( $data['rating'], $type );
			$rating_count++;
		}
	}
	if ( $rating_count > 0 ) {
		$avg_rating = round( $sum / $rating_count, 1 );
	}
}

// Count migratable posts across all registered migration sources (WP Review Pro + Pro sources).
$wp_review_count = verdix_count_all_migratable();

// Latest review — use the most recently modified reviewed post.
$latest_review_label = '—';
$latest_review_title = '';
if ( ! empty( $recent_ids ) ) {
	$latest_post = get_post( $recent_ids[0] );
	if ( $latest_post ) {
		$latest_gmt = strtotime( $latest_post->post_modified_gmt );
		if ( $latest_gmt ) {
			/* translators: %s: human-readable time difference, e.g. "2 days". */
			$latest_review_label = sprintf( __( '%s ago', 'verdix-reviews' ), human_time_diff( $latest_gmt ) );
			$latest_review_title = $latest_post->post_title;
		}
	}
}
?>
<div class="wrap verdix-admin">
	<h1 class="verdix-admin__title">
		<span class="dashicons dashicons-star-filled"></span>
		<?php esc_html_e( 'Verdix Reviews', 'verdix-reviews' ); ?>
		<?php do_action( 'verdix_admin_dashboard_title_badge' ); ?>
	</h1>

	<div class="verdix-dashboard">
		<!-- Stats cards -->
		<div class="verdix-stats">
			<div class="verdix-stat-card">
				<div class="verdix-stat-card__icon dashicons dashicons-edit"></div>
				<div class="verdix-stat-card__content">
					<span class="verdix-stat-card__value"><?php echo esc_html( $total_reviews ); ?></span>
					<span class="verdix-stat-card__label"><?php esc_html_e( 'Total reviews', 'verdix-reviews' ); ?></span>
				</div>
			</div>

			<div class="verdix-stat-card">
				<div class="verdix-stat-card__icon dashicons dashicons-star-filled"></div>
				<div class="verdix-stat-card__content">
					<span class="verdix-stat-card__value"><?php echo esc_html( $avg_rating ); ?><small>/5</small></span>
					<span class="verdix-stat-card__label"><?php esc_html_e( 'Average rating', 'verdix-reviews' ); ?></span>
				</div>
			</div>

			<div class="verdix-stat-card">
				<div class="verdix-stat-card__icon dashicons dashicons-migrate"></div>
				<div class="verdix-stat-card__content">
					<span class="verdix-stat-card__value"><?php echo esc_html( $wp_review_count ); ?></span>
					<span class="verdix-stat-card__label">
						<?php if ( $wp_review_count > 0 ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=verdix-migration' ) ); ?>">
								<?php esc_html_e( 'Ready to migrate', 'verdix-reviews' ); ?>
							</a>
						<?php else : ?>
							<?php esc_html_e( 'Ready to migrate', 'verdix-reviews' ); ?>
						<?php endif; ?>
					</span>
				</div>
			</div>

			<div class="verdix-stat-card"<?php echo $latest_review_title ? ' title="' . esc_attr( $latest_review_title ) . '"' : ''; ?>>
				<div class="verdix-stat-card__icon dashicons dashicons-clock"></div>
				<div class="verdix-stat-card__content">
					<span class="verdix-stat-card__value"><?php echo esc_html( $latest_review_label ); ?></span>
					<span class="verdix-stat-card__label"><?php esc_html_e( 'Latest review', 'verdix-reviews' ); ?></span>
				</div>
			</div>
		</div>
		<?php do_action( 'verdix_admin_dashboard_after_stats' ); ?>

		<div class="verdix-dashboard__grid">
			<!-- Recent reviews -->
			<div class="verdix-panel">
				<h2 class="verdix-panel__title">
					<?php esc_html_e( 'Recent reviews', 'verdix-reviews' ); ?>
					<?php if ( $total_reviews > 10 ) : ?>
						<span class="verdix-panel__title-meta">
							<?php
							/* translators: %d: total number of reviews */
							echo esc_html( sprintf( __( 'showing 10 of %d', 'verdix-reviews' ), $total_reviews ) );
							?>
						</span>
					<?php endif; ?>
				</h2>
				<?php if ( empty( $recent_ids ) ) : ?>
					<p class="verdix-panel__empty"><?php esc_html_e( 'No reviews yet. Add a Review Box block to any post to get started.', 'verdix-reviews' ); ?></p>
				<?php else : ?>
					<table class="verdix-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Post', 'verdix-reviews' ); ?></th>
								<th><?php esc_html_e( 'Rating', 'verdix-reviews' ); ?></th>
								<th><?php esc_html_e( 'Type', 'verdix-reviews' ); ?></th>
								<th><?php esc_html_e( 'Status', 'verdix-reviews' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $recent_ids as $pid ) : ?>
								<?php
								$post   = get_post( $pid );
								$review = verdix_get_review( $pid );
								if ( ! $post || ! $review ) {
									continue;
								}
								$r_type  = ! empty( $review['rating_type'] ) ? $review['rating_type'] : 'star';
								$rt_reg  = verdix_get_rating_types();
								$rt_def  = isset( $rt_reg[ $r_type ] ) ? $rt_reg[ $r_type ] : $rt_reg['star'];
								$rt_fmt  = isset( $rt_def['format'] ) ? $rt_def['format'] : '%s/5';
								$display = sprintf( $rt_fmt, $review['rating'] );
								?>
								<tr>
									<td>
										<a href="<?php echo esc_url( get_edit_post_link( $pid ) ); ?>">
											<?php echo esc_html( get_the_title( $pid ) ); ?>
										</a>
									</td>
									<td><strong><?php echo esc_html( $display ); ?></strong></td>
									<td><?php echo esc_html( ucfirst( $r_type ) ); ?></td>
									<td>
										<span class="verdix-status verdix-status--<?php echo esc_attr( $post->post_status ); ?>">
											<?php echo esc_html( get_post_status_object( $post->post_status )->label ); ?>
										</span>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<p class="verdix-panel__footer">
						<a href="<?php echo esc_url( admin_url( 'edit.php?orderby=verdix_rating&order=DESC' ) ); ?>">
							<?php esc_html_e( 'View all reviewed posts', 'verdix-reviews' ); ?> &rarr;
						</a>
					</p>
				<?php endif; ?>
			</div>

			<!-- Quick links panel -->
			<div class="verdix-panel">
				<h2 class="verdix-panel__title"><?php esc_html_e( 'Quick links', 'verdix-reviews' ); ?></h2>
				<div class="verdix-quick-links">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=verdix-settings' ) ); ?>" class="verdix-quick-link">
						<span class="dashicons dashicons-admin-generic"></span>
						<?php esc_html_e( 'Settings', 'verdix-reviews' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=verdix-migration' ) ); ?>" class="verdix-quick-link">
						<span class="dashicons dashicons-migrate"></span>
						<?php esc_html_e( 'Migration tools', 'verdix-reviews' ); ?>
						<?php if ( $wp_review_count > 0 ) : ?>
							<span class="verdix-badge verdix-badge--count"><?php echo esc_html( $wp_review_count ); ?></span>
						<?php endif; ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>" class="verdix-quick-link">
						<span class="dashicons dashicons-plus-alt2"></span>
						<?php esc_html_e( 'New review post', 'verdix-reviews' ); ?>
					</a>
				</div>

				<h3 class="verdix-panel__subtitle"><?php esc_html_e( 'Usage', 'verdix-reviews' ); ?></h3>
				<div class="verdix-usage-hints">
					<div class="verdix-hint">
						<strong><?php esc_html_e( 'Block editor', 'verdix-reviews' ); ?></strong>
						<p><?php esc_html_e( 'Add the "Review Box" block to any post or page.', 'verdix-reviews' ); ?></p>
					</div>
					<div class="verdix-hint">
						<strong><?php esc_html_e( 'Shortcode', 'verdix-reviews' ); ?></strong>
						<p><code>[verdix]</code> <?php esc_html_e( 'or', 'verdix-reviews' ); ?> <code>[verdix id="123"]</code></p>
					</div>
					<div class="verdix-hint">
						<strong><?php esc_html_e( 'REST API', 'verdix-reviews' ); ?></strong>
						<p><code>/wp-json/verdix/v1/review/{id}</code></p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
