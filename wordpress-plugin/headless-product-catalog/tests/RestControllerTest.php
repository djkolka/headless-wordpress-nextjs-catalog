<?php

final class RestControllerTest extends WP_UnitTestCase {
	public function set_up(): void {
		parent::set_up();
		do_action( 'rest_api_init' );
	}

	public function test_rejects_invalid_collection_parameters(): void {
		$request = new WP_REST_Request( 'GET', '/catalog/v1/products' );
		$request->set_param( 'per_page', 500 );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 400, $response->get_status() );
	}

	public function test_draft_is_not_publicly_exposed(): void {
		self::factory()->post->create(
			array(
				'post_type'   => 'hpc_product',
				'post_status' => 'draft',
				'post_name'   => 'secret-draft',
				'post_title'  => 'Secret Draft',
			)
		);
		$request  = new WP_REST_Request( 'GET', '/catalog/v1/products/secret-draft' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 404, $response->get_status() );
	}

	public function test_sort_is_allow_listed(): void {
		$request = new WP_REST_Request( 'GET', '/catalog/v1/products' );
		$request->set_param( 'sort', 'post_password' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 400, $response->get_status() );
	}
}
