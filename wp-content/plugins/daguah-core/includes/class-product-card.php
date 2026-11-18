<?php
/**
 * The product card, rendered on the server.
 *
 * One markup for every list of pieces: the calendar collection on the home
 * page now, the shop grid in Stage 4. Until a piece has a photo it wears its
 * stall's cloth and a line describing the photo that should be taken.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

namespace Daguah\Core;

use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Product cards and grids.
 */
final class Product_Card {

	/**
	 * One card.
	 *
	 * @param int $product_id Product ID.
	 */
	public static function render( int $product_id ): string {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;

		if ( ! $product instanceof WC_Product || ! $product->is_visible() || in_array( $product_id, Shop_Query::hidden_ids(), true ) ) {
			return '';
		}

		$data      = Product_Data::get( $product_id );
		$vendor_id = (int) get_post_field( 'post_author', $product_id );
		$stall     = Vendor::is_vendor( $vendor_id ) ? Vendor::store_name( $vendor_id ) : '';
		$place     = implode( ', ', array_filter( array( $stall, $data['origin_town'] ) ) );
		$condition = $data['condition'][0] ?? '';
		$image_id  = (int) $product->get_image_id();

		if ( $image_id ) {
			$media = wp_get_attachment_image(
				$image_id,
				'woocommerce_thumbnail',
				false,
				array(
					'class'   => 'daguah-card__image',
					'sizes'   => '(min-width: 1024px) 25vw, (min-width: 600px) 50vw, 100vw',
					'loading' => 'lazy',
				)
			);
		} else {
			$media = $vendor_id ? Vendor::cloth_svg( $vendor_id ) : Cloth::svg( $product->get_name(), 'mix' );
		}

		Shop_Assets::enqueue();

		ob_start();
		?>
		<article class="daguah-card is-interactive daguah-product-card">
			<div class="daguah-card__media daguah-cloth-box daguah-product-card__media">
				<?php echo $media; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core image markup or cloth SVG built from integers. ?>
			</div>
			<div class="daguah-card__body">
				<?php if ( $condition ) : ?>
					<span><span class="daguah-tag<?php echo 'New' === $condition ? '' : ' daguah-tag--quiet'; ?>"><?php echo esc_html( $condition ); ?></span></span>
				<?php endif; ?>
				<h3 class="title-3 daguah-product-card__title">
					<a class="daguah-product-card__link" href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
				</h3>
				<?php if ( $place ) : ?>
					<p class="caption-2"><?php echo esc_html( $place ); ?></p>
				<?php endif; ?>
				<?php echo self::fit_chip( $data['measurements'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>
				<p class="body-1 daguah-price">
					<?php echo wp_kses_post( $product->get_price_html() ); ?>
					<span class="daguah-approx caption-4" data-daguah-gbp="<?php echo esc_attr( (string) $product->get_price() ); ?>" hidden></span>
				</p>
				<?php if ( ! $image_id && '' !== $data['shot_note'] ) : ?>
					<p class="caption-5 daguah-product-card__shot"><?php echo esc_html( sprintf( /* translators: %s: description of the photo to take */ __( 'Photo to come: %s', 'daguah' ), $data['shot_note'] ) ); ?></p>
				<?php endif; ?>
			</div>
		</article>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The fit chip and its detail. Hidden until the shop script knows the
	 * buyer's measurements; pieces without measurements get none.
	 *
	 * @param array $measurements Piece measurements in cm.
	 */
	public static function fit_chip( array $measurements ): string {
		if ( ! $measurements ) {
			return '';
		}

		Shop_Assets::enqueue();

		return sprintf(
			'<div class="daguah-fit"><button type="button" class="daguah-fit-chip" data-measurements="%1$s" aria-expanded="false" hidden><span class="daguah-fit-chip__dot" aria-hidden="true"></span><span class="daguah-fit-chip__label">%2$s</span></button><p class="daguah-fit-chip__detail caption-5" hidden></p></div>',
			esc_attr( (string) wp_json_encode( $measurements ) ),
			esc_html__( 'Check the fit', 'daguah' )
		);
	}

	/**
	 * A grid of cards, or a composed empty state.
	 *
	 * @param int[]  $ids   Product IDs.
	 * @param string $empty What to say when there is nothing to show.
	 */
	public static function grid( array $ids, string $empty = '', string $action = '' ): string {
		$cards = array_filter( array_map( array( self::class, 'render' ), $ids ) );

		if ( ! $cards ) {
			return sprintf(
				'<div class="daguah-empty"><div class="daguah-empty__cloth daguah-cloth-box">%1$s</div><div class="daguah-empty__text"><p class="body-3">%2$s</p>%3$s</div></div>',
				Cloth::svg( 'empty|' . $empty, 'mix' ), // Built from integers and a fixed palette.
				esc_html( '' !== $empty ? $empty : __( 'Nothing on the stalls for this yet. New pieces arrive every week.', 'daguah' ) ),
				$action // Markup built by the caller with escaped parts.
			);
		}

		return '<div class="daguah-product-grid">' . implode( '', $cards ) . '</div>';
	}
}
