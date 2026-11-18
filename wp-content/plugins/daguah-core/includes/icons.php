<?php
/**
 * Phosphor icons as inline SVG.
 *
 * Icons are copied from @phosphor-icons/core into assets/icons by
 * `npm run assets`. Add a name to scripts/copy-assets.mjs to bring in more.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'daguah_icon' ) ) {
	/**
	 * Inline SVG for a Phosphor icon.
	 *
	 * The markup comes from a file shipped with the plugin, so it is trusted
	 * and returned ready to print.
	 *
	 * @param string $name Icon file name without extension, for example "seal-check".
	 * @param array  $args {
	 *     Optional.
	 *
	 *     @type string $label Accessible name. Without one the icon is decorative.
	 *     @type string $class Extra class names.
	 * }
	 * @return string SVG markup, or an empty string if the icon is missing.
	 */
	function daguah_icon( string $name, array $args = array() ): string {
		static $cache = array();

		$name = preg_replace( '/[^a-z0-9-]/', '', strtolower( $name ) );

		if ( ! is_string( $name ) || '' === $name ) {
			return '';
		}

		if ( ! array_key_exists( $name, $cache ) ) {
			$path           = DAGUAH_CORE_DIR . 'assets/icons/' . $name . '.svg';
			$cache[ $name ] = is_readable( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file shipped with the plugin.
		}

		if ( '' === $cache[ $name ] ) {
			return '';
		}

		$svg = new WP_HTML_Tag_Processor( $cache[ $name ] );

		if ( ! $svg->next_tag( 'svg' ) ) {
			return '';
		}

		$svg->add_class( 'daguah-icon' );
		$svg->set_attribute( 'width', '24' );
		$svg->set_attribute( 'height', '24' );
		$svg->set_attribute( 'focusable', 'false' );

		if ( ! empty( $args['class'] ) ) {
			$svg->add_class( (string) $args['class'] );
		}

		if ( ! empty( $args['label'] ) ) {
			$svg->set_attribute( 'role', 'img' );
			$svg->set_attribute( 'aria-label', (string) $args['label'] );
		} else {
			$svg->set_attribute( 'aria-hidden', 'true' );
		}

		return $svg->get_updated_html();
	}
}
