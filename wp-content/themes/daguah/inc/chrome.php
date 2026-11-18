<?php
/**
 * Small fixes to core block output so the header matches the system.
 *
 * @package Daguah
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Phosphor icon on the core search button (core ships its own glyph).
 */
add_filter(
	'render_block_core/search',
	static function ( string $content ): string {
		if ( ! function_exists( 'daguah_icon' ) ) {
			return $content;
		}

		$icon = daguah_icon( 'magnifying-glass' );

		if ( '' === $icon ) {
			return $content;
		}

		$swapped = preg_replace( '/<svg\b[^>]*class="search-icon"[^>]*>.*?<\/svg>/s', $icon, $content, 1 );

		return is_string( $swapped ) ? $swapped : $content;
	}
);

/**
 * The theme draws its header and cloth with blocks from daguah-core.
 */
add_action(
	'admin_notices',
	static function (): void {
		if ( function_exists( 'daguah_icon' ) || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__( 'The DAGUAH theme needs the DAGUAH Core plugin. Activate it so the header, cloth patterns and marketplace features appear.', 'daguah' )
		);
	}
);
