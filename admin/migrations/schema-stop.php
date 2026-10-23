<?php
defined( 'ABSPATH' ) || exit;

function verdix_schema_stop_enabled( $source ) {
	return in_array( $source, (array) get_option( 'verdix_migration_stop_schema', array() ), true );
}

function verdix_schema_stop_applies( $source ) {
	if ( ! is_singular() || ! verdix_schema_stop_enabled( $source ) ) {
		return false;
	}

	$review = verdix_get_review( get_queried_object_id() );
	return $review && ! empty( $review['rating'] );
}

function verdix_stop_ultimate_blocks_schema( $schema ) {
	return verdix_schema_stop_applies( 'ultimate_blocks' ) ? '' : $schema;
}
add_filter( 'ultimate-blocks/filter/review_schema', 'verdix_stop_ultimate_blocks_schema' );

function verdix_strip_ultimate_blocks_empty_schema( $html ) {
	if ( ! verdix_schema_stop_applies( 'ultimate_blocks' ) ) {
		return $html;
	}
	return preg_replace( '#<script type="application/ld\+json">\s*</script>#', '', $html );
}
add_filter( 'render_block_ub/review', 'verdix_strip_ultimate_blocks_empty_schema' );

function verdix_stop_rank_math_schema( $data ) {
	if ( ! is_array( $data ) || ! verdix_schema_stop_applies( 'rank_math' ) ) {
		return $data;
	}

	foreach ( $data as $key => $entity ) {
		$is_schema = 0 === strpos( (string) $key, 'schema-' ) || 'richSnippet' === $key;
		if ( $is_schema && is_array( $entity ) && isset( $entity['review'] ) ) {
			unset( $data[ $key ] );
		}
	}

	return $data;
}
add_filter( 'rank_math/json_ld', 'verdix_stop_rank_math_schema', 11 );
