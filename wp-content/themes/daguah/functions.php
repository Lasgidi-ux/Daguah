<?php
/**
 * DAGUAH theme bootstrap.
 *
 * Presentation only. Data, blocks and marketplace logic live in the
 * daguah-core plugin so nothing is lost if the theme changes.
 *
 * @package Daguah
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

define( 'DAGUAH_THEME_VERSION', '0.1.0' );

require_once __DIR__ . '/inc/theme-mode.php';
require_once __DIR__ . '/inc/motion.php';
require_once __DIR__ . '/inc/block-styles.php';
require_once __DIR__ . '/inc/chrome.php';
require_once __DIR__ . '/inc/shop.php';
require_once __DIR__ . '/inc/opening-video.php';

/**
 * Read a wp-scripts asset manifest from the build folder.
 *
 * @param string $name Entry name without extension.
 * @return array{dependencies: string[], version: string}
 */
function daguah_asset( string $name ): array {
	$path = get_theme_file_path( "build/{$name}.asset.php" );

	if ( is_readable( $path ) ) {
		$asset = require $path;
		if ( is_array( $asset ) ) {
			return array(
				'dependencies' => (array) ( $asset['dependencies'] ?? array() ),
				'version'      => (string) ( $asset['version'] ?? DAGUAH_THEME_VERSION ),
			);
		}
	}

	return array(
		'dependencies' => array(),
		'version'      => DAGUAH_THEME_VERSION,
	);
}

add_action(
	'after_setup_theme',
	static function (): void {
		load_theme_textdomain( 'daguah', get_theme_file_path( 'languages' ) );

		add_theme_support( 'woocommerce' );
		add_theme_support( 'editor-styles' );
		add_editor_style( 'build/theme.css' );

		// Core patterns do not follow the system, so the inserter shows ours only.
		remove_theme_support( 'core-block-patterns' );
	}
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$theme = daguah_asset( 'theme' );

		wp_enqueue_style(
			'daguah-theme',
			get_theme_file_uri( 'build/theme.css' ),
			array(),
			$theme['version']
		);
		wp_add_inline_style( 'daguah-theme', daguah_mode_css() );

		wp_enqueue_script(
			'daguah-theme',
			get_theme_file_uri( 'build/theme.js' ),
			$theme['dependencies'],
			$theme['version'],
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		$smooth = daguah_asset( 'smooth' );

		wp_enqueue_script(
			'daguah-smooth',
			get_theme_file_uri( 'build/smooth.js' ),
			$smooth['dependencies'],
			$smooth['version'],
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}
);

add_action(
	'enqueue_block_editor_assets',
	static function (): void {
		$editor = daguah_asset( 'editor' );

		wp_enqueue_script(
			'daguah-theme-editor',
			get_theme_file_uri( 'build/editor.js' ),
			$editor['dependencies'],
			$editor['version'],
			true
		);
		wp_set_script_translations( 'daguah-theme-editor', 'daguah' );
	}
);

/**
 * Preload the one font file every page needs for its first paint.
 */
add_action(
	'wp_head',
	static function (): void {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( get_theme_file_uri( 'assets/fonts/manrope-latin-wght-normal.woff2' ) )
		);
	},
	1
);
