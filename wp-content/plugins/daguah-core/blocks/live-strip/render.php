<?php
/**
 * Market Live strip block.
 *
 * Shows the session on now, or the next one. Times are stored in UTC and
 * printed in UTC; the view script rewrites them in the visitor's own time.
 * Hidden when Market Live is switched off or nothing is scheduled.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

use Daguah\Core\Post_Types;
use Daguah\Core\Settings;
use Daguah\Core\Vendor;

defined( 'ABSPATH' ) || exit;

$daguah_in_editor = defined( 'REST_REQUEST' ) && REST_REQUEST;

if ( ! Settings::flag( 'market_live' ) ) {
	if ( $daguah_in_editor ) {
		printf( '<p %s>%s</p>', get_block_wrapper_attributes(), esc_html__( 'Market Live is switched off in DAGUAH settings, so this strip is hidden.', 'daguah' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core.
	}
	return;
}

$daguah_now     = time();
$daguah_session = null;
$daguah_live    = false;
$daguah_soonest = PHP_INT_MAX;

foreach ( get_posts( array( 'post_type' => Post_Types::LIVE, 'post_status' => 'publish', 'numberposts' => 30 ) ) as $daguah_post ) {
	$daguah_start = strtotime( (string) get_post_meta( $daguah_post->ID, 'daguah_starts_at', true ) . ' UTC' );
	$daguah_end   = strtotime( (string) get_post_meta( $daguah_post->ID, 'daguah_ends_at', true ) . ' UTC' );

	if ( ! $daguah_start ) {
		continue;
	}

	$daguah_end = $daguah_end && $daguah_end > $daguah_start ? $daguah_end : $daguah_start + HOUR_IN_SECONDS;

	if ( $daguah_start <= $daguah_now && $daguah_now <= $daguah_end ) {
		$daguah_session = $daguah_post;
		$daguah_live    = true;
		break;
	}

	if ( $daguah_start > $daguah_now && $daguah_start < $daguah_soonest ) {
		$daguah_soonest = $daguah_start;
		$daguah_session = $daguah_post;
	}
}

if ( ! $daguah_session ) {
	if ( $daguah_in_editor ) {
		printf( '<p %s>%s</p>', get_block_wrapper_attributes(), esc_html__( 'No live session scheduled, so this strip is hidden. Add one under DAGUAH, Live sessions.', 'daguah' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core.
	}
	return;
}

$daguah_start_at = (string) get_post_meta( $daguah_session->ID, 'daguah_starts_at', true );
$daguah_stamp    = (int) strtotime( $daguah_start_at . ' UTC' );
$daguah_stall_id = (int) get_post_meta( $daguah_session->ID, 'daguah_stall', true );
$daguah_market   = (string) get_post_meta( $daguah_session->ID, 'daguah_market', true );
$daguah_tray     = array_slice( array_filter( array_map( 'absint', (array) get_post_meta( $daguah_session->ID, 'daguah_products', true ) ) ), 0, 3 );
$daguah_where    = implode( ', ', array_filter( array( $daguah_stall_id ? Vendor::store_name( $daguah_stall_id ) : '', $daguah_market ) ) );
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => $daguah_live ? 'is-live' : 'is-next', 'aria-label' => __( 'Market Live', 'daguah' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<div class="daguah-live__badge">
		<span class="daguah-live__dot" aria-hidden="true"></span>
		<?php echo $daguah_live ? esc_html__( 'Live now', 'daguah' ) : esc_html__( 'Next on Market Live', 'daguah' ); ?>
	</div>

	<div class="daguah-live__body">
		<p class="daguah-live__title"><?php echo esc_html( get_the_title( $daguah_session ) ); ?></p>
		<p class="daguah-live__meta">
			<?php echo esc_html( $daguah_where ); ?>
			<?php if ( ! $daguah_live ) : ?>
				<span aria-hidden="true">·</span>
				<time datetime="<?php echo esc_attr( gmdate( 'c', $daguah_stamp ) ); ?>" data-local-time><?php echo esc_html( gmdate( 'D j M, H:i', $daguah_stamp ) . ' GMT' ); ?></time>
			<?php endif; ?>
		</p>
	</div>

	<?php if ( $daguah_tray ) : ?>
		<ul class="daguah-live__tray" aria-label="<?php esc_attr_e( 'Pieces in the tray', 'daguah' ); ?>">
			<?php foreach ( $daguah_tray as $daguah_product_id ) : ?>
				<li><?php echo esc_html( get_the_title( $daguah_product_id ) ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<a class="wp-element-button daguah-live__cta" href="<?php echo esc_url( (string) get_permalink( $daguah_session ) ); ?>">
		<?php echo $daguah_live ? esc_html__( 'Watch and shop', 'daguah' ) : esc_html__( 'See the tray', 'daguah' ); ?>
	</a>
</section>
