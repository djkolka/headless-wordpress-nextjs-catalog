<?php

declare(strict_types=1);

namespace Headless_Catalog;

final class Field_Repository {
	public const PREFIX = 'hpcatalog_';

	/** @return mixed */
	public function get( int $post_id, string $name ) {
		$key = self::PREFIX . $name;
		if ( function_exists( 'get_field' ) ) {
			$value = get_field( $key, $post_id );
			if ( false !== $value && null !== $value ) {
				return $value;
			}
		}
		return get_post_meta( $post_id, $key, true );
	}

	/** @param mixed $value */
	public static function set( int $post_id, string $name, $value ): void {
		if ( function_exists( 'update_field' ) ) {
			update_field( 'field_hpcatalog_' . $name, $value, $post_id );
			return;
		}

		update_post_meta( $post_id, self::PREFIX . $name, $value );
	}
}
