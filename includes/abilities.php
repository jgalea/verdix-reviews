<?php
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wp_register_ability' ) ) {
	return;
}

function verdix_register_ability_category() {
	if ( ! function_exists( 'wp_register_ability_category' ) ) {
		return;
	}

	wp_register_ability_category(
		'verdix',
		array(
			'label'       => __( 'Verdix Reviews', 'verdix-reviews' ),
			'description' => __( 'Read and write review boxes and their structured data.', 'verdix-reviews' ),
		)
	);
}
add_action( 'wp_abilities_api_categories_init', 'verdix_register_ability_category' );

function verdix_ability_review_schema() {
	$properties = array();
	foreach ( verdix_get_defaults() as $key => $default ) {
		if ( is_array( $default ) ) {
			$properties[ $key ] = array( 'type' => 'array' );
		} elseif ( is_numeric( $default ) ) {
			$properties[ $key ] = array( 'type' => 'number' );
		} else {
			$properties[ $key ] = array( 'type' => 'string' );
		}
	}
	$properties['pros']        = array(
		'type'  => 'array',
		'items' => array( 'type' => 'string' ),
	);
	$properties['cons']        = $properties['pros'];
	$properties['type_fields'] = array( 'type' => 'object' );

	return array(
		'type'                 => 'object',
		'properties'           => $properties,
		'additionalProperties' => true,
	);
}

function verdix_ability_can_read( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return true;
	}
	return 'publish' === $post->post_status || current_user_can( 'read_post', $post->ID );
}

function verdix_ability_can_edit( $input ) {
	$post_id = isset( $input['post_id'] ) ? absint( $input['post_id'] ) : 0;
	return $post_id && current_user_can( 'edit_post', $post_id );
}

function verdix_ability_not_found() {
	return new WP_Error( 'verdix_not_found', __( 'Post not found.', 'verdix-reviews' ), array( 'status' => 404 ) );
}

function verdix_ability_get_review( $input ) {
	$post_id = absint( $input['post_id'] );
	if ( ! get_post( $post_id ) ) {
		return verdix_ability_not_found();
	}

	$review = verdix_get_review( $post_id );

	return array(
		'post_id' => $post_id,
		'review'  => $review,
		'schema'  => $review ? verdix_build_schema( $post_id, $review ) : null,
	);
}

function verdix_ability_list_reviews( $input ) {
	$input = is_array( $input ) ? $input : array();

	$meta_query = array(
		array(
			'key'     => '_verdix_review',
			'value'   => '',
			'compare' => '!=',
		),
	);
	if ( isset( $input['min_rating'] ) ) {
		$meta_query[] = array(
			'key'     => '_verdix_rating_sort',
			'value'   => (float) $input['min_rating'],
			'type'    => 'DECIMAL(3,1)',
			'compare' => '>=',
		);
	}
	if ( ! empty( $input['schema_type'] ) ) {
		$meta_query[] = array(
			'key'     => '_verdix_review',
			'value'   => '"schema_type":' . wp_json_encode( $input['schema_type'] ),
			'compare' => 'LIKE',
		);
	}

	$query = new WP_Query(
		array(
			'post_type'              => apply_filters( 'verdix_post_types', array( 'post', 'page' ) ),
			'post_status'            => current_user_can( 'edit_posts' ) ? 'any' : 'publish',
			'posts_per_page'         => isset( $input['per_page'] ) ? absint( $input['per_page'] ) : 20,
			'paged'                  => isset( $input['page'] ) ? absint( $input['page'] ) : 1,
			'meta_query'             => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => false,
			'update_post_term_cache' => false,
		)
	);

	$items = array();
	foreach ( $query->posts as $post ) {
		$items[] = array(
			'post_id' => $post->ID,
			'title'   => get_the_title( $post ),
			'status'  => $post->post_status,
			'review'  => verdix_get_review( $post->ID ),
		);
	}

	return array(
		'items'       => $items,
		'total'       => (int) $query->found_posts,
		'total_pages' => (int) $query->max_num_pages,
	);
}

function verdix_ability_update_review( $input ) {
	$post_id = absint( $input['post_id'] );
	if ( ! get_post( $post_id ) ) {
		return verdix_ability_not_found();
	}

	$review = verdix_update_review( $post_id, $input['review'] );

	return array(
		'post_id' => $post_id,
		'review'  => $review,
		'schema'  => $review ? verdix_build_schema( $post_id, $review ) : null,
	);
}

