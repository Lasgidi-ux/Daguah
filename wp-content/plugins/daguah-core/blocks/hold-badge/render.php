<?php
/**
 * DAGUAH Hold line.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;
?>
<p <?php echo get_block_wrapper_attributes( array( 'class' => 'daguah-hold' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<?php echo daguah_icon( 'shield-check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
	<span>
		<strong><?php esc_html_e( 'DAGUAH Hold.', 'daguah' ); ?></strong>
		<?php esc_html_e( 'Your payment is held until you confirm delivery.', 'daguah' ); ?>
		<a href="<?php echo esc_url( home_url( '/help/' ) ); ?>"><?php esc_html_e( 'How it works', 'daguah' ); ?></a>
	</span>
</p>
