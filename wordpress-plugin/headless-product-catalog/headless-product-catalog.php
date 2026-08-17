<?php
/**
 * Plugin Name: Headless Product Catalog
 * Description: A normalized, secure REST API for a headless product catalog.
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Headless Catalog Demo
 * License: MIT
 * Text Domain: headless-product-catalog
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HPCATALOG_VERSION', '1.0.0' );
define( 'HPCATALOG_FILE', __FILE__ );

require_once __DIR__ . '/includes/class-product-post-type.php';
require_once __DIR__ . '/includes/class-acf-fields.php';
require_once __DIR__ . '/includes/class-field-repository.php';
require_once __DIR__ . '/includes/class-product-normalizer.php';
require_once __DIR__ . '/includes/class-rest-controller.php';
require_once __DIR__ . '/includes/class-cache-invalidator.php';
require_once __DIR__ . '/includes/class-seed-command.php';

add_action(
	'plugins_loaded',
	static function (): void {
		\Headless_Catalog\Product_Post_Type::register_hooks();
		\Headless_Catalog\ACF_Fields::register_hooks();
		\Headless_Catalog\REST_Controller::register_hooks();
		\Headless_Catalog\Cache_Invalidator::register_hooks();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'catalog seed', new \Headless_Catalog\Seed_Command() );
		}
	}
);

register_activation_hook(
	__FILE__,
	static function (): void {
		\Headless_Catalog\Product_Post_Type::register();
		flush_rewrite_rules();
	}
);

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
