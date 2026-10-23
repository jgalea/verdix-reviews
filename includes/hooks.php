<?php
defined( 'ABSPATH' ) || exit;

/**
 * Get all registered rating types.
 *
 * Returns an associative array keyed by type slug. Each entry contains:
 *   label  (string)   Human-readable label.
 *   max    (float)    Maximum rating value.
 *   step   (float)    Minimum increment.
 *   render (callable) Function name that renders the display HTML.
 *   format (string)   sprintf-compatible format string for the value label.
 *
 * @return array<string, array>
 */
function verdix_get_rating_types() {
	$types = array(
		'star' => array(
			'label'  => __( 'Star (0-5)', 'verdix-reviews' ),
			'max'    => 5,
			'step'   => 0.5,
			'render' => 'verdix_render_stars_display',
			'format' => '%s/5',
		),
	);

	return apply_filters( 'verdix_rating_types', $types );
}

/**
 * Get all registered box styles.
 *
 * @return array<string, array>
 */
function verdix_get_styles() {
	$styles = array(
		'default' => array(
			'label' => __( 'Default', 'verdix-reviews' ),
		),
	);

	return apply_filters( 'verdix_styles', $styles );
}

/**
 * Get all registered migration sources.
 *
 * Returns an associative array keyed by source slug. Each entry contains:
 *   label    (string)   Human-readable label.
 *   detect   (callable) Returns an array of WP_Post objects to migrate.
 *   migrate  (callable) Migrates a single post by ID.
 *   columns  (array)    Column definitions for the migration table.
 *   row_data (callable) Returns per-row display data for a given post.
 *
 * @return array<string, array>
 */
function verdix_get_migration_sources() {
	$sources = array(
		'wp_review_pro' => array(
			'label'    => __( 'WP Review Pro', 'verdix-reviews' ),
			'detect'   => 'verdix_get_migratable_posts',
			'migrate'  => 'verdix_migrate_post_wp_review_pro',
			'columns'  => array( 'type', 'rating', 'schema', 'pros', 'cons' ),
			'row_data' => 'verdix_migration_row_wp_review_pro',
		),
	);

	return apply_filters( 'verdix_migration_sources', $sources );
}

/**
 * Count posts migratable across every registered source.
 *
 * @return int
 */
function verdix_count_all_migratable() {
	$total = 0;
	foreach ( verdix_get_migration_sources() as $source ) {
		if ( isset( $source['detect'] ) && is_callable( $source['detect'] ) ) {
			$posts  = call_user_func( $source['detect'] );
			$total += is_array( $posts ) ? count( $posts ) : 0;
		}
	}
	return $total;
}

/**
 * Build the editor configuration array for the block editor JS.
 *
 * @return array
 */
function verdix_get_editor_config() {
	$rating_types_config = array();
	foreach ( verdix_get_rating_types() as $slug => $type ) {
		$rating_types_config[] = array(
			'value'  => $slug,
			'label'  => $type['label'],
			'max'    => $type['max'],
			'step'   => $type['step'],
			'format' => $type['format'],
		);
	}

	$styles_config = array();
	foreach ( verdix_get_styles() as $slug => $style ) {
		$styles_config[] = array(
			'value' => $slug,
			'label' => $style['label'],
		);
	}

	$schema_types_config = array();
	foreach ( verdix_get_schema_types() as $slug => $label ) {
		$eligibility           = verdix_schema_eligibility( $slug );
		$schema_types_config[] = array(
			'value'       => $slug,
			'label'       => $label,
			'eligibility' => $eligibility['status'],
			'note'        => $eligibility['message'],
		);
	}

	$type_fields_config = array();
	foreach ( verdix_get_type_fields() as $type_key => $fields ) {
		$list = array();
		foreach ( $fields as $field_key => $def ) {
			$list[] = array(
				'key'   => $field_key,
				'label' => isset( $def['label'] ) ? $def['label'] : $field_key,
				'type'  => isset( $def['type'] ) ? $def['type'] : 'text',
			);
		}
		$type_fields_config[ $type_key ] = $list;
	}

	$disclosure_config = array();
	foreach ( verdix_get_disclosure_types() as $slug => $label ) {
		$disclosure_config[] = array(
			'value' => $slug,
			'label' => $label,
		);
	}

	$config = array(
		'disclosureTypes' => $disclosure_config,
		'disclosurePresets' => verdix_get_disclosure_presets(),
		'katinaActive'    => verdix_katina_active(),
		'ratingTypes' => $rating_types_config,
		'styles'      => $styles_config,
		'schemaTypes' => $schema_types_config,
		'typeFields'  => $type_fields_config,
	);

	return apply_filters( 'verdix_editor_config', $config );
}
