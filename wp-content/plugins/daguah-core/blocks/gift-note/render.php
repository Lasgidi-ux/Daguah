<?php
/**
 * Gift note block.
 *
 * Who it is for, a written note, who it is from and a voice message of up
 * to a minute, recorded in the browser. A card beside the form shows what
 * the family will receive. The note is kept on this device until checkout,
 * which attaches it to the order (Stage 6); nothing is uploaded before then.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

use Daguah\Core\Settings;

defined( 'ABSPATH' ) || exit;

if ( ! Settings::flag( 'send_home' ) ) {
	return;
}

$daguah_limit = 300;
?>
<section <?php echo get_block_wrapper_attributes( array( 'aria-labelledby' => 'daguah-note-title', 'data-limit' => (string) $daguah_limit, 'data-seconds' => '60' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<div class="daguah-note__form">
		<div class="daguah-section__head">
			<p class="daguah-eyebrow"><?php esc_html_e( 'The gift note', 'daguah' ); ?></p>
			<h2 id="daguah-note-title" class="heading-4"><?php esc_html_e( 'Say it in your own voice', 'daguah' ); ?></h2>
			<p class="body-5 daguah-section__lead"><?php esc_html_e( 'Every Send Home parcel carries a printed card. Scan it, and your voice plays, wherever they are.', 'daguah' ); ?></p>
		</div>

		<noscript>
			<p class="daguah-notice body-5"><?php esc_html_e( 'Writing the note and recording your voice need JavaScript. You can also add a note at checkout.', 'daguah' ); ?></p>
		</noscript>

		<form class="daguah-note__fields" hidden>
			<p class="daguah-field">
				<label for="daguah-note-to" class="caption-1"><?php esc_html_e( 'For', 'daguah' ); ?></label>
				<input id="daguah-note-to" class="daguah-input" type="text" name="to" maxlength="60" placeholder="<?php esc_attr_e( 'Mummy, Uncle Kofi, Grandma', 'daguah' ); ?>">
			</p>

			<p class="daguah-field">
				<label for="daguah-note-message" class="caption-1"><?php esc_html_e( 'Your note', 'daguah' ); ?></label>
				<textarea id="daguah-note-message" class="daguah-input" name="message" rows="4" maxlength="<?php echo esc_attr( (string) $daguah_limit ); ?>" aria-describedby="daguah-note-count"></textarea>
				<span id="daguah-note-count" class="caption-2 is-muted is-mono" aria-live="polite"><?php echo esc_html( sprintf( /* translators: %d: characters left */ __( '%d characters left', 'daguah' ), $daguah_limit ) ); ?></span>
			</p>

			<p class="daguah-field">
				<label for="daguah-note-from" class="caption-1"><?php esc_html_e( 'From', 'daguah' ); ?></label>
				<input id="daguah-note-from" class="daguah-input" type="text" name="from" maxlength="60" autocomplete="given-name">
			</p>

			<div class="daguah-recorder" data-state="idle">
				<p class="caption-1"><?php esc_html_e( 'Voice message, up to a minute', 'daguah' ); ?></p>
				<div class="daguah-recorder__controls">
					<button type="button" class="wp-element-button is-style-outline daguah-recorder__record">
						<?php echo daguah_icon( 'microphone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
						<span><?php esc_html_e( 'Record', 'daguah' ); ?></span>
					</button>
					<button type="button" class="wp-element-button daguah-recorder__stop" hidden>
						<?php echo daguah_icon( 'stop' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
						<span><?php esc_html_e( 'Stop', 'daguah' ); ?></span>
					</button>
					<span class="daguah-recorder__time caption-1 is-mono" aria-hidden="true">0:00</span>
					<button type="button" class="daguah-link-button caption-2 daguah-recorder__delete" hidden>
						<?php echo daguah_icon( 'trash' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
						<?php esc_html_e( 'Record again', 'daguah' ); ?>
					</button>
				</div>
				<audio class="daguah-recorder__audio" controls hidden></audio>
				<p class="caption-2 daguah-recorder__message" role="status"></p>
			</div>

			<div class="daguah-note__actions">
				<button type="submit" class="wp-element-button"><?php esc_html_e( 'Keep this note', 'daguah' ); ?></button>
				<button type="button" class="daguah-link-button caption-1 daguah-note__clear"><?php esc_html_e( 'Start again', 'daguah' ); ?></button>
			</div>
			<p class="caption-2 daguah-note__status" role="status"></p>
			<p class="caption-5 is-muted"><?php esc_html_e( 'Your note and recording stay on this device until checkout attaches them to your order. Nothing is uploaded before then.', 'daguah' ); ?></p>
		</form>
	</div>

	<figure class="daguah-note__card" aria-label="<?php esc_attr_e( 'How the card will look', 'daguah' ); ?>">
		<div class="daguah-note__cloth daguah-cloth-box" aria-hidden="true">
			<?php echo \Daguah\Core\Cloth::svg( 'send-home|card', 'adire' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from integers and a fixed palette. ?>
		</div>
		<div class="daguah-note__paper">
			<p class="caption-4 daguah-note__for" data-preview="to" data-empty="<?php esc_attr_e( 'For someone at home', 'daguah' ); ?>"><?php esc_html_e( 'For someone at home', 'daguah' ); ?></p>
			<p class="body-3 daguah-note__text" data-preview="message" data-empty="<?php esc_attr_e( 'Your note appears here, in your words.', 'daguah' ); ?>"><?php esc_html_e( 'Your note appears here, in your words.', 'daguah' ); ?></p>
			<p class="caption-1 daguah-note__from" data-preview="from" data-empty="" data-prefix="<?php esc_attr_e( 'With love,', 'daguah' ); ?>"></p>
			<p class="caption-2 daguah-note__voice" hidden>
				<?php echo daguah_icon( 'speaker-high' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
				<?php esc_html_e( 'Scan to hear my voice', 'daguah' ); ?>
			</p>
		</div>
	</figure>
</section>
