<?php
/**
 * Circle board block.
 *
 * Opened from an invite link (/circle/?c=ID&key=KEY). Shows the fabric, who
 * has joined from where and who has paid, the link to pass on, and the form
 * to join. Renders nothing on the Circle page without an invite link.
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
	return;
}

$daguah_req = Circles::from_request();

if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
	printf( '<p %s>%s</p>', get_block_wrapper_attributes(), esc_html__( 'The circle board appears here when someone opens an invite link.', 'daguah' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core.
	return;
}

if ( ! $daguah_req['asked'] ) {
	return;
}

if ( ! $daguah_req['id'] ) {
	?>
	<section <?php echo get_block_wrapper_attributes( array( 'class' => 'is-missing' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
		<p class="daguah-notice is-error body-5" role="alert">
			<?php echo daguah_icon( 'warning-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
			<span><?php esc_html_e( 'This invite link does not open a circle. It may have been copied only in part. Ask the person who sent it for the whole link.', 'daguah' ); ?></span>
		</p>
	</section>
	<?php
	return;
}

$daguah_id      = $daguah_req['id'];
$daguah_board   = Circles::board( $daguah_id );
$daguah_post    = get_post( $daguah_id );
$daguah_user    = get_current_user_id();
$daguah_is_in   = null !== Circles::member_index( $daguah_id, $daguah_user );
$daguah_invite  = Circles::invite_url( $daguah_id );
$daguah_done    = sanitize_key( wp_unslash( $_GET['circle_done'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read only, a known word.
$daguah_error   = sanitize_key( wp_unslash( $_GET['circle_error'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read only, a known word.
$daguah_form    = '' !== $daguah_error ? Circles::recall( 'join' ) : null;
$daguah_typed   = (array) ( $daguah_form['input'] ?? array() );
$daguah_fields  = (array) ( $daguah_form['fields'] ?? array() );
$daguah_country = (string) ( $daguah_typed['country'] ?? '' );
$daguah_percent = $daguah_board['total'] ? (int) round( 100 * $daguah_board['paid'] / $daguah_board['total'] ) : 0;
$daguah_closes  = '' !== $daguah_board['closes'] ? date_i18n( 'j F Y', (int) strtotime( $daguah_board['closes'] . ' 12:00' ) ) : '';

// The fabric price is also shown in the visitor's currency.
\Daguah\Core\Shop_Assets::enqueue();

$daguah_messages = array(
	'expired' => __( 'That form had been open a long time. Please send it again.', 'daguah' ),
);

$daguah_share = sprintf(
	/* translators: 1: circle name, 2: invite link */
	__( 'Join our DAGUAH Circle for %1$s, so we all wear the same cloth: %2$s', 'daguah' ),
	$daguah_board['title'],
	$daguah_invite
);

/**
 * One fact row.
 *
 * @param string $label Label.
 * @param string $value Value, escaped by the caller.
 */
