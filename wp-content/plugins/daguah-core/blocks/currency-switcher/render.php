<?php
/**
 * Currency switcher block.
 *
 * Changes the currency shown beside prices and in the landed price. Checkout
 * stays in pounds until the multi currency plugin is connected (Stage 6).
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

use Daguah\Core\Countries;

defined( 'ABSPATH' ) || exit;

$daguah_id = wp_unique_id( 'daguah-currency-' );
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'daguah-currency' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<label class="screen-reader-text" for="<?php echo esc_attr( $daguah_id ); ?>"><?php esc_html_e( 'Show prices in', 'daguah' ); ?></label>
	<select id="<?php echo esc_attr( $daguah_id ); ?>" class="daguah-currency__select" data-daguah-currency>
		<?php foreach ( Countries::CURRENCIES as $daguah_code ) : ?>
			<option value="<?php echo esc_attr( $daguah_code ); ?>"><?php echo esc_html( $daguah_code ); ?></option>
		<?php endforeach; ?>
	</select>
</div>
