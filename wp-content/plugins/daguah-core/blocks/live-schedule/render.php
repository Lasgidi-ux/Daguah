<?php
/**
 * Market Live schedule block.
 *
 * On now, coming up, and the last few sessions. Times print in GMT and the
 * view script rewrites them into the visitor's own time.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

use Daguah\Core\Live;
use Daguah\Core\Settings;
use Daguah\Core\Vendor;

defined( 'ABSPATH' ) || exit;

if ( ! Settings::flag( 'market_live' ) ) {
	printf( '<p %1$s>%2$s</p>', get_block_wrapper_attributes( array( 'class' => 'daguah-notice body-5' ) ), esc_html__( 'Market Live is resting for now. The stalls are open as usual, and sessions will be back soon.', 'daguah' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core.
	return;
}

$daguah_all      = Live::schedule();
$daguah_upcoming = array_values( array_filter( $daguah_all, static fn( $s ) => Live::PAST !== $s['status'] ) );
$daguah_past     = array_slice( array_values( array_filter( $daguah_all, static fn( $s ) => Live::PAST === $s['status'] ) ), 0, 6 );

/**
 * One session row.
 *
 * @param array $s Session.
 */
$daguah_row = static function ( array $s ): void {
	$where = implode( ', ', array_filter( array( $s['stall'] ? Vendor::store_name( $s['stall'] ) : '', $s['market'] ) ) );
	$label = array(
		Live::NOW   => __( 'Live now', 'daguah' ),
		Live::NEXT  => __( 'Next', 'daguah' ),
		Live::LATER => __( 'Coming up', 'daguah' ),
		Live::PAST  => '' !== $s['stream'] ? __( 'Replay', 'daguah' ) : __( 'Finished', 'daguah' ),
	)[ $s['status'] ];
	?>
	<li class="daguah-session is-<?php echo esc_attr( $s['status'] ); ?>">
		<a class="daguah-session__link" href="<?php echo esc_url( $s['link'] ); ?>">
			<span class="daguah-session__cloth daguah-cloth-box" aria-hidden="true">
				<?php echo $s['stall'] ? Vendor::cloth_svg( $s['stall'] ) : \Daguah\Core\Cloth::svg( 'live|' . $s['id'], 'mix' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from integers and a fixed palette. ?>
			</span>
			<span class="daguah-session__body">
				<span class="daguah-session__badge caption-4"><span class="daguah-live__dot" aria-hidden="true"></span><?php echo esc_html( $label ); ?></span>
				<span class="title-2 daguah-session__title"><?php echo esc_html( $s['title'] ); ?></span>
				<?php if ( '' !== $where ) : ?>
					<span class="caption-2 is-muted"><?php echo esc_html( $where ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== $s['excerpt'] && Live::PAST !== $s['status'] ) : ?>
					<span class="body-6 daguah-session__excerpt"><?php echo esc_html( $s['excerpt'] ); ?></span>
				<?php endif; ?>
			</span>
			<span class="daguah-session__when">
				<?php if ( Live::NOW === $s['status'] ) : ?>
					<span class="caption-1"><?php esc_html_e( 'Watch and shop', 'daguah' ); ?></span>
				<?php else : ?>
					<span class="caption-1 is-mono"><?php echo Live::time_tag( $s['start'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?></span>
				<?php endif; ?>
				<?php if ( $s['products'] ) : ?>
					<span class="caption-2 is-muted">
						<?php
						/* translators: %s: number of pieces */
						echo esc_html( sprintf( _n( '%s piece in the tray', '%s pieces in the tray', count( $s['products'] ), 'daguah' ), number_format_i18n( count( $s['products'] ) ) ) );
						?>
					</span>
				<?php endif; ?>
			</span>
		</a>
	</li>
	<?php
};
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<section aria-labelledby="daguah-live-upcoming">
		<h2 id="daguah-live-upcoming" class="heading-4"><?php esc_html_e( 'On now and coming up', 'daguah' ); ?></h2>

		<?php if ( $daguah_upcoming ) : ?>
			<ul class="daguah-sessions">
				<?php array_map( $daguah_row, $daguah_upcoming ); ?>
			</ul>
		<?php else : ?>
			<div class="daguah-empty">
				<div class="daguah-empty__cloth daguah-cloth-box"><?php echo \Daguah\Core\Cloth::svg( 'live|empty', 'kente' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from integers and a fixed palette. ?></div>
				<div class="daguah-empty__text">
					<p class="body-3"><?php esc_html_e( 'No sessions on the schedule this week. Stalls go live most weekends, so check back soon.', 'daguah' ); ?></p>
					<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
						<a class="wp-element-button is-style-outline" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Browse the market', 'daguah' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
	</section>

	<?php if ( $daguah_past ) : ?>
		<section aria-labelledby="daguah-live-past">
			<h2 id="daguah-live-past" class="heading-5"><?php esc_html_e( 'Recent sessions', 'daguah' ); ?></h2>
			<ul class="daguah-sessions is-past">
				<?php array_map( $daguah_row, $daguah_past ); ?>
			</ul>
		</section>
	<?php endif; ?>
</div>
