<?php
defined( 'ABSPATH' ) || exit;

function verdix_get_schema_output_modes() {
	return array(
		'auto'       => __( 'Auto (join the SEO plugin graph if one is active)', 'verdix-reviews' ),
		'standalone' => __( 'Standalone script tag', 'verdix-reviews' ),
		'integrate'  => __( 'Integrate with the SEO plugin graph', 'verdix-reviews' ),
		'off'        => __( 'Off', 'verdix-reviews' ),
	);
}

function verdix_detect_seo_plugin() {
	if ( defined( 'WPSEO_VERSION' ) ) {
		return 'yoast';
	}
	if ( class_exists( 'RankMath' ) && class_exists( '\RankMath\Helper' ) && \RankMath\Helper::is_module_active( 'rich-snippet' ) ) {
		return 'rankmath';
	}
	if ( function_exists( 'aioseo' ) && aioseo()->schema->helpers->isEnabled() ) {
		return 'aioseo';
	}
	return '';
}

function verdix_get_schema_mode() {
	$options = get_option( 'verdix_settings', array() );
	$setting = isset( $options['schema_output'] ) && isset( verdix_get_schema_output_modes()[ $options['schema_output'] ] )
		? $options['schema_output']
		: 'auto';
	$plugin  = verdix_detect_seo_plugin();

	if ( 'auto' === $setting ) {
		$mode = '' !== $plugin ? 'integrate' : 'standalone';
	} elseif ( 'integrate' === $setting ) {
		$mode = '' !== $plugin ? 'integrate' : 'standalone';
	} else {
		$mode = $setting;
	}

	/**
	 * Filter the resolved schema output mode.
	 *
	 * @param string $mode    One of standalone, integrate, off.
	 * @param string $setting The saved setting (auto, standalone, integrate, off).
	 * @param string $plugin  Detected SEO plugin: yoast, rankmath, aioseo or empty.
	 */
	$mode = apply_filters( 'verdix_schema_integration', $mode, $setting, $plugin );

	return in_array( $mode, array( 'standalone', 'integrate', 'off' ), true ) ? $mode : 'standalone';
}

function verdix_graph_node_types( $node ) {
	if ( ! is_array( $node ) || empty( $node['@type'] ) ) {
		return array();
	}
	return array_map( 'strval', (array) $node['@type'] );
}

function verdix_graph_find_id( array $nodes, array $types, $name = null ) {
	foreach ( $nodes as $node ) {
		if ( empty( $node['@id'] ) || ! array_intersect( $types, verdix_graph_node_types( $node ) ) ) {
			continue;
		}
		if ( null !== $name && ( empty( $node['name'] ) || $node['name'] !== $name ) ) {
			continue;
		}
		return $node['@id'];
	}
	return '';
}

function verdix_graph_find_webpage_id( array $nodes ) {
	$types = array( 'WebPage', 'ItemPage', 'CollectionPage', 'AboutPage', 'ContactPage', 'FAQPage', 'ProfilePage', 'SearchResultsPage', 'CheckoutPage' );
	return verdix_graph_find_id( $nodes, $types );
}

/**
 * Build the Verdix graph node for the current post and fold it into $nodes.
 *
 * Merges into an existing node of the same type (e.g. a WooCommerce or Rank
 * Math Product), skips if a Review node is already present, otherwise appends.
 *
 * @param array $nodes Graph nodes (list of arrays).
 * @return array
 */
function verdix_merge_into_graph( array $nodes ) {
	if ( ! is_singular() || 'integrate' !== verdix_get_schema_mode() ) {
		return $nodes;
	}

	$post_id = get_the_ID();
	$review  = verdix_get_schema_review( $post_id );
	if ( ! $review ) {
		return $nodes;
	}

	$schema = verdix_build_schema( $post_id, $review );
	if ( empty( $schema ) || empty( $schema['@type'] ) ) {
		return $nodes;
	}
	unset( $schema['@context'] );

	foreach ( $nodes as $node ) {
		if ( in_array( 'Review', verdix_graph_node_types( $node ), true ) ) {
			return $nodes;
		}
	}

	$webpage_id = verdix_graph_find_webpage_id( $nodes );

	if ( ! empty( $schema['review']['author']['name'] ) ) {
		$person_id = verdix_graph_find_id( $nodes, array( 'Person' ), $schema['review']['author']['name'] );
		if ( '' !== $person_id ) {
			$schema['review']['author'] = array( '@id' => $person_id );
		}
	}

	$types = (array) $schema['@type'];
	foreach ( $nodes as $i => $node ) {
		if ( ! array_intersect( $types, verdix_graph_node_types( $node ) ) || ! isset( $node['@id'] ) ) {
			continue;
		}
		foreach ( array( 'review', 'aggregateRating' ) as $key ) {
			if ( empty( $node[ $key ] ) && ! empty( $schema[ $key ] ) ) {
				$nodes[ $i ][ $key ] = $schema[ $key ];
			}
		}
		return $nodes;
	}

	$schema['@id'] = get_permalink( $post_id ) . '#verdix-review';
	if ( '' !== $webpage_id ) {
		$schema['mainEntityOfPage'] = array( '@id' => $webpage_id );
	}

	$nodes[] = $schema;
	return $nodes;
}

function verdix_yoast_schema_graph( $graph ) {
	return is_array( $graph ) ? verdix_merge_into_graph( $graph ) : $graph;
}
add_filter( 'wpseo_schema_graph', 'verdix_yoast_schema_graph', 20 );

function verdix_rankmath_json_ld( $data ) {
	if ( ! is_array( $data ) ) {
		return $data;
	}
	$before = count( $data );
	$merged = verdix_merge_into_graph( array_values( $data ) );
	if ( count( $merged ) > $before ) {
		$data['verdix-review'] = end( $merged );
	} else {
		$keys = array_keys( $data );
		foreach ( $merged as $i => $node ) {
			$data[ $keys[ $i ] ] = $node;
		}
	}
	return $data;
}
add_filter( 'rank_math/json_ld', 'verdix_rankmath_json_ld', 110 );

function verdix_aioseo_schema_output( $graphs ) {
	return is_array( $graphs ) ? verdix_merge_into_graph( $graphs ) : $graphs;
}
add_filter( 'aioseo_schema_output', 'verdix_aioseo_schema_output', 20 );
