<?php
/**
 * Block registration.
 *
 * Each block has its own folder with a block.json and a render.php. Blocks are
 * rendered on the server, and a block's view script loads only on pages where
 * the block appears.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

namespace Daguah\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Blocks shipped so far. Later stages add to this list.
 */
const BLOCKS = array(
	'cloth',
	'menu-toggle',
	'theme-toggle',
	'icon',
	'market-word',
	'lantern-row',
	'calendar-ribbon',
	'occasion-grid',
	'featured-stalls',
	'live-strip',
	'lantern-words',
	'currency-switcher',
	'fit-mirror',
	'fit-chip',
	'hold-badge',
	'landed-price',
	'thread-map',
	'heritage-passport',
	'measurements',
	'product-media',
	'product-facts',
	'product-stall',
	'complete-the-look',
	'shop-filters',
	'product-results',
	'stall-header',
	'stall-pieces',
	'stall-directory',
	'occasion-intro',
	'circle-board',
	'circle-start',
	'live-schedule',
	'live-stage',
	'live-tray',
	'send-home',
	'gift-note',
);

add_action(
	'init',
	static function (): void {
		$asset_path = DAGUAH_CORE_DIR . 'build/editor.asset.php';
		$asset      = is_readable( $asset_path ) ? require $asset_path : array();

		wp_register_script(
			'daguah-core-editor',
			DAGUAH_CORE_URL . 'build/editor.js',
			(array) ( $asset['dependencies'] ?? array() ),
			(string) ( $asset['version'] ?? DAGUAH_CORE_VERSION ),
			true
		);
		wp_set_script_translations( 'daguah-core-editor', 'daguah' );

		// Choices for the block sidebars: stalls, occasions and icons.
		add_action(
			'enqueue_block_editor_assets',
			static function (): void {
				$icons = array_map(
					static fn( $path ) => basename( $path, '.svg' ),
					glob( DAGUAH_CORE_DIR . 'assets/icons/*.svg' ) ?: array()
				);

				$choices          = Post_Types::choices();
				$choices['icons'] = array_values( $icons );

				wp_add_inline_script( 'daguah-core-editor', 'window.daguahChoices = ' . wp_json_encode( $choices ) . ';', 'before' );
			}
		);

		foreach ( BLOCKS as $block ) {
			register_block_type( DAGUAH_CORE_DIR . 'blocks/' . $block );
		}
	}
);

/**
 * The product a product block is about.
 *
 * On a product page, that product. In the Site Editor, where a template has
 * no product of its own, the newest product, so the preview shows real data.
 *
 * @param \WP_Block|null $block The block being rendered.
 */
function block_product( $block = null ): ?\WC_Product {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return null;
	}

	$id = (int) ( $block->context['postId'] ?? 0 );
	$id = $id ?: (int) get_the_ID();

	if ( $id && 'product' === get_post_type( $id ) ) {
		$product = wc_get_product( $id );
		return $product instanceof \WC_Product ? $product : null;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		$latest = wc_get_products(
			array(
				'status'  => 'publish',
				'limit'   => 1,
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);
		return $latest[0] ?? null;
	}

	return null;
}

add_filter(
	'block_categories_all',
	static function ( array $categories ): array {
		array_unshift(
			$categories,
			array(
				'slug'  => 'daguah',
				'title' => __( 'DAGUAH', 'daguah' ),
			)
		);

		return $categories;
	}
);