function verdix_ability_delete_review( $input ) {
	$post_id = absint( $input['post_id'] );
	if ( ! get_post( $post_id ) ) {
		return verdix_ability_not_found();
	}

	delete_post_meta( $post_id, '_verdix_review' );
	delete_post_meta( $post_id, '_verdix_rating_sort' );

	return array(
		'post_id' => $post_id,
		'deleted' => true,
	);
}

function verdix_register_abilities() {
	$post_id_schema = array(
		'type'    => 'integer',
		'minimum' => 1,
	);
	$review_schema  = verdix_ability_review_schema();
	$result_schema  = array(
		'type'       => 'object',
		'properties' => array(
			'post_id' => array( 'type' => 'integer' ),
			'review'  => array_merge( $review_schema, array( 'type' => array( 'object', 'null' ) ) ),
			'schema'  => array( 'type' => array( 'object', 'array', 'null' ) ),
		),
	);

	wp_register_ability(
		'verdix/get-review',
		array(
			'label'               => __( 'Get review', 'verdix-reviews' ),
			'description'         => __( 'Returns the review box data and generated JSON-LD schema for a post.', 'verdix-reviews' ),
			'category'            => 'verdix',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array( 'post_id' => $post_id_schema ),
				'required'             => array( 'post_id' ),
				'additionalProperties' => false,
			),
			'output_schema'       => $result_schema,
			'execute_callback'    => 'verdix_ability_get_review',
			'permission_callback' => function ( $input ) {
				return verdix_ability_can_read( isset( $input['post_id'] ) ? absint( $input['post_id'] ) : 0 );
			},
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array( 'readonly' => true ),
			),
		)
	);

	wp_register_ability(
		'verdix/list-reviews',
		array(
			'label'               => __( 'List reviews', 'verdix-reviews' ),
			'description'         => __( 'Lists posts that have a review box, with optional minimum rating (0-5 scale) and schema type filters.', 'verdix-reviews' ),
			'category'            => 'verdix',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'page'        => array(
						'type'    => 'integer',
						'minimum' => 1,
						'default' => 1,
					),
					'per_page'    => array(
						'type'    => 'integer',
						'minimum' => 1,
						'maximum' => 100,
						'default' => 20,
					),
					'min_rating'  => array(
						'type'    => 'number',
						'minimum' => 0,
						'maximum' => 5,
					),
					'schema_type' => array(
						'type' => 'string',
						'enum' => array_keys( verdix_get_schema_types() ),
					),
				),
				'additionalProperties' => false,
				'default'              => array(),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'items'       => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'post_id' => array( 'type' => 'integer' ),
								'title'   => array( 'type' => 'string' ),
								'status'  => array( 'type' => 'string' ),
								'review'  => $review_schema,
							),
						),
					),
					'total'       => array( 'type' => 'integer' ),
					'total_pages' => array( 'type' => 'integer' ),
				),
			),
			'execute_callback'    => 'verdix_ability_list_reviews',
			'permission_callback' => '__return_true',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array( 'readonly' => true ),
			),
		)
	);

	wp_register_ability(
		'verdix/update-review',
		array(
			'label'               => __( 'Update review', 'verdix-reviews' ),
			'description'         => __( 'Merges the given fields into a post\'s review box. Omitted fields keep their saved values.', 'verdix-reviews' ),
			'category'            => 'verdix',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'post_id' => $post_id_schema,
					'review'  => array_merge( $review_schema, array( 'minProperties' => 1 ) ),
				),
				'required'             => array( 'post_id', 'review' ),
				'additionalProperties' => false,
			),
			'output_schema'       => $result_schema,
			'execute_callback'    => 'verdix_ability_update_review',
			'permission_callback' => 'verdix_ability_can_edit',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => true,
				),
			),
		)
	);

	wp_register_ability(
		'verdix/delete-review',
		array(
			'label'               => __( 'Delete review', 'verdix-reviews' ),
			'description'         => __( 'Removes the review box data from a post.', 'verdix-reviews' ),
			'category'            => 'verdix',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array( 'post_id' => $post_id_schema ),
				'required'             => array( 'post_id' ),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'post_id' => array( 'type' => 'integer' ),
					'deleted' => array( 'type' => 'boolean' ),
				),
			),
			'execute_callback'    => 'verdix_ability_delete_review',
			'permission_callback' => 'verdix_ability_can_edit',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'    => false,
					'destructive' => true,
					'idempotent'  => true,
				),
			),
		)
	);
}
add_action( 'wp_abilities_api_init', 'verdix_register_abilities' );
