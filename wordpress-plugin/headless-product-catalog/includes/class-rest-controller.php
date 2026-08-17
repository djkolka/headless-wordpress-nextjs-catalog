<?php

declare(strict_types=1);

namespace Headless_Catalog;

final class REST_Controller {
	private const NAMESPACE = 'catalog/v1';

	public static function register_hooks(): void {
		add_action( 'rest_api_init', array( self::class, 'register_routes' ) );
	}

	public static function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/products',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'collection' ),
				'permission_callback' => '__return_true',
				'args'                => self::collection_args(),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/products/(?P<slug>[a-z0-9-]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'single' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'slug'    => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => array( self::class, 'sanitize_slug' ),
						'validate_callback' => array( self::class, 'validate_slug' ),
					),
					'preview' => array(
						'type'              => 'boolean',
						'default'           => false,
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
				),
			)
		);
	}

	/** @return array<string,array<string,mixed>> */
	public static function collection_args(): array {
		return array(
			'page'         => array(
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => array( self::class, 'validate_positive' ),
			),
			'per_page'     => array(
				'type'              => 'integer',
				'default'           => 12,
				'sanitize_callback' => 'absint',
				'validate_callback' => array( self::class, 'validate_per_page' ),
			),
			'search'       => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => array( self::class, 'validate_search' ),
			),
			'category'     => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => array( self::class, 'sanitize_slug' ),
				'validate_callback' => array( self::class, 'validate_optional_slug' ),
			),
			'manufacturer' => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => array( self::class, 'sanitize_slug' ),
				'validate_callback' => array( self::class, 'validate_optional_slug' ),
			),
			'application'  => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => array( self::class, 'sanitize_slug' ),
				'validate_callback' => array( self::class, 'validate_optional_slug' ),
			),
			'sort'         => array(
				'type'              => 'string',
				'default'           => 'date',
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => array( self::class, 'validate_sort' ),
			),
			'order'        => array(
				'type'              => 'string',
				'default'           => 'desc',
				'sanitize_callback' => array( self::class, 'sanitize_order' ),
				'validate_callback' => array( self::class, 'validate_order' ),
			),
		);
	}

	public static function collection( \WP_REST_Request $request ): \WP_REST_Response {
		$version = (int) get_option( 'hpcatalog_cache_version', 1 );
		$key     = 'hpcatalog_' . md5( $version . ':' . wp_json_encode( $request->get_params() ) );
		$cached  = get_transient( $key );
		if ( is_array( $cached ) ) {
			return new \WP_REST_Response( $cached, 200, array( 'X-Catalog-Cache' => 'HIT' ) );
		}

		$query_args = self::build_query_args( $request );
		$query      = new \WP_Query( $query_args );
		$normalizer = new Product_Normalizer();
		$data       = array_map( static fn( \WP_Post $post ): array => $normalizer->normalize( $post ), $query->posts );
		$payload    = array(
			'data'       => $data,
			'pagination' => array(
				'page'       => (int) $query_args['paged'],
				'perPage'    => (int) $query_args['posts_per_page'],
				'total'      => (int) $query->found_posts,
				'totalPages' => (int) $query->max_num_pages,
			),
		);
		set_transient( $key, $payload, 5 * MINUTE_IN_SECONDS );
		return new \WP_REST_Response( $payload, 200, array( 'X-Catalog-Cache' => 'MISS' ) );
	}

	public static function single( \WP_REST_Request $request ) {
		$post = get_page_by_path( (string) $request['slug'], OBJECT, Product_Post_Type::POST_TYPE );
		if ( ! $post instanceof \WP_Post ) {
			return self::error( 'catalog_product_not_found', __( 'Product not found.', 'headless-product-catalog' ), 404 );
		}

		$is_preview = (bool) $request->get_param( 'preview' );
		if ( 'publish' !== $post->post_status ) {
			if ( ! $is_preview || ! is_user_logged_in() || ! current_user_can( 'edit_post', $post->ID ) ) {
				return self::error( 'catalog_product_not_found', __( 'Product not found.', 'headless-product-catalog' ), 404 );
			}
		}

		return rest_ensure_response( ( new Product_Normalizer() )->normalize( $post ) );
	}

	/** @return array<string,mixed> */
	public static function build_query_args( \WP_REST_Request $request ): array {
		$sort_map = array(
			'date'     => 'date',
			'modified' => 'modified',
			'title'    => 'title',
			'price'    => 'meta_value_num',
		);
		$sort     = (string) $request->get_param( 'sort' );
		$args     = array(
			'post_type'      => Product_Post_Type::POST_TYPE,
			'post_status'    => 'publish',
			'paged'          => (int) $request->get_param( 'page' ),
			'posts_per_page' => (int) $request->get_param( 'per_page' ),
			's'              => (string) $request->get_param( 'search' ),
			'orderby'        => $sort_map[ $sort ],
			'order'          => strtoupper( (string) $request->get_param( 'order' ) ),
			'no_found_rows'  => false,
		);
		if ( 'price' === $sort ) {
			$args['meta_key'] = Field_Repository::PREFIX . 'price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		}

		$tax_query = array();
		foreach ( array(
			'category'     => 'categories',
			'manufacturer' => 'manufacturers',
			'application'  => 'applications',
		) as $parameter => $taxonomy_key ) {
			$value = (string) $request->get_param( $parameter );
			if ( '' !== $value ) {
				$tax_query[] = array(
					'taxonomy' => Product_Post_Type::TAXONOMIES[ $taxonomy_key ],
					'field'    => 'slug',
					'terms'    => array( $value ),
				);
			}
		}
		if ( $tax_query ) {
			$args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}
		return $args;
	}

	public static function validate_positive( $value ): bool {
		return is_numeric( $value ) && (int) $value >= 1; }
	public static function validate_per_page( $value ): bool {
		return is_numeric( $value ) && (int) $value >= 1 && (int) $value <= 50; }
	public static function validate_search( $value ): bool {
		return is_string( $value ) && strlen( $value ) <= 100; }
	public static function validate_slug( $value ): bool {
		return is_string( $value ) && (bool) preg_match( '/^[a-z0-9-]+$/', $value ); }
	public static function validate_optional_slug( $value ): bool {
		return '' === $value || self::validate_slug( $value ); }
	public static function validate_sort( $value ): bool {
		return in_array( $value, array( 'date', 'modified', 'title', 'price' ), true ); }
	public static function validate_order( $value ): bool {
		return in_array( strtolower( (string) $value ), array( 'asc', 'desc' ), true ); }
	public static function sanitize_slug( $value ): string {
		return sanitize_title( (string) $value ); }
	public static function sanitize_order( $value ): string {
		return strtolower( sanitize_key( (string) $value ) ); }

	private static function error( string $code, string $message, int $status ): \WP_Error {
		return new \WP_Error( $code, $message, array( 'status' => $status ) );
	}
}
