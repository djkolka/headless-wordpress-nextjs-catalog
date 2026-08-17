<?php

declare(strict_types=1);

namespace Headless_Catalog;

final class Product_Normalizer {
	private Field_Repository $fields;

	public function __construct( ?Field_Repository $fields = null ) {
		$this->fields = $fields ?? new Field_Repository();
	}

	/** @return array<string,mixed> */
	public function normalize( \WP_Post $post, bool $include_related = true ): array {
		$amount   = max( 0.0, (float) $this->fields->get( $post->ID, 'price' ) );
		$currency = strtoupper( sanitize_key( (string) $this->fields->get( $post->ID, 'currency' ) ) );
		$currency = preg_match( '/^[A-Z]{3}$/', $currency ) ? $currency : 'USD';

		return array(
			'id'              => $post->ID,
			'slug'            => $post->post_name,
			'title'           => get_the_title( $post ),
			'status'          => $post->post_status,
			'excerpt'         => $this->excerpt( $post ),
			'content'         => wp_kses_post( apply_filters( 'the_content', $post->post_content ) ),
			'sku'             => sanitize_text_field( (string) $this->fields->get( $post->ID, 'sku' ) ),
			'price'           => array(
				'amount'    => $amount,
				'currency'  => $currency,
				'formatted' => $this->format_price( $amount, $currency ),
			),
			'featuredImage'   => $this->image( (int) get_post_thumbnail_id( $post ) ),
			'gallery'         => $this->gallery( $this->fields->get( $post->ID, 'gallery' ) ),
			'specifications'  => $this->specifications( $this->fields->get( $post->ID, 'specifications' ) ),
			'datasheetUrl'    => $this->url_or_null( $this->fields->get( $post->ID, 'datasheet_url' ) ),
			'taxonomies'      => array(
				'categories'    => $this->terms( $post->ID, Product_Post_Type::TAXONOMIES['categories'] ),
				'manufacturers' => $this->terms( $post->ID, Product_Post_Type::TAXONOMIES['manufacturers'] ),
				'applications'  => $this->terms( $post->ID, Product_Post_Type::TAXONOMIES['applications'] ),
			),
			'relatedProducts' => $include_related ? $this->related( $this->fields->get( $post->ID, 'related_products' ) ) : array(),
			'publishedAt'     => get_post_time( 'c', true, $post ),
			'modifiedAt'      => get_post_modified_time( 'c', true, $post ),
		);
	}

	private function excerpt( \WP_Post $post ): string {
		$custom = (string) $this->fields->get( $post->ID, 'short_description' );
		if ( '' !== $custom ) {
			return sanitize_text_field( $custom );
		}

		$excerpt = '' !== $post->post_excerpt
			? $post->post_excerpt
			: wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 );
		return sanitize_text_field( $excerpt );
	}

	private function format_price( float $amount, string $currency ): string {
		$symbols = array(
			'USD' => '$',
			'EUR' => '€',
			'GBP' => '£',
		);
		return ( $symbols[ $currency ] ?? $currency . ' ' ) . number_format_i18n( $amount, 2 );
	}

	/** @return array<string,mixed>|null */
	private function image( int $id ): ?array {
		$source = $id ? wp_get_attachment_image_src( $id, 'full' ) : false;
		if ( ! $source ) {
			return null;
		}
		return array(
			'url'    => esc_url_raw( $source[0] ),
			'alt'    => sanitize_text_field( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ),
			'width'  => (int) $source[1],
			'height' => (int) $source[2],
		);
	}

	/** @param mixed $value @return array<int,array<string,mixed>> */
	private function gallery( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}
		return array_values( array_filter( array_map( fn( $item ) => $this->image( is_array( $item ) ? (int) ( $item['ID'] ?? 0 ) : (int) $item ), $value ) ) );
	}

	/** @param mixed $value @return array<int,array{label:string,value:string}> */
	private function specifications( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}
		$result = array();
		foreach ( $value as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$label = sanitize_text_field( (string) ( $row['spec_label'] ?? $row['label'] ?? '' ) );
			$entry = sanitize_text_field( (string) ( $row['spec_value'] ?? $row['value'] ?? '' ) );
			if ( '' !== $label && '' !== $entry ) {
				$result[] = array(
					'label' => $label,
					'value' => $entry,
				);
			}
		}
		return $result;
	}

	/** @return array<int,array{id:int,slug:string,name:string}> */
	private function terms( int $post_id, string $taxonomy ): array {
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( ! is_array( $terms ) ) {
			return array();
		}
		return array_map(
			static fn( \WP_Term $term ): array => array(
				'id'   => $term->term_id,
				'slug' => $term->slug,
				'name' => $term->name,
			),
			$terms
		);
	}

	/** @param mixed $value @return array<int,array<string,mixed>> */
	private function related( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}
		$result = array();
		foreach ( array_slice( $value, 0, 8 ) as $item ) {
			$post = get_post( is_object( $item ) ? $item->ID : (int) $item );
			if ( $post instanceof \WP_Post && Product_Post_Type::POST_TYPE === $post->post_type && 'publish' === $post->post_status ) {
				$normalized = $this->normalize( $post, false );
				$result[]   = array_intersect_key( $normalized, array_flip( array( 'id', 'slug', 'title', 'excerpt', 'sku', 'price', 'featuredImage' ) ) );
			}
		}
		return $result;
	}

	/** @param mixed $value */
	private function url_or_null( $value ): ?string {
		$url = esc_url_raw( (string) $value, array( 'http', 'https' ) );
		return '' === $url ? null : $url;
	}
}
