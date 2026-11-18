<?php
/**
 * Start a circle block.
 *
 * Signed in buyers name the circle, pick the fabric, the yards each person
 * needs, where it is delivered and when it closes, and add a note to the
 * family. On success they land on the circle's board with the invite link.
 * Visitors who are not signed in are asked to sign in first, so the host can
 * come back to manage the circle.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

use Daguah\Core\Circles;
use Daguah\Core\Countries;
use Daguah\Core\Geo;
use Daguah\Core\Settings;

defined( 'ABSPATH' ) || exit;

if ( ! Settings::flag( 'circle' ) ) {
	?>
	<section <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
		<p class="daguah-notice body-5"><?php esc_html_e( 'DAGUAH Circle is resting for now. Every piece can still be ordered on its own, and we will open circles again soon.', 'daguah' ); ?></p>
	</section>
	<?php
	return;
}

$daguah_user   = wp_get_current_user();
$daguah_error  = sanitize_key( wp_unslash( $_GET['circle_error'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read only, a known word.
$daguah_form   = '' !== $daguah_error && ! isset( $_GET['c'] ) ? Circles::recall( 'start' ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only.
$daguah_typed  = (array) ( $daguah_form['input'] ?? array() );
$daguah_fields = (array) ( $daguah_form['fields'] ?? array() );
$daguah_fabric = Circles::fabrics();

// Sensible starting values: the buyer's own name and address, five yards, closing in six weeks.
if ( ! $daguah_form && $daguah_user->exists() ) {
	$daguah_typed = array(
		'name'    => (string) ( $daguah_user->first_name ?: $daguah_user->display_name ),
		'city'    => (string) get_user_meta( $daguah_user->ID, 'billing_city', true ),
		'country' => (string) get_user_meta( $daguah_user->ID, 'billing_country', true ),
	);
}

$daguah_typed += array(
	'title'    => '',
	'note'     => '',
	'fabric'   => '',
	'yards'    => '5',
	'delivery' => 'split',
	'closes'   => wp_date( 'Y-m-d', time() + 6 * WEEK_IN_SECONDS ),
	'name'     => '',
	'city'     => '',
	'country'  => '',
	'currency' => '',
);

if ( '' === $daguah_typed['currency'] ) {
	$daguah_typed['currency'] = '' !== $daguah_typed['country'] ? Countries::currency_for( $daguah_typed['country'] ) : 'GBP';
}

$daguah_invalid = static fn( string $field ): string => isset( $daguah_fields[ $field ] ) ? ' aria-invalid="true" aria-describedby="daguah-start-' . esc_attr( $field ) . '-error"' : '';
$daguah_message = static function ( string $field ) use ( $daguah_fields ): void {
	if ( isset( $daguah_fields[ $field ] ) ) {
		printf( '<span id="daguah-start-%1$s-error" class="caption-2 daguah-field__error">%2$s</span>', esc_attr( $field ), esc_html( (string) $daguah_fields[ $field ] ) );
	}
};
?>
<section <?php echo get_block_wrapper_attributes( array( 'id' => 'start', 'aria-labelledby' => 'daguah-start-title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<div class="daguah-section__head">
		<p class="daguah-eyebrow"><?php esc_html_e( 'Start a circle', 'daguah' ); ?></p>
		<h2 id="daguah-start-title" class="heading-3"><?php esc_html_e( 'Choose the cloth, then send the link', 'daguah' ); ?></h2>
		<p class="body-3 daguah-section__lead"><?php esc_html_e( 'It takes two minutes. Everyone you invite pays their own share in their own currency, and DAGUAH Hold protects every payment.', 'daguah' ); ?></p>
	</div>

	<?php if ( ! $daguah_user->exists() ) : ?>
		<div class="daguah-circle-signin">
			<p class="body-5"><?php esc_html_e( 'Sign in or create an account to start a circle, so you can come back to see who has joined.', 'daguah' ); ?></p>
			<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
				<a class="wp-element-button" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Sign in to start', 'daguah' ); ?></a>
			<?php endif; ?>
		</div>
	<?php elseif ( ! $daguah_fabric ) : ?>
		<p class="daguah-notice body-5"><?php esc_html_e( 'There is no fabric on the stalls to start a circle with yet. New fabric arrives every week.', 'daguah' ); ?></p>
	<?php else : ?>
		<form class="daguah-circle-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
			<?php if ( '' !== $daguah_error && ! isset( $_GET['c'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only. ?>
				<p class="daguah-notice is-error body-5" role="alert">
					<?php echo daguah_icon( 'warning-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
					<span><?php echo esc_html( (string) ( $daguah_form['message'] ?? ( 'expired' === $daguah_error ? __( 'That form had been open a long time. Please send it again.', 'daguah' ) : __( 'That did not work. Please try again.', 'daguah' ) ) ) ); ?></span>
				</p>
			<?php endif; ?>

			<input type="hidden" name="action" value="daguah_circle_start">
			<?php wp_nonce_field( 'daguah_circle_start', '_daguah_nonce', false ); ?>

			<fieldset class="daguah-form-grid">
				<legend class="title-3"><?php esc_html_e( 'The circle', 'daguah' ); ?></legend>

				<p class="daguah-field daguah-field--wide">
					<label for="daguah-start-title-field" class="caption-1"><?php esc_html_e( 'Name of the circle', 'daguah' ); ?></label>
					<input id="daguah-start-title-field" class="daguah-input" type="text" name="circle[title]" maxlength="120" required placeholder="<?php esc_attr_e( 'For example, Chidinma and Tobe\'s wedding', 'daguah' ); ?>" value="<?php echo esc_attr( $daguah_typed['title'] ); ?>"<?php echo $daguah_invalid( 'title' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>>
					<?php $daguah_message( 'title' ); ?>
				</p>

				<p class="daguah-field daguah-field--wide">
					<label for="daguah-start-fabric" class="caption-1"><?php esc_html_e( 'Fabric', 'daguah' ); ?></label>
					<select id="daguah-start-fabric" class="daguah-input" name="circle[fabric]" required<?php echo $daguah_invalid( 'fabric' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>>
						<option value=""><?php esc_html_e( 'Choose a fabric', 'daguah' ); ?></option>
						<?php foreach ( $daguah_fabric as $daguah_pid => $daguah_pname ) : ?>
							<option value="<?php echo esc_attr( (string) $daguah_pid ); ?>" <?php selected( (string) $daguah_typed['fabric'], (string) $daguah_pid ); ?>><?php echo esc_html( $daguah_pname ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php $daguah_message( 'fabric' ); ?>
				</p>

				<p class="daguah-field">
					<label for="daguah-start-yards" class="caption-1"><?php esc_html_e( 'Yards each person needs', 'daguah' ); ?></label>
					<input id="daguah-start-yards" class="daguah-input is-mono" type="number" name="circle[yards]" min="1" max="20" step="0.5" required value="<?php echo esc_attr( (string) $daguah_typed['yards'] ); ?>"<?php echo $daguah_invalid( 'yards' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>>
					<?php $daguah_message( 'yards' ); ?>
				</p>

				<p class="daguah-field">
					<label for="daguah-start-closes" class="caption-1"><?php esc_html_e( 'Closes to new members on', 'daguah' ); ?></label>
					<input id="daguah-start-closes" class="daguah-input is-mono" type="date" name="circle[closes]" required min="<?php echo esc_attr( wp_date( 'Y-m-d', time() + DAY_IN_SECONDS ) ); ?>" max="<?php echo esc_attr( wp_date( 'Y-m-d', time() + YEAR_IN_SECONDS ) ); ?>" value="<?php echo esc_attr( (string) $daguah_typed['closes'] ); ?>"<?php echo $daguah_invalid( 'closes' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>>
					<?php $daguah_message( 'closes' ); ?>
				</p>

				<div class="daguah-field daguah-field--wide" role="radiogroup" aria-labelledby="daguah-start-delivery-label">
					<span id="daguah-start-delivery-label" class="caption-1"><?php esc_html_e( 'Delivery', 'daguah' ); ?></span>
					<?php
					$daguah_modes = array(
						'split'  => array( __( 'Each person at their own address', 'daguah' ), __( 'Best when the family is spread across countries.', 'daguah' ) ),
						'single' => array( __( 'Everything to me', 'daguah' ), __( 'One parcel, one delivery charge. You share it out.', 'daguah' ) ),
					);
					foreach ( $daguah_modes as $daguah_mode => list( $daguah_label, $daguah_help ) ) :
						?>
						<label class="daguah-choice">
							<input type="radio" name="circle[delivery]" value="<?php echo esc_attr( $daguah_mode ); ?>" <?php checked( (string) $daguah_typed['delivery'], $daguah_mode ); ?>>
							<span>
								<span class="body-5"><?php echo esc_html( $daguah_label ); ?></span>
								<span class="caption-2 is-muted"><?php echo esc_html( $daguah_help ); ?></span>
							</span>
						</label>
					<?php endforeach; ?>
				</div>

				<p class="daguah-field daguah-field--wide">
					<label for="daguah-start-note" class="caption-1"><?php esc_html_e( 'A note to the family (optional)', 'daguah' ); ?></label>
					<textarea id="daguah-start-note" class="daguah-input" name="circle[note]" rows="3" maxlength="1000"<?php echo $daguah_invalid( 'note' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>><?php echo esc_textarea( (string) $daguah_typed['note'] ); ?></textarea>
					<?php $daguah_message( 'note' ); ?>
				</p>
			</fieldset>

			<fieldset class="daguah-form-grid">
				<legend class="title-3"><?php esc_html_e( 'You', 'daguah' ); ?></legend>

				<p class="daguah-field">
					<label for="daguah-start-name" class="caption-1"><?php esc_html_e( 'Your name', 'daguah' ); ?></label>
					<input id="daguah-start-name" class="daguah-input" type="text" name="circle[name]" maxlength="60" required autocomplete="name" value="<?php echo esc_attr( (string) $daguah_typed['name'] ); ?>"<?php echo $daguah_invalid( 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>>
					<?php $daguah_message( 'name' ); ?>
				</p>

				<p class="daguah-field">
					<label for="daguah-start-city" class="caption-1"><?php esc_html_e( 'Your city', 'daguah' ); ?></label>
					<input id="daguah-start-city" class="daguah-input" type="text" name="circle[city]" maxlength="60" required autocomplete="address-level2" value="<?php echo esc_attr( (string) $daguah_typed['city'] ); ?>"<?php echo $daguah_invalid( 'city' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>>
					<?php $daguah_message( 'city' ); ?>
				</p>

				<p class="daguah-field">
					<label for="daguah-start-country" class="caption-1"><?php esc_html_e( 'Country', 'daguah' ); ?></label>
					<select id="daguah-start-country" class="daguah-input" name="circle[country]" required data-currency-for="#daguah-start-currency"<?php echo $daguah_invalid( 'country' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>>
						<option value=""><?php esc_html_e( 'Choose', 'daguah' ); ?></option>
						<?php foreach ( Geo::buyer_countries() as $daguah_code => $daguah_c ) : ?>
							<option value="<?php echo esc_attr( $daguah_code ); ?>" data-currency="<?php echo esc_attr( $daguah_c['currency'] ); ?>" <?php selected( (string) $daguah_typed['country'], $daguah_code ); ?>><?php echo esc_html( $daguah_c['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php $daguah_message( 'country' ); ?>
				</p>

				<p class="daguah-field">
					<label for="daguah-start-currency" class="caption-1"><?php esc_html_e( 'You pay in', 'daguah' ); ?></label>
					<select id="daguah-start-currency" class="daguah-input" name="circle[currency]">
						<?php foreach ( Countries::CURRENCIES as $daguah_cur ) : ?>
							<option value="<?php echo esc_attr( $daguah_cur ); ?>" <?php selected( (string) $daguah_typed['currency'], $daguah_cur ); ?>><?php echo esc_html( $daguah_cur ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
			</fieldset>

			<div class="daguah-circle-form__foot">
				<button type="submit" class="wp-element-button"><?php esc_html_e( 'Start the circle', 'daguah' ); ?></button>
				<p class="caption-2 is-muted"><?php esc_html_e( 'Nothing is charged now. You pay your own share at checkout, like everyone else.', 'daguah' ); ?></p>
			</div>
		</form>
	<?php endif; ?>
</section>
