<?php

$tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $tests_dir || ! file_exists( $tests_dir . '/includes/functions.php' ) ) {
	// Direct STDERR output is appropriate before the WordPress test bootstrap exists.
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
	fwrite( STDERR, "WP_TESTS_DIR must point to the WordPress test suite.\n" );
	exit( 1 );
}
require_once $tests_dir . '/includes/functions.php';
tests_add_filter( 'muplugins_loaded', static fn() => require dirname( __DIR__ ) . '/headless-product-catalog.php' );
require $tests_dir . '/includes/bootstrap.php';
