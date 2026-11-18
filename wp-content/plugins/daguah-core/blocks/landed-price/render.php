<?php
/**
 * Landed price block.
 *
 * Rendered for the store default (United Kingdom, pounds) so it is complete
 * without scripts and caches well. The shop script refetches it for the
 * visitor's own country and currency.
 *
 * @package Daguah\Core
 *
 * @var array     $attributes Block attributes.
 * @var \WP_Block $block      Block instance.
 */

declare( strict_types=1 );

use Daguah\Core\Geo;
use Daguah\Core\Pricing;

use function Daguah\Core\block_product;

defined( 'ABSPATH' ) || exit;

$daguah_product = block_product( $block ?? null );

if ( ! $daguah_product ) {
	return;
}

$daguah_country = 'GB';
$daguah_landed  = Pricing::landed( $daguah_product->get_id(), $daguah_country, 'GBP' );

if ( is_wp_error( $daguah_landed ) ) {
	return;
}

$daguah_money = static fn( float $amount ) => wp_strip_all_tags( wc_price( $amount, array( 'currency' => 'GBP' ) ) );
$daguah_id    = wp_unique_id( 'daguah-landed-' );
?>
<section <?php echo get_block_wrapper_attributes( array( 'data-product' => (string) $daguah_product->get_id(), 'data-country' => $daguah_country, 'data-currency' => 'GBP', 'aria-labelledby' => $daguah_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<div class="daguah-landed__head">
		<h2 class="title-2" id="<?php echo esc_attr( $daguah_id ); ?>"><?php esc_html_e( 'Full price to your door', 'daguah' ); ?></h2>
		<label class="daguah-landed__country">
			<span class="caption-4"><?php esc_html_e( 'Delivering to', 'daguah' ); ?></span>
			<select data-daguah-country>
				<?php foreach ( Geo::buyer_countries() as $daguah_code => $daguah_info ) : ?>
					<option value="<?php echo esc_attr( $daguah_code ); ?>" <?php selected( $daguah_code, $daguah_country ); ?>><?php echo esc_html( $daguah_info['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
	</div>

	<dl class="daguah-landed__lines" aria-live="polite">
		<?php if ( $daguah_landed['ships'] ) : ?>
			<div class="daguah-landed__row"><dt><?php esc_html_e( 'Item', 'daguah' ); ?></dt><dd><?php echo esc_html( $daguah_money( $daguah_landed['item'] ) ); ?></dd></div>
			<div class="daguah-landed__row"><dt><?php echo esc_html( sprintf( /* translators: 1: fewest days, 2: most days */ __( 'Delivery, %1$d to %2$d days', 'daguah' ), $daguah_landed['delivery']['min_days'], $daguah_landed['delivery']['max_days'] ) ); ?></dt><dd><?php echo esc_html( $daguah_money( $daguah_landed['delivery']['amount'] ) ); ?></dd></div>
			<div class="daguah-landed__row"><dt><?php esc_html_e( 'Estimated duties', 'daguah' ); ?></dt><dd><?php echo esc_html( $daguah_money( $daguah_landed['duties']['amount'] ) ); ?></dd></div>
			<div class="daguah-landed__row is-total"><dt><?php esc_html_e( 'At your door', 'daguah' ); ?></dt><dd><?php echo esc_html( $daguah_money( $daguah_landed['total'] ) ); ?></dd></div>
		<?php else : ?>
			<p class="daguah-landed__nope"><?php esc_html_e( 'This piece does not ship to the United Kingdom yet.', 'daguah' ); ?></p>
		<?php endif; ?>
	</dl>

	<p class="daguah-landed__note caption-5"><?php echo 'demo' === $daguah_landed['rates']['source'] ? esc_html__( 'Demo rates. Duties are an estimate; customs sets the final amount.', 'daguah' ) : esc_html__( 'Duties are an estimate; customs sets the final amount.', 'daguah' ); ?></p>
</section>
