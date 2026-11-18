<?php
/**
 * Plugin Name:       DAGUAH Core
 * Description:       Data model, blocks, REST endpoints and marketplace logic for DAGUAH. Everything here keeps working if the theme changes.
 * Version:           0.5.0
 * Requires at least: 6.8
 * Requires PHP:      8.2
 * Author:            DAGUAH
 * License:           GPL-2.0-or-later
 * Text Domain:       daguah
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

define( 'DAGUAH_CORE_VERSION', '0.5.0' );
define( 'DAGUAH_CORE_FILE', __FILE__ );
define( 'DAGUAH_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'DAGUAH_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once DAGUAH_CORE_DIR . 'includes/class-cloth.php';
require_once DAGUAH_CORE_DIR . 'includes/icons.php';
require_once DAGUAH_CORE_DIR . 'includes/blocks.php';
require_once DAGUAH_CORE_DIR . 'includes/class-countries.php';
require_once DAGUAH_CORE_DIR . 'includes/class-settings.php';
require_once DAGUAH_CORE_DIR . 'includes/data/class-taxonomies.php';
require_once DAGUAH_CORE_DIR . 'includes/data/class-product-data.php';
require_once DAGUAH_CORE_DIR . 'includes/data/class-vendor.php';
require_once DAGUAH_CORE_DIR . 'includes/data/class-post-types.php';
require_once DAGUAH_CORE_DIR . 'includes/class-pricing.php';
require_once DAGUAH_CORE_DIR . 'includes/class-fit.php';
require_once DAGUAH_CORE_DIR . 'includes/class-geo.php';
require_once DAGUAH_CORE_DIR . 'includes/class-shop-assets.php';
require_once DAGUAH_CORE_DIR . 'includes/class-shop-query.php';
require_once DAGUAH_CORE_DIR . 'includes/class-product-card.php';
require_once DAGUAH_CORE_DIR . 'includes/class-calendar.php';
require_once DAGUAH_CORE_DIR . 'includes/class-faq-schema.php';
require_once DAGUAH_CORE_DIR . 'includes/class-stalls.php';
require_once DAGUAH_CORE_DIR . 'includes/class-live.php';
require_once DAGUAH_CORE_DIR . 'includes/class-circles.php';
require_once DAGUAH_CORE_DIR . 'includes/class-rest.php';
require_once DAGUAH_CORE_DIR . 'includes/seed/class-seeder.php';

Daguah\Core\Settings::init();
Daguah\Core\Taxonomies::init();
Daguah\Core\Product_Data::init();
Daguah\Core\Vendor::init();
Daguah\Core\Post_Types::init();
Daguah\Core\Rest::init();
Daguah\Core\Faq_Schema::init();
Daguah\Core\Shop_Assets::init();
Daguah\Core\Shop_Query::init();
Daguah\Core\Stalls::init();
Daguah\Core\Live::init();
Daguah\Core\Circles::init();

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once DAGUAH_CORE_DIR . 'includes/class-cli.php';
	WP_CLI::add_command( 'daguah', Daguah\Core\CLI::class );
}

add_action(
	'admin_enqueue_scripts',
	static function ( string $hook ): void {
		$screen = get_current_screen();

		if ( 'toplevel_page_daguah' === $hook || ( $screen && 'product' === $screen->post_type ) || in_array( $hook, array( 'profile.php', 'user-edit.php' ), true ) ) {
			wp_enqueue_style( 'daguah-admin', DAGUAH_CORE_URL . 'assets/admin.css', array(), DAGUAH_CORE_VERSION );
		}
	}
);

register_activation_hook(
	__FILE__,
	static function (): void {
		Daguah\Core\Taxonomies::register();
		Daguah\Core\Post_Types::register();
		Daguah\Core\Taxonomies::ensure_attributes();
		flush_rewrite_rules( false );
	}
);

register_deactivation_hook( __FILE__, static fn() => flush_rewrite_rules( false ) );