$daguah_fact = static function ( string $label, string $value ): void {
	printf( '<div class="daguah-circle-facts__row"><dt class="caption-4">%1$s</dt><dd class="body-5">%2$s</dd></div>', esc_html( $label ), $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Value escaped by the caller.
};
?>
<section <?php echo get_block_wrapper_attributes( array( 'id' => 'board', 'aria-labelledby' => 'daguah-circle-title', 'data-circle' => (string) $daguah_id, 'data-key' => $daguah_req['key'], 'data-rest' => esc_url_raw( rest_url( 'daguah/v1/circles/' . $daguah_id . '/board' ) ), 'data-poll' => (string) $daguah_board['poll_seconds'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<?php if ( 'started' === $daguah_done ) : ?>
		<p class="daguah-notice is-success body-5" role="status">
			<?php echo daguah_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
			<span><?php esc_html_e( 'Your circle is ready. Send the link below to everyone who is wearing the cloth.', 'daguah' ); ?></span>
		</p>
	<?php elseif ( 'joined' === $daguah_done ) : ?>
		<p class="daguah-notice is-success body-5" role="status">
			<?php echo daguah_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
			<span><?php esc_html_e( 'You are in the circle. Your place is saved, and you pay your own share in your own currency.', 'daguah' ); ?></span>
		</p>
	<?php endif; ?>

	<div class="daguah-circle__head">
		<p class="daguah-eyebrow"><?php esc_html_e( 'You are invited', 'daguah' ); ?></p>
		<h2 id="daguah-circle-title" class="heading-3"><?php echo esc_html( $daguah_board['title'] ); ?></h2>
		<?php if ( '' !== $daguah_board['host'] ) : ?>
			<p class="body-5 is-muted">
				<?php
				/* translators: %s: host's first name */
				echo esc_html( sprintf( __( 'Hosted by %s', 'daguah' ), $daguah_board['host'] ) );
				?>
			</p>
		<?php endif; ?>
		<?php if ( $daguah_post && '' !== trim( $daguah_post->post_content ) ) : ?>
			<div class="daguah-circle__note body-3"><?php echo wp_kses_post( $daguah_post->post_content ); ?></div>
		<?php endif; ?>
	</div>

	<div class="daguah-circle__grid">
		<div class="daguah-circle__main">
			<div class="daguah-circle-progress">
				<p class="daguah-circle-progress__line title-3">
					<span data-circle-paid><?php echo esc_html( number_format_i18n( $daguah_board['paid'] ) ); ?></span>
					<?php
					/* translators: %s: number of members */
					echo esc_html( sprintf( _n( 'of %s paid', 'of %s paid', $daguah_board['total'], 'daguah' ), number_format_i18n( $daguah_board['total'] ) ) );
					?>
				</p>
				<p class="caption-2 is-muted" data-circle-spread>
					<?php
					/* translators: 1: number of people, 2: number of countries */
					echo esc_html( sprintf( __( '%1$s in the circle, in %2$s', 'daguah' ), sprintf( _n( '%s person', '%s people', $daguah_board['total'], 'daguah' ), number_format_i18n( $daguah_board['total'] ) ), sprintf( _n( '%s country', '%s countries', $daguah_board['countries'], 'daguah' ), number_format_i18n( $daguah_board['countries'] ) ) ) );
					?>
				</p>
				<div class="daguah-circle-progress__bar" role="progressbar" aria-label="<?php esc_attr_e( 'Members who have paid', 'daguah' ); ?>" aria-valuemin="0" aria-valuemax="<?php echo esc_attr( (string) $daguah_board['total'] ); ?>" aria-valuenow="<?php echo esc_attr( (string) $daguah_board['paid'] ); ?>">
					<span style="width:<?php echo esc_attr( (string) $daguah_percent ); ?>%"></span>
				</div>
			</div>

			<ul class="daguah-circle-members" aria-label="<?php esc_attr_e( 'Who is in the circle', 'daguah' ); ?>" aria-live="polite">
				<?php foreach ( $daguah_board['members'] as $daguah_m ) : ?>
					<li class="daguah-circle-member">
						<span class="daguah-circle-member__initial" aria-hidden="true"><?php echo esc_html( mb_substr( $daguah_m['name'], 0, 1 ) ); ?></span>
						<span class="daguah-circle-member__who">
							<span class="title-4">
								<?php echo esc_html( $daguah_m['name'] ); ?>
								<?php if ( $daguah_m['host'] ) : ?>
									<span class="daguah-tag daguah-tag--quiet"><?php esc_html_e( 'Host', 'daguah' ); ?></span>
								<?php endif; ?>
							</span>
							<span class="caption-2 is-muted"><?php echo esc_html( implode( ', ', array_filter( array( $daguah_m['city'], Countries::name( $daguah_m['country'] ) ) ) ) . ' · ' . $daguah_m['currency'] ); ?></span>
						</span>
						<span class="daguah-circle-member__status <?php echo $daguah_m['paid'] ? 'is-paid' : 'is-waiting'; ?> caption-4">
							<?php echo $daguah_m['paid'] ? esc_html__( 'Paid', 'daguah' ) : esc_html__( 'Waiting', 'daguah' ); ?>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<aside class="daguah-circle__side">
			<dl class="daguah-circle-facts">
				<?php
				if ( $daguah_board['fabric'] ) {
					$daguah_fact(
						__( 'Fabric', 'daguah' ),
						sprintf(
							'<a href="%1$s">%2$s</a><br><span class="daguah-price">%3$s</span> <span class="daguah-approx caption-4" data-daguah-gbp="%4$s" hidden></span>',
							esc_url( $daguah_board['fabric']['link'] ),
							esc_html( $daguah_board['fabric']['title'] ),
							wp_kses_post( function_exists( 'wc_price' ) ? wc_price( $daguah_board['fabric']['price'] ) : (string) $daguah_board['fabric']['price'] ),
							esc_attr( (string) $daguah_board['fabric']['price'] )
						)
					);
				}

				/* translators: %s: number of yards */
				$daguah_fact( __( 'Each person', 'daguah' ), esc_html( sprintf( _n( '%s yard', '%s yards', (int) ceil( $daguah_board['yards_per_person'] ), 'daguah' ), number_format_i18n( $daguah_board['yards_per_person'], floor( $daguah_board['yards_per_person'] ) === $daguah_board['yards_per_person'] ? 0 : 1 ) ) ) );

				$daguah_fact(
					__( 'Delivery', 'daguah' ),
					esc_html( 'single' === $daguah_board['delivery_mode'] ? __( 'Everything to the host, who shares it out', 'daguah' ) : __( 'Each person at their own address', 'daguah' ) )
				);

				if ( '' !== $daguah_closes ) {
					/* translators: %s: date */
					$daguah_fact( $daguah_board['closed'] ? __( 'Closed', 'daguah' ) : __( 'Join by', 'daguah' ), esc_html( $daguah_closes ) );
				}
				?>
			</dl>

			<?php
			// Only the host or a member sees the link to pass on; everyone with it can share it anyway.
			$daguah_can_share = $daguah_is_in || ( $daguah_user && (int) get_post_meta( $daguah_id, 'daguah_host', true ) === $daguah_user );
			?>
			<?php if ( $daguah_can_share && ! $daguah_board['closed'] ) : ?>
				<div class="daguah-circle-share">
					<label class="caption-4" for="daguah-circle-link"><?php esc_html_e( 'Invite link', 'daguah' ); ?></label>
					<div class="daguah-circle-share__row">
						<input id="daguah-circle-link" class="daguah-input is-mono" type="text" readonly value="<?php echo esc_attr( $daguah_invite ); ?>">
						<button type="button" class="wp-element-button is-style-outline daguah-copy" data-copy="#daguah-circle-link" data-done="<?php esc_attr_e( 'Copied', 'daguah' ); ?>" hidden>
							<?php echo daguah_icon( 'copy' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
							<?php esc_html_e( 'Copy', 'daguah' ); ?>
						</button>
					</div>
					<a class="caption-2" href="<?php echo esc_url( 'https://wa.me/?text=' . rawurlencode( $daguah_share ) ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Send it on WhatsApp', 'daguah' ); ?>
					</a>
					<p class="caption-5 is-muted"><?php esc_html_e( 'Anyone with this link can see first names and cities in the circle and join it. Share it with family only.', 'daguah' ); ?></p>
				</div>
			<?php endif; ?>
		</aside>
	</div>

	<?php if ( $daguah_is_in ) : ?>
		<div class="daguah-circle-pay">
			<button type="button" class="wp-element-button" disabled aria-describedby="daguah-circle-pay-note"><?php esc_html_e( 'Pay your share', 'daguah' ); ?></button>
			<p id="daguah-circle-pay-note" class="caption-2 is-muted"><?php esc_html_e( 'Checkout for circles opens soon. Your place is saved, and the host can see you have joined.', 'daguah' ); ?></p>
		</div>
	<?php elseif ( $daguah_board['closed'] ) : ?>
		<p class="daguah-notice body-5"><?php esc_html_e( 'This circle has closed to new members. Ask the host about a second circle.', 'daguah' ); ?></p>
	<?php else : ?>
		<form id="join" class="daguah-circle-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
			<h3 class="heading-5"><?php esc_html_e( 'Join this circle', 'daguah' ); ?></h3>

			<?php if ( '' !== $daguah_error ) : ?>
				<p class="daguah-notice is-error body-5" role="alert">
					<?php echo daguah_icon( 'warning-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
					<span><?php echo esc_html( (string) ( $daguah_form['message'] ?? $daguah_messages[ $daguah_error ] ?? __( 'That did not work. Please try again.', 'daguah' ) ) ); ?></span>
				</p>
			<?php endif; ?>

			<input type="hidden" name="action" value="daguah_circle_join">
			<input type="hidden" name="circle_id" value="<?php echo esc_attr( (string) $daguah_id ); ?>">
			<input type="hidden" name="key" value="<?php echo esc_attr( $daguah_req['key'] ); ?>">
			<?php wp_nonce_field( 'daguah_circle_join_' . $daguah_id, '_daguah_nonce', false ); ?>

			<div class="daguah-form-grid">
				<?php
				$daguah_text_field = static function ( string $name, string $label, string $help, string $autocomplete ) use ( $daguah_typed, $daguah_fields ): void {
					$error = (string) ( $daguah_fields[ $name ] ?? '' );
					?>
					<p class="daguah-field">
						<label for="daguah-join-<?php echo esc_attr( $name ); ?>" class="caption-1"><?php echo esc_html( $label ); ?></label>
						<input id="daguah-join-<?php echo esc_attr( $name ); ?>" class="daguah-input" type="text" name="member[<?php echo esc_attr( $name ); ?>]" value="<?php echo esc_attr( (string) ( $daguah_typed[ $name ] ?? '' ) ); ?>" maxlength="60" required autocomplete="<?php echo esc_attr( $autocomplete ); ?>" <?php echo '' !== $error ? 'aria-invalid="true" aria-describedby="daguah-join-' . esc_attr( $name ) . '-error"' : ( '' !== $help ? 'aria-describedby="daguah-join-' . esc_attr( $name ) . '-help"' : '' ); ?>>
						<?php if ( '' !== $error ) : ?>
							<span id="daguah-join-<?php echo esc_attr( $name ); ?>-error" class="caption-2 daguah-field__error"><?php echo esc_html( $error ); ?></span>
						<?php elseif ( '' !== $help ) : ?>
							<span id="daguah-join-<?php echo esc_attr( $name ); ?>-help" class="caption-2 is-muted"><?php echo esc_html( $help ); ?></span>
						<?php endif; ?>
					</p>
					<?php
				};

				$daguah_text_field( 'name', __( 'Your name', 'daguah' ), __( 'Only your first name is shown to the circle.', 'daguah' ), 'name' );
				$daguah_text_field( 'city', __( 'City the fabric goes to', 'daguah' ), '', 'address-level2' );
				?>

				<p class="daguah-field">
					<label for="daguah-join-country" class="caption-1"><?php esc_html_e( 'Country', 'daguah' ); ?></label>
					<select id="daguah-join-country" class="daguah-input" name="member[country]" required data-currency-for="#daguah-join-currency" <?php echo isset( $daguah_fields['country'] ) ? 'aria-invalid="true"' : ''; ?>>
						<option value=""><?php esc_html_e( 'Choose', 'daguah' ); ?></option>
						<?php foreach ( Geo::buyer_countries() as $daguah_code => $daguah_c ) : ?>
							<option value="<?php echo esc_attr( $daguah_code ); ?>" data-currency="<?php echo esc_attr( $daguah_c['currency'] ); ?>" <?php selected( $daguah_country, $daguah_code ); ?>><?php echo esc_html( $daguah_c['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>

				<p class="daguah-field">
					<label for="daguah-join-currency" class="caption-1"><?php esc_html_e( 'You pay in', 'daguah' ); ?></label>
					<select id="daguah-join-currency" class="daguah-input" name="member[currency]">
						<?php foreach ( Countries::CURRENCIES as $daguah_cur ) : ?>
							<option value="<?php echo esc_attr( $daguah_cur ); ?>" <?php selected( (string) ( $daguah_typed['currency'] ?? ( '' !== $daguah_country ? Countries::currency_for( $daguah_country ) : 'GBP' ) ), $daguah_cur ); ?>><?php echo esc_html( $daguah_cur ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
			</div>

			<div class="daguah-circle-form__foot">
				<button type="submit" class="wp-element-button"><?php esc_html_e( 'Join the circle', 'daguah' ); ?></button>
				<?php if ( $daguah_user ) : ?>
					<p class="caption-2 is-muted"><?php esc_html_e( 'Your place is linked to your account.', 'daguah' ); ?></p>
				<?php elseif ( function_exists( 'wc_get_page_permalink' ) ) : ?>
					<p class="caption-2 is-muted">
						<?php esc_html_e( 'Have a DAGUAH account?', 'daguah' ); ?>
						<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Sign in first', 'daguah' ); ?></a>
						<?php esc_html_e( 'so your place is linked to it.', 'daguah' ); ?>
					</p>
				<?php endif; ?>
			</div>
		</form>
	<?php endif; ?>
</section>
