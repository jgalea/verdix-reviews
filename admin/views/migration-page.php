<?php
defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- File is included inside a class method; variables are function-scoped, not global.

$sources = verdix_get_migration_sources();

// Detect posts up-front so each row shows real status.
$detected = array();
foreach ( $sources as $source_key => $source_config ) {
	$posts = is_callable( $source_config['detect'] ) ? call_user_func( $source_config['detect'] ) : array();
	if ( ! is_array( $posts ) ) {
		$posts = array();
	}
	$detected[ $source_key ] = array(
		'config' => $source_config,
		'posts'  => $posts,
		'total'  => count( $posts ),
	);
}

$total_migratable = array_sum( wp_list_pluck( $detected, 'total' ) );
?>
<div class="wrap verdix-admin">
	<h1 class="verdix-admin__title">
		<span class="dashicons dashicons-star-filled"></span>
		<?php esc_html_e( 'Verdix Reviews Migration', 'verdix-reviews' ); ?>
	</h1>

	<div id="verdix-migration-status" style="display:none;" class="notice">
		<p id="verdix-migration-message"></p>
	</div>

	<div class="verdix-panel">
		<h2 class="verdix-panel__title"><?php esc_html_e( 'Supported Sources', 'verdix-reviews' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Verdix Reviews can import review data from the plugins listed below. Your site is scanned automatically; sources with matching data let you review and migrate each post before any changes are saved.', 'verdix-reviews' ); ?>
		</p>

		<table class="wp-list-table widefat striped" style="margin-top: 12px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Source Plugin', 'verdix-reviews' ); ?></th>
					<th style="width: 180px;"><?php esc_html_e( 'Status', 'verdix-reviews' ); ?></th>
					<th style="width: 120px;"><?php esc_html_e( 'Posts Found', 'verdix-reviews' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $detected as $source_key => $info ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $info['config']['label'] ); ?></strong></td>
						<td>
							<?php if ( $info['total'] > 0 ) : ?>
								<span class="verdix-migrate-badge verdix-migrate-badge--ready">
									<span class="dashicons dashicons-yes-alt"></span>
									<?php esc_html_e( 'Data detected', 'verdix-reviews' ); ?>
								</span>
							<?php else : ?>
								<span class="verdix-migrate-badge verdix-migrate-badge--none">
									<span class="dashicons dashicons-minus"></span>
									<?php esc_html_e( 'Nothing to migrate', 'verdix-reviews' ); ?>
								</span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $info['total'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( 0 === $total_migratable ) : ?>
			<p class="description" style="margin-top: 12px;">
				<?php esc_html_e( 'No legacy review data was detected on this site. Once you install content from one of the supported plugins above, migration options will appear here automatically.', 'verdix-reviews' ); ?>
			</p>
		<?php endif; ?>
	</div>

	<?php foreach ( $detected as $source_key => $info ) : ?>
		<?php
		if ( 0 === $info['total'] ) {
			continue;
		}
		$source_config = $info['config'];
		$posts         = $info['posts'];
		$total         = $info['total'];
		?>

		<div class="verdix-panel">
			<h2 class="verdix-panel__title">
				<?php
				/* translators: %s: migration source label (e.g. WP Review Pro) */
				echo esc_html( sprintf( __( 'Migrate from %s', 'verdix-reviews' ), $source_config['label'] ) );
				?>
			</h2>
			<p>
				<?php
				/* translators: %1$d: number of posts, %2$s: source label */
				echo esc_html( sprintf( _n( 'Found %1$d post with %2$s data.', 'Found %1$d posts with %2$s data.', $total, 'verdix-reviews' ), $total, $source_config['label'] ) );
				?>
			</p>

			<p>
				<button type="button" class="button button-primary verdix-migrate-all-btn" data-source="<?php echo esc_attr( $source_key ); ?>">
					<?php
					/* translators: %d: number of posts */
					echo esc_html( sprintf( __( 'Migrate All (%d posts)', 'verdix-reviews' ), $total ) );
					?>
				</button>
			</p>

			<?php if ( ! empty( $source_config['replace'] ) ) : ?>
				<p>
					<label>
						<input type="checkbox" class="verdix-migrate-replace" data-source="<?php echo esc_attr( $source_key ); ?>">
						<?php echo esc_html( $source_config['replace_label'] ); ?>
					</label>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $source_config['plugin_active'] ) ) : ?>
				<p>
					<label>
						<input type="checkbox" class="verdix-stop-schema" data-source="<?php echo esc_attr( $source_key ); ?>" <?php checked( verdix_schema_stop_enabled( $source_key ) ); ?>>
						<?php
						/* translators: %s: source plugin name */
						echo esc_html( sprintf( __( 'Stop %s printing its review schema', 'verdix-reviews' ), $source_config['plugin_name'] ) );
						?>
					</label>
					<span class="description"><?php esc_html_e( 'Applies to posts that have Verdix data, so the page does not output two review schemas.', 'verdix-reviews' ); ?></span>
				</p>
			<?php endif; ?>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width: 50px;"><?php esc_html_e( 'ID', 'verdix-reviews' ); ?></th>
						<th><?php esc_html_e( 'Post Title', 'verdix-reviews' ); ?></th>
						<?php foreach ( $source_config['columns'] as $col ) : ?>
							<th style="width: 80px;"><?php echo esc_html( ucfirst( $col ) ); ?></th>
						<?php endforeach; ?>
						<th style="width: 100px;"><?php esc_html_e( 'Status', 'verdix-reviews' ); ?></th>
						<th style="width: 120px;"><?php esc_html_e( 'Action', 'verdix-reviews' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $posts as $post ) : ?>
						<?php
						$existing = get_post_meta( $post->ID, '_verdix_review', true );
						$already  = ! empty( $existing );
						$row_data = is_callable( $source_config['row_data'] ) ? call_user_func( $source_config['row_data'], $post ) : array();
						?>
						<tr id="verdix-migrate-row-<?php echo esc_attr( $post->ID ); ?>">
							<td><?php echo esc_html( $post->ID ); ?></td>
							<td><a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>"><?php echo esc_html( get_the_title( $post->ID ) ); ?></a></td>
							<?php foreach ( $source_config['columns'] as $col ) : ?>
								<td><?php echo isset( $row_data[ $col ] ) ? wp_kses_post( $row_data[ $col ] ) : '-'; ?></td>
							<?php endforeach; ?>
							<td class="verdix-migrate-status">
								<?php if ( $already ) : ?>
									<span style="color: #0073aa;"><?php esc_html_e( 'Already migrated', 'verdix-reviews' ); ?></span>
								<?php else : ?>
									<span style="color: #999;"><?php esc_html_e( 'Pending', 'verdix-reviews' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( ! $already ) : ?>
									<button type="button" class="button button-small verdix-migrate-single"
										data-post-id="<?php echo esc_attr( $post->ID ); ?>"
										data-source="<?php echo esc_attr( $source_key ); ?>">
										<?php esc_html_e( 'Migrate', 'verdix-reviews' ); ?>
									</button>
								<?php else : ?>
									&mdash;
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endforeach; ?>
</div>
