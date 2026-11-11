<?php
defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- File is included inside a class method; variables are function-scoped, not global.

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- tab switching only.
$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
?>
<div class="wrap verdix-admin">
	<h1 class="verdix-admin__title">
		<span class="dashicons dashicons-star-filled"></span>
		<?php esc_html_e( 'Verdix Reviews Settings', 'verdix-reviews' ); ?>
	</h1>

	<nav class="verdix-tabs">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=verdix-settings&tab=general' ) ); ?>"
			class="verdix-tab <?php echo 'general' === $active_tab ? 'verdix-tab--active' : ''; ?>">
			<span class="dashicons dashicons-admin-generic"></span>
			<?php esc_html_e( 'General', 'verdix-reviews' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=verdix-settings&tab=appearance' ) ); ?>"
			class="verdix-tab <?php echo 'appearance' === $active_tab ? 'verdix-tab--active' : ''; ?>">
			<span class="dashicons dashicons-art"></span>
			<?php esc_html_e( 'Appearance', 'verdix-reviews' ); ?>
		</a>
		<?php do_action( 'verdix_settings_tabs', $active_tab ); ?>
	</nav>

	<div class="verdix-settings-content">
		<?php if ( 'general' === $active_tab ) : ?>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'verdix_settings_group' );
				?>
				<input type="hidden" name="verdix_settings[_tab]" value="general">
				<div class="verdix-panel">
					<h2 class="verdix-panel__title"><?php esc_html_e( 'Review defaults', 'verdix-reviews' ); ?></h2>
					<table class="form-table" role="presentation">
						<?php do_settings_fields( 'verdix-settings', 'verdix_general' ); ?>
					<?php do_action( 'verdix_settings_after_general' ); ?>
					</table>
				</div>

				<div class="verdix-panel">
					<h2 class="verdix-panel__title"><?php esc_html_e( 'Post types', 'verdix-reviews' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable reviews on', 'verdix-reviews' ); ?></th>
							<td>
								<?php
								$enabled_types = verdix_get_post_types();
								$available     = get_post_types( array( 'public' => true ), 'objects' );
								foreach ( $available as $pt ) :
									if ( 'attachment' === $pt->name ) {
										continue;
									}
								?>
									<label style="display: block; margin-bottom: 6px;">
										<input type="checkbox" name="verdix_settings[post_types][]"
											value="<?php echo esc_attr( $pt->name ); ?>"
											<?php checked( in_array( $pt->name, $enabled_types, true ) ); ?>>
										<?php echo esc_html( $pt->labels->name ); ?>
									</label>
								<?php endforeach; ?>
								<p class="description"><?php esc_html_e( 'Select which post types can have review boxes.', 'verdix-reviews' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<?php submit_button(); ?>
			</form>

		<?php elseif ( 'appearance' === $active_tab ) : ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'verdix_settings_group' ); ?>
				<input type="hidden" name="verdix_settings[_tab]" value="appearance">

				<div class="verdix-panel">
					<h2 class="verdix-panel__title"><?php esc_html_e( 'Style and colors', 'verdix-reviews' ); ?></h2>
					<table class="form-table" role="presentation">
						<?php do_settings_fields( 'verdix-settings-appearance', 'verdix_appearance' ); ?>
					</table>
				</div>

				<div class="verdix-panel">
					<h2 class="verdix-panel__title"><?php esc_html_e( 'Preview', 'verdix-reviews' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Preview how the review box will look with current settings. Save to apply changes.', 'verdix-reviews' ); ?></p>
					<div id="verdix-settings-preview" style="max-width: 600px; margin-top: 16px;">
						<?php
						echo verdix_render_box( verdix_get_preview_review(), 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</div>
				</div>

				<?php submit_button(); ?>
			</form>

		<?php endif; ?>
		<?php do_action( 'verdix_settings_tab_content', $active_tab ); ?>
	</div>
</div>
