<?php

final class Test_Product_Normalizer extends WP_UnitTestCase {
	public function test_normalizes_fields_without_exposing_raw_post_data(): void {
		$id = self::factory()->post->create(
			array(
				'post_type'    => 'hpc_product',
				'post_status'  => 'publish',
				'post_title'   => 'Test Sensor',
				'post_name'    => 'test-sensor',
				'post_content' => '<p>Safe</p><script>bad()</script>',
			)
		);
		\Headless_Catalog\Field_Repository::set( $id, 'sku', 'TS-1' );
		\Headless_Catalog\Field_Repository::set( $id, 'price', '12.50' );
		\Headless_Catalog\Field_Repository::set( $id, 'currency', 'usd' );
		$result = ( new \Headless_Catalog\Product_Normalizer() )->normalize( get_post( $id ) );

		$this->assertSame( 'TS-1', $result['sku'] );
		$this->assertSame( 12.5, $result['price']['amount'] );
		$this->assertSame( 'USD', $result['price']['currency'] );
		$this->assertStringNotContainsString( '<script', $result['content'] );
		$this->assertArrayNotHasKey( 'post_author', $result );
	}
}
