<?php

declare(strict_types=1);

namespace Headless_Catalog;

final class ACF_Fields {
	public static function register_hooks(): void {
		add_action( 'acf/init', array( self::class, 'register' ) );
		add_action( 'admin_notices', array( self::class, 'missing_notice' ) );
	}

	public static function missing_notice(): void {
		if ( function_exists( 'acf_add_local_field_group' ) || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>';
		echo esc_html__( 'Headless Product Catalog: Advanced Custom Fields is required for the product editing UI. The REST API and WP-CLI seed command remain available using registered metadata.', 'headless-product-catalog' );
		echo '</p></div>';
	}

	public static function register(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			array(
				'key'      => 'group_hpcatalog_product',
				'title'    => __( 'Product Details', 'headless-product-catalog' ),
				'fields'   => array(
					self::field( 'sku', 'SKU', 'text', true ),
					self::field( 'short_description', 'Short description', 'textarea' ),
					array_merge(
						self::field( 'price', 'Price', 'number', true ),
						array(
							'min'  => 0,
							'step' => '0.01',
						)
					),
					array_merge(
						self::field( 'currency', 'Currency', 'text', true ),
						array(
							'default_value' => 'USD',
							'maxlength'     => 3,
						)
					),
					array(
						'key'        => 'field_hpcatalog_specifications',
						'name'       => 'hpcatalog_specifications',
						'label'      => 'Specifications',
						'type'       => 'repeater',
						'layout'     => 'table',
						'sub_fields' => array(
							self::sub_field( 'spec_label', 'Label' ),
							self::sub_field( 'spec_value', 'Value' ),
						),
					),
					array_merge(
						self::field( 'gallery', 'Product gallery', 'gallery' ),
						array(
							'return_format' => 'id',
							'preview_size'  => 'medium',
						)
					),
					array_merge( self::field( 'datasheet_url', 'Datasheet URL', 'url' ), array( 'placeholder' => 'https://example.test/datasheet.pdf' ) ),
					array_merge(
						self::field( 'related_products', 'Related products', 'relationship' ),
						array(
							'post_type'     => array( Product_Post_Type::POST_TYPE ),
							'return_format' => 'id',
						)
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => Product_Post_Type::POST_TYPE,
						),
					),
				),
			)
		);
	}

	private static function field( string $name, string $label, string $type, bool $required = false ): array {
		return array(
			'key'      => 'field_hpcatalog_' . $name,
			'name'     => 'hpcatalog_' . $name,
			'label'    => $label,
			'type'     => $type,
			'required' => $required ? 1 : 0,
		);
	}

	private static function sub_field( string $name, string $label ): array {
		return array(
			'key'      => 'field_hpcatalog_' . $name,
			'name'     => $name,
			'label'    => $label,
			'type'     => 'text',
			'required' => 1,
		);
	}
}
