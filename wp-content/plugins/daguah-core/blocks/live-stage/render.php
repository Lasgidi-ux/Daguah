<?php
/**
 * Market Live stage block.
 *
 * The session's title, stall and time. While it is on, the player; before
 * it starts, a countdown and a link to add it to a calendar; afterwards the
 * replay when there is one. The player is the stream's own embed, loaded
 * only when the visitor presses play, so no third party loads before then.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

use Daguah\Core\Live;
use Daguah\Core\Stalls;
use Daguah\Core\Vendor;

defined( 'ABSPATH' ) || exit;

$daguah_post_id = (int) ( $block->context['postId'] ?? 0 ) ?: (int) get_the_ID();

if ( ( ! $daguah_post_id || \Daguah\Core\Post_Types::LIVE !== get_post_type( $daguah_post_id ) ) && defined( 'REST_REQUEST' ) && REST_REQUEST ) {
	$daguah_first   = Live::schedule()[0] ?? null;
	$daguah_post_id = $daguah_first ? $daguah_first['id'] : 0;
}

$daguah_s = Live::session( $daguah_post_id );

if ( ! $daguah_s ) {
	return;
}

$daguah_embed = Live::embed_url( $daguah_s['stream'] );
$daguah_where = implode( ', ', array_filter( array( $daguah_s['stall'] ? Vendor::store_name( $daguah_s['stall'] ) : '', $daguah_s['market'] ) ) );
$daguah_plays = '' !== $daguah_embed && Live::LATER !== $daguah_s['status'];
$daguah_badge = array(
	Live::NOW   => __( 'Live now', 'daguah' ),
	Live::LATER => __( 'Coming up', 'daguah' ),
	Live::PAST  => '' !== $daguah_embed ? __( 'Replay', 'daguah' ) : __( 'This session has finished', 'daguah' ),
)[ $daguah_s['status'] ];
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'is-' . $daguah_s['status'], 'aria-labelledby' => 'daguah-live-title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<div class="daguah-stage__screen">
		<?php if ( $daguah_plays ) : ?>
			<div class="daguah-stage__player" data-embed="<?php echo esc_url( $daguah_embed ); ?>">
				<div class="daguah-stage__cloth daguah-cloth-box" aria-hidden="true">
					<?php echo $daguah_s['stall'] ? Vendor::cloth_svg( $daguah_s['stall'] ) : \Daguah\Core\Cloth::svg( 'live|' . $daguah_s['id'], 'mix' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from integers and a fixed palette. ?>
				</div>
				<button type="button" class="daguah-stage__play" hidden>
					<?php echo daguah_icon( 'play' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
					<span><?php echo Live::NOW === $daguah_s['status'] ? esc_html__( 'Watch live', 'daguah' ) : esc_html__( 'Watch the replay', 'daguah' ); ?></span>
				</button>
				<noscript>
					<a class="wp-element-button daguah-stage__out" href="<?php echo esc_url( $daguah_s['stream'] ); ?>"><?php esc_html_e( 'Watch on the stream\'s own site', 'daguah' ); ?></a>
				</noscript>
				<p class="caption-5 daguah-stage__consent"><?php esc_html_e( 'The player loads from the stream\'s own site when you press play.', 'daguah' ); ?></p>
			</div>
		<?php else : ?>
			<div class="daguah-stage__waiting">
				<div class="daguah-stage__cloth daguah-cloth-box" aria-hidden="true">
					<?php echo $daguah_s['stall'] ? Vendor::cloth_svg( $daguah_s['stall'] ) : \Daguah\Core\Cloth::svg( 'live|' . $daguah_s['id'], 'mix' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from integers and a fixed palette. ?>
				</div>
				<div class="daguah-stage__count">
					<?php if ( Live::LATER === $daguah_s['status'] ) : ?>
						<p class="caption-4"><?php esc_html_e( 'Starts in', 'daguah' ); ?></p>
						<p class="heading-3 is-mono" data-countdown="<?php echo esc_attr( gmdate( 'c', $daguah_s['start'] ) ); ?>" data-live-text="<?php esc_attr_e( 'Starting now', 'daguah' ); ?>">
							<?php
							$daguah_left = max( 0, $daguah_s['start'] - time() );
							/* translators: 1: days, 2: hours */
							echo esc_html( sprintf( __( '%1$dd %2$dh', 'daguah' ), intdiv( $daguah_left, DAY_IN_SECONDS ), intdiv( $daguah_left % DAY_IN_SECONDS, HOUR_IN_SECONDS ) ) );
							?>
						</p>
					<?php elseif ( Live::NOW === $daguah_s['status'] ) : ?>
						<p class="body-3"><?php esc_html_e( 'The stall is setting up the stream. Refresh in a minute.', 'daguah' ); ?></p>
					<?php else : ?>
						<p class="body-3"><?php esc_html_e( 'This session has finished. The pieces from the tray are still on the stall.', 'daguah' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<div class="daguah-stage__info">
		<p class="daguah-session__badge caption-4"><span class="daguah-live__dot" aria-hidden="true"></span><?php echo esc_html( $daguah_badge ); ?></p>
		<h1 id="daguah-live-title" class="heading-3"><?php echo esc_html( $daguah_s['title'] ); ?></h1>
		<?php if ( '' !== $daguah_where ) : ?>
			<p class="body-5 is-muted"><?php echo esc_html( $daguah_where ); ?></p>
		<?php endif; ?>
		<p class="caption-1 is-mono daguah-stage__time">
			<?php echo daguah_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
			<?php echo Live::time_tag( $daguah_s['start'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>
			<?php esc_html_e( 'to', 'daguah' ); ?>
			<?php echo Live::time_tag( $daguah_s['end'], 'H:i' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?>
		</p>

		<?php if ( '' !== $daguah_s['excerpt'] ) : ?>
			<p class="body-3"><?php echo esc_html( $daguah_s['excerpt'] ); ?></p>
		<?php endif; ?>

		<?php if ( Live::PAST !== $daguah_s['status'] ) : ?>
			<a class="wp-element-button is-style-outline" href="<?php echo esc_url( Live::calendar_url( $daguah_s['id'] ) ); ?>" download>
				<?php echo daguah_icon( 'calendar-plus' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
				<?php esc_html_e( 'Add to my calendar', 'daguah' ); ?>
			</a>
		<?php endif; ?>

		<p class="caption-2 daguah-stage__hold">
			<?php echo daguah_icon( 'shield-check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
			<?php esc_html_e( 'Everything in the tray is protected by DAGUAH Hold: your payment waits until your parcel arrives.', 'daguah' ); ?>
		</p>

		<?php if ( $daguah_s['stall'] && Vendor::is_vendor( $daguah_s['stall'] ) ) : ?>
			<?php wp_enqueue_style( 'daguah-featured-stalls-style' ); ?>
			<div class="daguah-stage__stall"><?php echo Vendor::card( $daguah_s['stall'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside Vendor::card. ?></div>
		<?php endif; ?>
	</div>
</section>
