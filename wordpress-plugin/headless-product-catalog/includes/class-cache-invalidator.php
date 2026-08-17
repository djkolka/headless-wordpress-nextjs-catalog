<?php

declare(strict_types=1);

namespace Headless_Catalog;

final class Cache_Invalidator {
	private static array $handled = array();

	public static function register_hooks(): void {
		add_action( 'save_post_' . Product_Post_Type::POST_TYPE, array( self::class, 'invalidate' ), 20, 3 );
		add_action( 'trashed_post', array( self::class, 'handle_post_id' ) );
		add_action( 'before_delete_post', array( self::class, 'handle_post_id' ) );
		add_action( 'set_object_terms', array( self::class, 'handle_terms' ), 10, 6 );
	}

	public static function handle_post_id( int $post_id ): void {
		$post = get_post( $post_id );
		if ( $post instanceof \WP_Post && Product_Post_Type::POST_TYPE === $post->post_type ) {
			self::invalidate( $post_id, $post, true );
		}
	}

	public static function handle_terms( int $object_id, $terms, $tt_ids, string $taxonomy ): void {
		if ( in_array( $taxonomy, Product_Post_Type::TAXONOMIES, true ) ) {
			self::handle_post_id( $object_id );
		}
	}

	public static function invalidate( int $post_id, \WP_Post $post, bool $update ): void {
		unset( $update );
		if ( wp_is_post_revision( $post_id ) || isset( self::$handled[ $post_id ] ) ) {
			return;
		}
		self::$handled[ $post_id ] = true;
		update_option( 'hpcatalog_cache_version', time(), false );
		self::send_webhook( $post );
	}

	private static function send_webhook( \WP_Post $post ): void {
		$secret = defined( 'HPCATALOG_REVALIDATION_SECRET' ) ? (string) HPCATALOG_REVALIDATION_SECRET : (string) getenv( 'HPCATALOG_REVALIDATION_SECRET' );
		$url    = defined( 'HPCATALOG_REVALIDATION_URL' ) ? (string) HPCATALOG_REVALIDATION_URL : (string) getenv( 'HPCATALOG_REVALIDATION_URL' );
		if ( '' === $secret || '' === $url || ! wp_http_validate_url( $url ) ) {
			return;
		}
		$body      = (string) wp_json_encode(
			array(
				'tags'  => array( 'products', 'product:' . $post->post_name ),
				'paths' => array( '/products', '/products/' . $post->post_name ),
			)
		);
		$timestamp = (string) time();
		wp_safe_remote_post(
			$url,
			array(
				'timeout'     => 5,
				'blocking'    => false,
				'headers'     => array(
					'Content-Type'        => 'application/json',
					'X-Catalog-Timestamp' => $timestamp,
					'X-Catalog-Signature' => hash_hmac( 'sha256', $timestamp . '.' . $body, $secret ),
				),
				'body'        => $body,
				'data_format' => 'body',
			)
		);
	}
}
