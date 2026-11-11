<?php
defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- File is included inside a class method; variables are function-scoped, not global.

$pros = is_array( $data['pros'] ) ? $data['pros'] : array();
$cons = is_array( $data['cons'] ) ? $data['cons'] : array();
if ( empty( $pros ) ) {
	$pros = array( '' );
}
if ( empty( $cons ) ) {
	$cons = array( '' );
}
?>
<div class="verdix-meta-box">
	<table class="form-table">
		<tr>
			<th scope="row">
				<label for="verdix_review_heading"><?php esc_html_e( 'Heading', 'verdix-reviews' ); ?></label>
			</th>
			<td>
				<input type="text" id="verdix_review_heading" name="verdix_review_heading"
					value="<?php echo esc_attr( $data['heading'] ); ?>" class="large-text"
					placeholder="<?php esc_attr_e( 'e.g., Our Verdict', 'verdix-reviews' ); ?>">
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="verdix_review_style"><?php esc_html_e( 'Style', 'verdix-reviews' ); ?></label>
			</th>
			<td>
				<?php
				$styles = verdix_get_styles();
				$current_style = isset( $data['style'] ) ? $data['style'] : '';
				?>
				<select id="verdix_review_style" name="verdix_review_style">
					<option value="" <?php selected( $current_style, '' ); ?>><?php esc_html_e( 'Use global default', 'verdix-reviews' ); ?></option>
					<?php foreach ( $styles as $style_key => $style_config ) : ?>
						<option value="<?php echo esc_attr( $style_key ); ?>" <?php selected( $current_style, $style_key ); ?>><?php echo esc_html( $style_config['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="verdix_review_rating_type"><?php esc_html_e( 'Rating type', 'verdix-reviews' ); ?></label>
			</th>
			<td>
				<?php $rating_types = verdix_get_rating_types(); ?>
				<?php if ( count( $rating_types ) > 1 ) : ?>
					<select id="verdix_review_rating_type" name="verdix_review_rating_type">
						<?php foreach ( $rating_types as $type_key => $type_config ) : ?>
							<option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( $data['rating_type'], $type_key ); ?>><?php echo esc_html( $type_config['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php else : ?>
					<input type="hidden" name="verdix_review_rating_type" value="star">
					<span><?php esc_html_e( 'Star (0-5)', 'verdix-reviews' ); ?></span>
				<?php endif; ?>
			</td>
		</tr>

		<?php do_action( 'verdix_meta_box_fields', $data ); ?>

		<tr>
			<th scope="row">
				<label for="verdix_review_rating"><?php esc_html_e( 'Rating', 'verdix-reviews' ); ?></label>
			</th>
			<td>
				<?php
				$rating_types = verdix_get_rating_types();
				$rating_type  = isset( $data['rating_type'] ) ? $data['rating_type'] : 'star';
				$type_config  = isset( $rating_types[ $rating_type ] ) ? $rating_types[ $rating_type ] : $rating_types['star'];
				$rating_max   = $type_config['max'];
				$rating_step  = $type_config['step'];
				$rating_desc  = $type_config['label'];
				?>
				<input type="number" id="verdix_review_rating" name="verdix_review_rating"
					value="<?php echo esc_attr( $data['rating'] ); ?>"
					min="0" max="<?php echo esc_attr( $rating_max ); ?>" step="<?php echo esc_attr( $rating_step ); ?>" style="width: 80px;">
				<span class="description" id="verdix-rating-description"><?php echo esc_html( $rating_desc ); ?></span>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="verdix_review_position"><?php esc_html_e( 'Position', 'verdix-reviews' ); ?></label>
			</th>
			<td>
				<select id="verdix_review_position" name="verdix_review_position">
					<option value="" <?php selected( $data['position'], '' ); ?>><?php esc_html_e( 'Use global default', 'verdix-reviews' ); ?></option>
					<option value="bottom" <?php selected( $data['position'], 'bottom' ); ?>><?php esc_html_e( 'After content', 'verdix-reviews' ); ?></option>
					<option value="top" <?php selected( $data['position'], 'top' ); ?>><?php esc_html_e( 'Before content', 'verdix-reviews' ); ?></option>
					<option value="both" <?php selected( $data['position'], 'both' ); ?>><?php esc_html_e( 'Before and after content', 'verdix-reviews' ); ?></option>
					<option value="manual" <?php selected( $data['position'], 'manual' ); ?>><?php esc_html_e( 'Manual (block/shortcode only)', 'verdix-reviews' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Where to display the review box. Leave on global default unless you want a per-post override.', 'verdix-reviews' ); ?></p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="verdix_review_summary"><?php esc_html_e( 'Summary', 'verdix-reviews' ); ?></label>
			</th>
			<td>
				<textarea id="verdix_review_summary" name="verdix_review_summary" rows="3" class="large-text"
					placeholder="<?php esc_attr_e( 'Brief review summary...', 'verdix-reviews' ); ?>"><?php echo esc_textarea( $data['summary'] ); ?></textarea>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Pros', 'verdix-reviews' ); ?></th>
			<td>
				<div class="verdix-repeater" data-field="pros">
					<?php foreach ( $pros as $pro ) : ?>
						<div class="verdix-repeater__row">
							<input type="text" name="verdix_review_pros[]" value="<?php echo esc_attr( $pro ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Add a pro...', 'verdix-reviews' ); ?>">
							<button type="button" class="button verdix-repeater__remove" aria-label="<?php esc_attr_e( 'Remove', 'verdix-reviews' ); ?>">&times;</button>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button verdix-repeater__add" data-target="pros"><?php esc_html_e( '+ Add pro', 'verdix-reviews' ); ?></button>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Cons', 'verdix-reviews' ); ?></th>
			<td>
				<div class="verdix-repeater" data-field="cons">
					<?php foreach ( $cons as $con ) : ?>
						<div class="verdix-repeater__row">
							<input type="text" name="verdix_review_cons[]" value="<?php echo esc_attr( $con ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Add a con...', 'verdix-reviews' ); ?>">
							<button type="button" class="button verdix-repeater__remove" aria-label="<?php esc_attr_e( 'Remove', 'verdix-reviews' ); ?>">&times;</button>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button verdix-repeater__add" data-target="cons"><?php esc_html_e( '+ Add con', 'verdix-reviews' ); ?></button>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="verdix_review_cta_text"><?php esc_html_e( 'CTA button text', 'verdix-reviews' ); ?></label>
			</th>
			<td>
				<input type="text" id="verdix_review_cta_text" name="verdix_review_cta_text"
					value="<?php echo esc_attr( $data['cta_text'] ); ?>" class="regular-text"
					placeholder="<?php esc_attr_e( 'e.g., Visit website', 'verdix-reviews' ); ?>">
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="verdix_review_cta_url"><?php esc_html_e( 'CTA button URL', 'verdix-reviews' ); ?></label>
			</th>
			<td>
				<input type="url" id="verdix_review_cta_url" name="verdix_review_cta_url"
					value="<?php echo esc_url( $data['cta_url'] ); ?>" class="large-text"
					placeholder="https://">
			</td>
		</tr>

		<tr>
			<th scope="row"><?php esc_html_e( 'Offers', 'verdix-reviews' ); ?></th>
			<td>
				<?php
				$katina_links = verdix_katina_active()
					? get_posts(
						array(
							'post_type'      => 'katina_link',
							'post_status'    => 'publish',
							'posts_per_page' => 200,
							'orderby'        => 'title',
							'order'          => 'ASC',
						)
					)
					: array();
				$offer_blank  = array(
					'merchant'    => '',
					'price'       => '',
					'currency'    => 'USD',
					'url'         => '',
					'cta_text'    => '',
					'katina_link' => 0,
				);
				$saved_offers = is_array( $data['offers'] ) ? array_values( $data['offers'] ) : array();
				?>
				<div class="verdix-offers" data-next="<?php echo esc_attr( count( $saved_offers ) ); ?>">
					<?php foreach ( $saved_offers as $offer_index => $offer ) : ?>
						<?php verdix_meta_box_offer_row( $offer_index, wp_parse_args( $offer, $offer_blank ), $katina_links ); ?>
					<?php endforeach; ?>
				</div>
				<template id="verdix-offer-template">
					<?php verdix_meta_box_offer_row( '__INDEX__', $offer_blank, $katina_links ); ?>
				</template>
				<button type="button" class="button verdix-offers__add"><?php esc_html_e( '+ Add offer', 'verdix-reviews' ); ?></button>
				<p class="description"><?php esc_html_e( 'Several offers are listed as separate buttons. With none, the price and CTA fields are used.', 'verdix-reviews' ); ?></p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="verdix_review_disclosure"><?php esc_html_e( 'Disclosure', 'verdix-reviews' ); ?></label>
			</th>
			<td>
				<select id="verdix_review_disclosure" name="verdix_review_disclosure">
					<option value="" <?php selected( $data['disclosure'], '' ); ?>><?php esc_html_e( 'Use global default', 'verdix-reviews' ); ?></option>
					<?php foreach ( verdix_get_disclosure_types() as $disclosure_value => $disclosure_label ) : ?>
						<option value="<?php echo esc_attr( $disclosure_value ); ?>" <?php selected( $data['disclosure'], $disclosure_value ); ?>><?php echo esc_html( $disclosure_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<p>
					<textarea id="verdix_review_disclosure_text" name="verdix_review_disclosure_text" rows="2" class="large-text"
						placeholder="<?php esc_attr_e( 'Custom disclosure text', 'verdix-reviews' ); ?>"><?php echo esc_textarea( $data['disclosure_text'] ); ?></textarea>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="verdix_review_last_tested"><?php esc_html_e( 'Last tested', 'verdix-reviews' ); ?></label>
			</th>
			<td>
				<input type="date" id="verdix_review_last_tested" name="verdix_review_last_tested" value="<?php echo esc_attr( $data['last_tested'] ); ?>">
			</td>
		</tr>
	</table>

	<hr>
	<h4><?php esc_html_e( 'Schema settings', 'verdix-reviews' ); ?></h4>
	<table class="form-table">
		<tr>
			<th scope="row">
				<label for="verdix_review_schema_type"><?php esc_html_e( 'Schema type', 'verdix-reviews' ); ?></label>
			</th>
			<td>
				<select id="verdix_review_schema_type" name="verdix_review_schema_type">
					<?php foreach ( verdix_get_schema_types() as $type_value => $type_label ) : ?>
						<?php $eligibility = verdix_schema_eligibility( $type_value ); ?>
						<option value="<?php echo esc_attr( $type_value ); ?>" data-eligibility="<?php echo esc_attr( $eligibility['status'] ); ?>" data-note="<?php echo esc_attr( $eligibility['message'] ); ?>" <?php selected( $data['schema_type'], $type_value ); ?>><?php echo esc_html( $type_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php $current_eligibility = verdix_schema_eligibility( $data['schema_type'] ); ?>
				<p class="description verdix-schema-type-note" style="color:#b32d2e;<?php echo '' === $current_eligibility['message'] ? 'display:none;' : ''; ?>"><?php echo esc_html( $current_eligibility['message'] ); ?></p>
			</td>
		</tr>

		<?php
		$type_registry = verdix_get_type_fields();
		$saved_tf      = isset( $data['type_fields'] ) && is_array( $data['type_fields'] ) ? $data['type_fields'] : array();
		if ( ! empty( $type_registry ) ) :
		?>
		<tr id="verdix-type-fields-row">
			<th scope="row"><?php esc_html_e( 'Type-specific fields', 'verdix-reviews' ); ?></th>
			<td>
				<?php foreach ( $type_registry as $type_key => $type_fields ) : ?>
					<div class="verdix-type-fields" data-type="<?php echo esc_attr( $type_key ); ?>"
						style="<?php echo $data['schema_type'] === $type_key ? '' : 'display:none;'; ?>">
						<?php foreach ( $type_fields as $field_key => $field_def ) :
							$field_type = isset( $field_def['type'] ) ? $field_def['type'] : 'text';
							$value      = isset( $saved_tf[ $type_key ][ $field_key ] ) ? $saved_tf[ $type_key ][ $field_key ] : '';
							$input_id   = 'verdix_tf_' . sanitize_key( $type_key ) . '_' . sanitize_key( $field_key );
							$input_name = 'verdix_review_type_fields[' . $type_key . '][' . $field_key . ']';
						?>
							<p>
								<label for="<?php echo esc_attr( $input_id ); ?>" style="display:block; font-weight:600;">
									<?php echo esc_html( $field_def['label'] ); ?>
								</label>
								<?php if ( 'textarea' === $field_type ) : ?>
									<textarea id="<?php echo esc_attr( $input_id ); ?>"
										name="<?php echo esc_attr( $input_name ); ?>"
										class="large-text" rows="3"><?php echo esc_textarea( $value ); ?></textarea>
								<?php elseif ( 'number' === $field_type ) : ?>
									<input type="number" step="any" id="<?php echo esc_attr( $input_id ); ?>"
										name="<?php echo esc_attr( $input_name ); ?>"
										value="<?php echo esc_attr( $value ); ?>" class="small-text">
								<?php elseif ( 'date' === $field_type ) : ?>
									<input type="date" id="<?php echo esc_attr( $input_id ); ?>"
										name="<?php echo esc_attr( $input_name ); ?>"
										value="<?php echo esc_attr( $value ); ?>">
								<?php else : ?>
									<input type="text" id="<?php echo esc_attr( $input_id ); ?>"
										name="<?php echo esc_attr( $input_name ); ?>"
										value="<?php echo esc_attr( $value ); ?>" class="regular-text">
								<?php endif; ?>
							</p>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
				<p class="description verdix-type-fields-empty"
					style="<?php echo isset( $type_registry[ $data['schema_type'] ] ) ? 'display:none;' : ''; ?>">
					<?php esc_html_e( 'This schema type has no extra fields. Base review data (rating, summary, pros/cons) will still be included.', 'verdix-reviews' ); ?>
				</p>
			</td>
		</tr>
		<?php endif; ?>

		<tr>
			<th scope="row">
				<label for="verdix_review_product_name"><?php esc_html_e( 'Product / item name', 'verdix-reviews' ); ?></label>
			</th>
			<td>
				<input type="text" id="verdix_review_product_name" name="verdix_review_product_name"
					value="<?php echo esc_attr( $data['product_name'] ); ?>" class="regular-text"
					placeholder="<?php esc_attr_e( 'Defaults to post title', 'verdix-reviews' ); ?>">
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="verdix_review_price"><?php esc_html_e( 'Price', 'verdix-reviews' ); ?></label>
			</th>
			<td>
				<input type="text" id="verdix_review_price" name="verdix_review_price"
					value="<?php echo esc_attr( $data['price'] ); ?>" style="width: 120px;"
					placeholder="<?php esc_attr_e( 'e.g., 49.99', 'verdix-reviews' ); ?>">
				<select id="verdix_review_currency" name="verdix_review_currency" style="width: 80px;">
					<option value="USD" <?php selected( $data['currency'], 'USD' ); ?>>USD</option>
					<option value="EUR" <?php selected( $data['currency'], 'EUR' ); ?>>EUR</option>
					<option value="GBP" <?php selected( $data['currency'], 'GBP' ); ?>>GBP</option>
				</select>
				<p class="description"><?php esc_html_e( 'Used in schema.org offers. Leave blank for free.', 'verdix-reviews' ); ?></p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="verdix_review_author_name"><?php esc_html_e( 'Author name override', 'verdix-reviews' ); ?></label>
			</th>
			<td>
				<input type="text" id="verdix_review_author_name" name="verdix_review_author_name"
					value="<?php echo esc_attr( $data['author_name'] ); ?>" class="regular-text"
					placeholder="<?php esc_attr_e( 'Leave blank to use default from settings', 'verdix-reviews' ); ?>">
			</td>
		</tr>
	</table>

<hr>
	<h4><?php esc_html_e( 'Schema check', 'verdix-reviews' ); ?></h4>
	<?php verdix_schema_check_render( $post->ID ); ?>
	<p class="description"><?php esc_html_e( 'Based on the last saved version.', 'verdix-reviews' ); ?></p>

</div>
