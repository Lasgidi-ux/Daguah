<?php
/**
 * Shop pages: body class, and "unrolling the bolt" between them.
 *
 * Moving between shop, category, occasion and search pages, the old page
 * rolls up and away while the new one settles in from just beneath it, like
 * cloth coming off a bolt. It uses cross document view transitions, which
 * both pages must opt into, so the rule is printed on shop pages only. The
 * header stays put. Visitors who prefer reduced motion get no transition.
 *
 * @package Daguah
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Is this a shop listing page?
 */
function daguah_is_shop_page(): bool {
	if ( ! function_exists( 'is_shop' ) ) {
		return false;
	}

	return is_shop() || is_product_taxonomy() || ( is_search() && 'product' === get_query_var( 'post_type' ) );
}

add_filter(
	'body_class',
	static function ( array $classes ): array {
		if ( daguah_is_shop_page() ) {
			$classes[] = 'daguah-shop-page';
		}
		return $classes;
	}
);

add_action(
	'wp_head',
	static function (): void {
		if ( ! daguah_is_shop_page() ) {
			return;
		}

		$css = '@media (prefers-reduced-motion: no-preference){'
			. '@view-transition{navigation:auto}'
			. '.wp-site-blocks>header.wp-block-template-part{view-transition-name:daguah-header}'
			. '::view-transition-old(root){animation:daguah-bolt-away 500ms cubic-bezier(0.22,1,0.36,1) both;z-index:2}'
			. '::view-transition-new(root){animation:daguah-bolt-settle 500ms cubic-bezier(0.22,1,0.36,1) both;z-index:1}'
			. '@keyframes daguah-bolt-away{to{transform:translateY(-24%);opacity:0}}'
			. '@keyframes daguah-bolt-settle{from{transform:translateY(32px) scale(0.985);opacity:0.4}}'
			. '}';

		printf( "<style id=\"daguah-unroll\">%s</style>\n", $css ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static CSS above.
	}
);

add_action(
	'init',
	static function (): void {
		register_block_style(
			'core/separator',
			array(
				'name'  => 'thread',
				'label' => __( 'Thread', 'daguah' ),
			)
		);
	}
);
