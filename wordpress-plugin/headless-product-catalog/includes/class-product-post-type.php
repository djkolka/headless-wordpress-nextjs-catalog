<?php

declare(strict_types=1);

namespace Headless_Catalog;

final class Product_Post_Type {
	public const POST_TYPE  = 'hpc_product';
	public const TAXONOMIES = array(
		'categories'    => 'hpc_product_category',
		'manufacturers' => 'hpc_manufacturer',
		'applications'  => 'hpc_application',
	);

	public static function register_hooks(): void {
		add_action( 'init', array( self::class, 'register' ) );
	}

	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'          => __( 'Catalog Products', 'headless-product-catalog' ),
					'singular_name' => __( 'Catalog Product', 'headless-product-catalog' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_rest' => false,
				'menu_icon'    => 'dashicons-products',
				'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
				'has_archive'  => false,
				'rewrite'      => false,
			)
		);

		$labels = array(
			'categories'    => array( 'Product Categories', 'Product Category' ),
			'manufacturers' => array( 'Manufacturers', 'Manufacturer' ),
			'applications'  => array( 'Applications', 'Application' ),
		);

		foreach ( self::TAXONOMIES as $key => $taxonomy ) {
			register_taxonomy(
				$taxonomy,
				self::POST_TYPE,
				array(
					'labels'       => array(
						'name'          => $labels[ $key ][0],
						'singular_name' => $labels[ $key ][1],
					),
					'public'       => false,
					'show_ui'      => true,
					'show_in_rest' => false,
					'hierarchical' => 'categories' === $key,
					'rewrite'      => false,
				)
			);
		}
	}
}
