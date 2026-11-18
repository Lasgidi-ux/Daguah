<?php
/**
 * Fit Mirror block: a button, and (once per page) the panel it opens.
 *
 * Buyers save bust or chest, waist, hip, length and shoulder, in centimetres
 * or inches. Guests keep them on the device; signed in buyers also on their
 * account. Every fit chip on the site reads from here.
 *
 * @package Daguah\Core
 *
 * @var array $attributes Block attributes.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$daguah_label = ! empty( $attributes['showLabel'] );
?>
<button type="button" <?php echo get_block_wrapper_attributes( array( 'class' => $daguah_label ? 'daguah-fit-trigger has-label' : 'daguah-fit-trigger daguah-icon-button' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?> data-open-fit-mirror aria-haspopup="dialog" aria-controls="daguah-fit-mirror"<?php echo $daguah_label ? '' : ' aria-label="' . esc_attr__( 'Fit Mirror: your measurements', 'daguah' ) . '"'; ?>>
	<?php echo daguah_icon( 'ruler' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
	<?php if ( $daguah_label ) : ?>
		<span><?php esc_html_e( 'Fit Mirror', 'daguah' ); ?></span>
	<?php endif; ?>
</button>
<?php
// The panel goes at the end of the page, once, whatever number of buttons there are.
if ( ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) && ! has_action( 'wp_footer', 'daguah_print_fit_mirror' ) ) {
	if ( ! function_exists( 'daguah_print_fit_mirror' ) ) {
		/**
		 * Print the Fit Mirror panel.
		 */
		function daguah_print_fit_mirror(): void {
			require __DIR__ . '/panel.php';
		}
	}
	add_action( 'wp_footer', 'daguah_print_fit_mirror', 5 );
}
