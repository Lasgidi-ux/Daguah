<?php
/**
 * Stall directory block.
 *
 * Every open stall as a card in its own cloth. Filters are plain links
 * (?from=GH, ?verified=1), so the list works without JavaScript and every
 * view can be shared.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

use Daguah\Core\Countries;
use Daguah\Core\Stalls;
use Daguah\Core\Vendor;

defined( 'ABSPATH' ) || exit;

// Read only: filters change what is shown, never what is stored.
$daguah_from     = strtoupper( sanitize_key( wp_unslash( $_GET['from'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
$daguah_verified = ! empty( $_GET['verified'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$daguah_from     = array_key_exists( $daguah_from, Countries::origins() ) ? $daguah_from : '';

$daguah_ids       = Stalls::directory(
	array(
		'from'     => $daguah_from,
		'verified' => $daguah_verified,
	)
);
$daguah_countries = Stalls::countries();
$daguah_base      = is_singular() ? (string) get_permalink() : home_url( '/stalls/' );
$daguah_link      = static fn( array $args ) => add_query_arg( array_filter( $args ), $daguah_base );

// The cards share the featured stalls block's styles.
wp_enqueue_style( 'daguah-featured-stalls-style' );
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<nav class="daguah-directory__filters" aria-label="<?php esc_attr_e( 'Filter stalls', 'daguah' ); ?>">
		<ul class="daguah-directory__chips">
			<li>
				<a class="daguah-chip<?php echo '' === $daguah_from ? ' is-active' : ''; ?>" href="<?php echo esc_url( $daguah_link( array( 'verified' => $daguah_verified ? 1 : 0 ) ) ); ?>" <?php echo '' === $daguah_from ? 'aria-current="true"' : ''; ?>>
					<?php esc_html_e( 'Everywhere', 'daguah' ); ?>
				</a>
			</li>
			<?php foreach ( $daguah_countries as $daguah_code => $daguah_n ) : ?>
				<li>
					<a class="daguah-chip<?php echo $daguah_code === $daguah_from ? ' is-active' : ''; ?>" href="<?php echo esc_url( $daguah_link( array( 'from' => $daguah_code, 'verified' => $daguah_verified ? 1 : 0 ) ) ); ?>" <?php echo $daguah_code === $daguah_from ? 'aria-current="true"' : ''; ?>>
						<?php echo esc_html( Countries::name( $daguah_code ) ); ?>
						<span class="daguah-chip__count"><?php echo esc_html( number_format_i18n( $daguah_n ) ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

		<a class="daguah-directory__toggle caption-1" href="<?php echo esc_url( $daguah_link( array( 'from' => $daguah_from, 'verified' => $daguah_verified ? 0 : 1 ) ) ); ?>" aria-pressed="<?php echo $daguah_verified ? 'true' : 'false'; ?>" role="button">
			<span class="daguah-directory__switch" aria-hidden="true"></span>
			<?php esc_html_e( 'Verified stalls only', 'daguah' ); ?>
		</a>
	</nav>

	<p class="daguah-directory__count body-5" role="status">
		<?php
		/* translators: %s: number of stalls */
		echo esc_html( sprintf( _n( '%s stall', '%s stalls', count( $daguah_ids ), 'daguah' ), number_format_i18n( count( $daguah_ids ) ) ) );
		?>
	</p>

	<?php if ( $daguah_ids ) : ?>
		<ul class="wp-block-daguah-featured-stalls daguah-directory__grid">
			<?php foreach ( $daguah_ids as $daguah_id ) : ?>
				<li><?php echo Vendor::card( $daguah_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside Vendor::card. ?></li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<div class="daguah-empty">
			<div class="daguah-empty__cloth daguah-cloth-box"><?php echo \Daguah\Core\Cloth::svg( 'stalls|' . $daguah_from, 'mix' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from integers and a fixed palette. ?></div>
			<div class="daguah-empty__text">
				<p class="body-3"><?php esc_html_e( 'No stalls match these filters yet. New sellers are checked and added every week.', 'daguah' ); ?></p>
				<a class="wp-element-button is-style-outline" href="<?php echo esc_url( $daguah_base ); ?>"><?php esc_html_e( 'See every stall', 'daguah' ); ?></a>
			</div>
		</div>
	<?php endif; ?>
</div>
