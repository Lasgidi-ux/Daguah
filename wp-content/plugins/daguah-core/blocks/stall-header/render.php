<?php
/**
 * Stall header block.
 *
 * The stall's cloth across the top, then who they are: name, maker, place,
 * the whole story, the facts buyers ask about, the checks DAGUAH has done,
 * the seller's voice intro when there is one, and their next Market Live.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

use Daguah\Core\Countries;
use Daguah\Core\Live;
use Daguah\Core\Settings;
use Daguah\Core\Stalls;
use Daguah\Core\Vendor;

defined( 'ABSPATH' ) || exit;

$daguah_id = Stalls::current();

if ( ! $daguah_id ) {
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		printf( '<p %s>%s</p>', get_block_wrapper_attributes(), esc_html__( 'The stall header shows on stall pages. There are no stalls yet to preview.', 'daguah' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core.
	}
	return;
}

$daguah_stall  = Vendor::get( $daguah_id );
$daguah_place  = Stalls::place( $daguah_id );
$daguah_maker  = Stalls::maker( $daguah_id );
$daguah_count  = count( Stalls::piece_ids( $daguah_id ) );
$daguah_levels = Vendor::verified_levels();
$daguah_voice  = $daguah_stall['voice_intro'] ? (string) wp_get_attachment_url( $daguah_stall['voice_intro'] ) : '';
$daguah_live   = Settings::flag( 'market_live' ) ? Live::current_or_next( $daguah_id ) : null;

$daguah_checks = array(
	1 => __( 'Identity checked', 'daguah' ),
	2 => __( 'Video call with the DAGUAH team', 'daguah' ),
	3 => __( 'Samples checked for authenticity claims', 'daguah' ),
);

$daguah_facts = array();
if ( $daguah_stall['selling_since'] ) {
	$daguah_facts[] = array( 'storefront', __( 'Selling since', 'daguah' ), (string) $daguah_stall['selling_since'] );
}
if ( $daguah_stall['response_hours'] ) {
	/* translators: %d: number of hours */
	$daguah_facts[] = array( 'clock', __( 'Usually replies', 'daguah' ), sprintf( _n( 'Within %d hour', 'Within %d hours', $daguah_stall['response_hours'], 'daguah' ), $daguah_stall['response_hours'] ) );
}
if ( $daguah_stall['ships_from'] ) {
	$daguah_facts[] = array( 'truck', __( 'Ships from', 'daguah' ), Countries::name( $daguah_stall['ships_from'] ) );
}
/* translators: %s: number of pieces */
$daguah_facts[] = array( 'handbag', __( 'On the stall', 'daguah' ), sprintf( _n( '%s piece', '%s pieces', $daguah_count, 'daguah' ), number_format_i18n( $daguah_count ) ) );
?>
<section <?php echo get_block_wrapper_attributes( array( 'aria-labelledby' => 'daguah-stall-name' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<div class="daguah-stall-hero__cloth daguah-cloth-box" aria-hidden="true">
		<?php echo Vendor::cloth_svg( $daguah_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Cached SVG built from integers and a fixed palette. ?>
	</div>

	<div class="daguah-stall-hero__card">
		<div class="daguah-stall-hero__who">
			<?php if ( $daguah_stall['verified_level'] > 0 ) : ?>
				<span><span class="daguah-tag daguah-tag--verified"><?php echo daguah_icon( 'seal-check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?><?php echo esc_html( $daguah_levels[ $daguah_stall['verified_level'] ] ); ?></span></span>
			<?php endif; ?>

			<h1 id="daguah-stall-name" class="heading-2 daguah-stall-hero__name"><?php echo esc_html( $daguah_stall['name'] ); ?></h1>

			<?php if ( $daguah_maker || $daguah_place ) : ?>
				<p class="body-5 daguah-stall-hero__place">
					<?php echo esc_html( implode( ' · ', array_filter( array( $daguah_maker, $daguah_place ) ) ) ); ?>
				</p>
			<?php endif; ?>

			<?php if ( '' !== $daguah_stall['story'] ) : ?>
				<p class="body-3 daguah-stall-hero__story"><?php echo esc_html( $daguah_stall['story'] ); ?></p>
			<?php endif; ?>

			<?php if ( '' !== $daguah_voice ) : ?>
				<figure class="daguah-stall-hero__voice">
					<figcaption class="caption-2">
						<?php echo daguah_icon( 'speaker-high' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
						<?php
						/* translators: %s: the seller's first name or stall name */
						echo esc_html( sprintf( __( 'Hear from %s', 'daguah' ), (string) ( get_user_meta( $daguah_id, 'first_name', true ) ?: $daguah_stall['name'] ) ) );
						?>
					</figcaption>
					<audio controls preload="none" src="<?php echo esc_url( $daguah_voice ); ?>"></audio>
				</figure>
			<?php endif; ?>
		</div>

		<div class="daguah-stall-hero__side">
			<dl class="daguah-stall-facts">
				<?php foreach ( $daguah_facts as list( $daguah_icon, $daguah_label, $daguah_value ) ) : ?>
					<div class="daguah-stall-facts__row">
						<dt class="caption-4"><?php echo daguah_icon( $daguah_icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?><?php echo esc_html( $daguah_label ); ?></dt>
						<dd class="body-5"><?php echo esc_html( $daguah_value ); ?></dd>
					</div>
				<?php endforeach; ?>
			</dl>

			<div class="daguah-stall-checks">
				<p class="caption-4 daguah-stall-checks__title"><?php esc_html_e( 'What DAGUAH has checked', 'daguah' ); ?></p>
				<ul>
					<?php foreach ( $daguah_checks as $daguah_level => $daguah_check ) : ?>
						<?php $daguah_done = $daguah_stall['verified_level'] >= $daguah_level; ?>
						<li class="<?php echo $daguah_done ? 'is-done' : 'is-todo'; ?>">
							<?php echo daguah_icon( $daguah_done ? 'check' : 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
							<span class="body-6"><?php echo esc_html( $daguah_check ); ?></span>
							<span class="screen-reader-text"><?php echo $daguah_done ? esc_html__( 'Done', 'daguah' ) : esc_html__( 'Not yet', 'daguah' ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
				<a class="caption-2" href="<?php echo esc_url( home_url( '/help/' ) ); ?>"><?php esc_html_e( 'How sellers are checked', 'daguah' ); ?></a>
			</div>

			<?php if ( $daguah_live ) : ?>
				<a class="daguah-stall-live<?php echo Live::NOW === $daguah_live['status'] ? ' is-live' : ''; ?>" href="<?php echo esc_url( $daguah_live['link'] ); ?>">
					<span class="daguah-stall-live__badge caption-4">
						<span class="daguah-live__dot" aria-hidden="true"></span>
						<?php echo Live::NOW === $daguah_live['status'] ? esc_html__( 'Live now', 'daguah' ) : esc_html__( 'On Market Live', 'daguah' ); ?>
					</span>
					<span class="title-4"><?php echo esc_html( $daguah_live['title'] ); ?></span>
					<?php if ( Live::NOW !== $daguah_live['status'] ) : ?>
						<span class="caption-2"><?php echo Live::time_tag( $daguah_live['start'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?></span>
					<?php endif; ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</section>
