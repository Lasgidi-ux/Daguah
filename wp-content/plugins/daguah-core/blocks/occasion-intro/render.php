<?php
/**
 * Occasion intro block.
 *
 * Opens an occasion page: the occasion's cloth, its name and description,
 * how many pieces are tagged, when it next comes round on the cultural
 * calendar, and, for occasions families dress for together, a way into
 * DAGUAH Circle. Gifting occasions point to Send Home instead.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

use Daguah\Core\Calendar;
use Daguah\Core\Cloth;
use Daguah\Core\Settings;
use Daguah\Core\Taxonomies;

defined( 'ABSPATH' ) || exit;

$daguah_term = get_queried_object();

if ( ! $daguah_term instanceof WP_Term && defined( 'REST_REQUEST' ) && REST_REQUEST ) {
	$daguah_terms = get_terms(
		array(
			'taxonomy' => Taxonomies::OCCASION,
			'orderby'  => 'count',
			'order'    => 'DESC',
			'number'   => 1,
		)
	);
	$daguah_term  = is_array( $daguah_terms ) ? ( $daguah_terms[0] ?? null ) : null;
}

if ( ! $daguah_term instanceof WP_Term || Taxonomies::OCCASION !== $daguah_term->taxonomy ) {
	return;
}

$daguah_next = null;
foreach ( Calendar::upcoming( 50 ) as $daguah_moment ) {
	if ( $daguah_moment['occasion'] === (int) $daguah_term->term_id ) {
		$daguah_next = $daguah_moment;
		break;
	}
}

/**
 * Occasions families dress for together, which suggest starting a circle.
 *
 * @param string[] $slugs Occasion slugs.
 */
$daguah_together = (array) apply_filters( 'daguah_circle_occasions', array( 'wedding-guest-aso-ebi', 'traditional-wedding', 'naming-ceremony', 'funerals-and-remembrance' ) );

/**
 * Occasions that are about sending something home, which suggest Send Home.
 *
 * @param string[] $slugs Occasion slugs.
 */
$daguah_gifting = (array) apply_filters( 'daguah_send_home_occasions', array( 'gifts-for-home', 'detty-december' ) );

$daguah_prompt = null;
if ( in_array( $daguah_term->slug, $daguah_together, true ) && Settings::flag( 'circle' ) ) {
	$daguah_prompt = array( 'users-three', __( 'Dressing the whole family?', 'daguah' ), __( 'Start a DAGUAH Circle: one cloth, everyone pays their own share from wherever they live.', 'daguah' ), home_url( '/circle/' ), __( 'Start a circle', 'daguah' ) );
} elseif ( in_array( $daguah_term->slug, $daguah_gifting, true ) && Settings::flag( 'send_home' ) ) {
	$daguah_prompt = array( 'gift', __( 'Sending it to family?', 'daguah' ), __( 'Send Home delivers to their door with a card that plays your voice.', 'daguah' ), home_url( '/send-home/' ), __( 'Send something home', 'daguah' ) );
}

$daguah_families = array( 'adire', 'kente', 'bogolan', 'kuba', 'mix' );
?>
<section <?php echo get_block_wrapper_attributes( array( 'aria-labelledby' => 'daguah-occasion-name' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<div class="daguah-occasion-intro__cloth daguah-cloth-box" aria-hidden="true">
		<?php echo Cloth::svg( 'occasion|' . $daguah_term->slug, $daguah_families[ $daguah_term->term_id % count( $daguah_families ) ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from integers and a fixed palette; the same cloth as the occasion's tile. ?>
	</div>

	<div class="daguah-occasion-intro__text">
		<p class="daguah-eyebrow"><?php esc_html_e( 'Occasion', 'daguah' ); ?></p>
		<h1 id="daguah-occasion-name" class="heading-2"><?php echo esc_html( $daguah_term->name ); ?></h1>
		<?php if ( '' !== $daguah_term->description ) : ?>
			<p class="body-3 daguah-section__lead"><?php echo esc_html( $daguah_term->description ); ?></p>
		<?php endif; ?>

		<ul class="daguah-occasion-intro__facts">
			<li class="caption-1">
				<?php echo daguah_icon( 'handbag' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
				<?php
				/* translators: %s: number of pieces */
				echo esc_html( sprintf( _n( '%s piece', '%s pieces', (int) $daguah_term->count, 'daguah' ), number_format_i18n( (int) $daguah_term->count ) ) );
				?>
			</li>
			<?php if ( $daguah_next ) : ?>
				<li class="caption-1">
					<?php echo daguah_icon( 'calendar-plus' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
					<?php
					echo esc_html(
						$daguah_next['now']
							/* translators: 1: moment name, 2: dates */
							? sprintf( __( 'On now: %1$s, %2$s', 'daguah' ), $daguah_next['title'], Calendar::date_label( $daguah_next ) )
							/* translators: 1: moment name, 2: dates */
							: sprintf( __( 'Next: %1$s, %2$s', 'daguah' ), $daguah_next['title'], Calendar::date_label( $daguah_next ) )
					);
					?>
				</li>
			<?php endif; ?>
		</ul>
	</div>

	<?php if ( $daguah_prompt ) : ?>
		<?php list( $daguah_icon, $daguah_title, $daguah_text, $daguah_url, $daguah_cta ) = $daguah_prompt; ?>
		<aside class="daguah-occasion-intro__prompt">
			<span class="daguah-occasion-intro__icon" aria-hidden="true"><?php echo daguah_icon( $daguah_icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?></span>
			<div>
				<p class="title-3"><?php echo esc_html( $daguah_title ); ?></p>
				<p class="body-6"><?php echo esc_html( $daguah_text ); ?></p>
			</div>
			<a class="wp-element-button is-style-outline" href="<?php echo esc_url( $daguah_url ); ?>"><?php echo esc_html( $daguah_cta ); ?></a>
		</aside>
	<?php endif; ?>
</section>
