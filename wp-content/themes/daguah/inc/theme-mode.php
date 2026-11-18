<?php
/**
 * Market at Dusk and Market at Noon.
 *
 * theme.json holds the dusk colour roles and styles/noon.json holds the noon
 * ones. This file turns both into [data-theme] overrides so the front end
 * toggle works whichever variation the client picks as the site default.
 *
 * @package Daguah
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Colour roles from a theme JSON file.
 *
 * @param string $file Path relative to the theme root.
 * @return array<string, string>
 */
function daguah_mode_tokens( string $file ): array {
	$path = get_theme_file_path( $file );

	if ( ! is_readable( $path ) ) {
		return array();
	}

	$data   = wp_json_file_decode( $path, array( 'associative' => true ) );
	$tokens = is_array( $data ) ? ( $data['settings']['custom']['color'] ?? array() ) : array();

	return is_array( $tokens ) ? array_filter( $tokens, 'is_string' ) : array();
}

/**
 * The mode the site opens in, from the active global style.
 */
function daguah_default_mode(): string {
	$base = wp_get_global_settings( array( 'custom', 'color', 'base' ) );
	$noon = daguah_mode_tokens( 'styles/noon.json' );

	if ( is_string( $base ) && isset( $noon['base'] ) && 0 === strcasecmp( $base, $noon['base'] ) ) {
		return 'noon';
	}

	return 'dusk';
}

/**
 * CSS that redefines every colour role under each [data-theme] value.
 */
function daguah_mode_css(): string {
	$modes = array(
		'dusk' => array( daguah_mode_tokens( 'theme.json' ), 'dark' ),
		'noon' => array( daguah_mode_tokens( 'styles/noon.json' ), 'light' ),
	);

	$default = daguah_default_mode();
	$css     = ':root{color-scheme:' . $modes[ $default ][1] . '}';

	foreach ( $modes as $mode => list( $tokens, $scheme ) ) {
		$css .= ':root[data-theme="' . $mode . '"]{color-scheme:' . $scheme . ';';

		foreach ( $tokens as $key => $value ) {
			// Colour values only: hex, rgb() and rgba().
			if ( ! preg_match( '/^[#(),.%\w\s-]+$/', $value ) ) {
				continue;
			}
			$css .= '--wp--custom--color--' . _wp_to_kebab_case( (string) $key ) . ':' . $value . ';';
		}

		$css .= '}';
	}

	// The Opening is always the market at dusk, whichever mode the visitor picks.
	$css .= '.daguah-opening{color-scheme:dark;';
	foreach ( $modes['dusk'][0] as $key => $value ) {
		if ( preg_match( '/^[#(),.%\w\s-]+$/', $value ) ) {
			$css .= '--wp--custom--color--' . _wp_to_kebab_case( (string) $key ) . ':' . $value . ';';
		}
	}
	$css .= '--base:var(--wp--custom--color--base);--text:var(--wp--custom--color--text);--muted:var(--wp--custom--color--text-muted);--action:var(--wp--custom--color--action);--action-text:var(--wp--custom--color--action-text);--action-hover:var(--wp--custom--color--action-hover);--action-hover-text:var(--wp--custom--color--action-hover-text);--link:var(--wp--custom--color--link);--focus:var(--wp--custom--color--focus);--line:var(--wp--custom--color--line);--raised:var(--wp--custom--color--raised);}';

	return $css;
}

/**
 * Tell the toggle which mode is the default.
 */
add_filter(
	'language_attributes',
	static function ( string $output ): string {
		if ( is_admin() ) {
			return $output;
		}

		return $output . ' data-default-theme="' . esc_attr( daguah_default_mode() ) . '"';
	}
);

/**
 * Apply the saved mode before first paint, and flag that JS is running.
 *
 * A link can also ask for a mode with ?mode=noon or ?mode=dusk, which is how
 * review screenshots of each mode are taken. That choice is not saved.
 */
add_action(
	'wp_head',
	static function (): void {
		wp_print_inline_script_tag(
			'(function(){var d=document.documentElement,t=null;d.classList.add("js");try{t=localStorage.getItem("daguah-theme");}catch(e){}var m=/[?&]mode=(dusk|noon)(&|$)/.exec(location.search);if(m){t=m[1];}if(t==="dusk"||t==="noon"){d.setAttribute("data-theme",t);}})();'
		);
	},
	0
);
