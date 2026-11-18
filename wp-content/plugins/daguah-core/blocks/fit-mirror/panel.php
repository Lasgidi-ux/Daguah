<?php
/**
 * The Fit Mirror panel. Printed once, at the end of the page, by the Fit
 * Mirror block: outside the header, because the header's blurred surface
 * would otherwise become the frame the fixed panel is positioned in.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

use Daguah\Core\Product_Data;

defined( 'ABSPATH' ) || exit;

$daguah_hints = array(
	'bust'     => __( 'Around the fullest part of the chest, tape level.', 'daguah' ),
	'waist'    => __( 'Around the narrowest part, usually just above the navel.', 'daguah' ),
	'hip'      => __( 'Around the fullest part of the hips and seat.', 'daguah' ),
	'length'   => __( 'From the top of the shoulder to where you want the piece to end.', 'daguah' ),
	'shoulder' => __( 'Across the back, from one shoulder point to the other.', 'daguah' ),
);
?>
<div id="daguah-fit-mirror" class="daguah-fit-mirror" role="dialog" aria-modal="true" aria-labelledby="daguah-fit-mirror-title" hidden>
	<div class="daguah-fit-mirror__panel">
		<div class="daguah-fit-mirror__head">
			<h2 id="daguah-fit-mirror-title" class="heading-5"><?php esc_html_e( 'Fit Mirror', 'daguah' ); ?></h2>
			<button type="button" class="daguah-icon-button" data-close-fit-mirror aria-label="<?php esc_attr_e( 'Close Fit Mirror', 'daguah' ); ?>"><?php echo daguah_icon( 'x' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?></button>
		</div>
		<p class="body-6 is-muted"><?php esc_html_e( 'Save your measurements once. Every piece with measurements then tells you whether it fits. Measure over light clothing with a soft tape.', 'daguah' ); ?></p>

		<form class="daguah-fit-mirror__form" novalidate>
			<div class="daguah-unit" role="group" aria-label="<?php esc_attr_e( 'Units', 'daguah' ); ?>">
				<button type="button" data-unit-choice="cm" aria-pressed="true"><?php esc_html_e( 'Centimetres', 'daguah' ); ?></button>
				<button type="button" data-unit-choice="in" aria-pressed="false"><?php esc_html_e( 'Inches', 'daguah' ); ?></button>
			</div>

			<?php foreach ( Product_Data::measurement_labels() as $daguah_key => $daguah_name ) : ?>
				<div class="daguah-fit-mirror__field">
					<label for="daguah-fit-<?php echo esc_attr( $daguah_key ); ?>">
						<span class="title-4"><?php echo esc_html( $daguah_name ); ?></span>
						<span class="caption-5 is-muted" id="daguah-fit-<?php echo esc_attr( $daguah_key ); ?>-hint"><?php echo esc_html( $daguah_hints[ $daguah_key ] ); ?></span>
					</label>
					<div class="daguah-fit-mirror__input">
						<input type="number" inputmode="decimal" step="0.5" min="20" max="250" id="daguah-fit-<?php echo esc_attr( $daguah_key ); ?>" name="<?php echo esc_attr( $daguah_key ); ?>" aria-describedby="daguah-fit-<?php echo esc_attr( $daguah_key ); ?>-hint">
						<span class="daguah-fit-mirror__unit" aria-hidden="true">cm</span>
					</div>
				</div>
			<?php endforeach; ?>

			<p class="daguah-fit-mirror__message body-5" role="status" aria-live="polite"></p>

			<div class="daguah-fit-mirror__actions">
				<button type="submit" class="wp-element-button"><?php esc_html_e( 'Save my measurements', 'daguah' ); ?></button>
				<button type="button" class="wp-element-button is-style-outline" data-clear-fit><?php esc_html_e( 'Remove them', 'daguah' ); ?></button>
			</div>
		</form>
	</div>
</div>
