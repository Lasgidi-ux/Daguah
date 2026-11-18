<?php
/**
 * Shop by occasion block.
 *
 * Chosen occasions in the order picked, or the occasions with the most
 * pieces. With "all" on, every occasion that has pieces; with "dates" on,
 * each tile also says when the occasion next comes round on the calendar. Each tile's cloth is seeded by the occasion's name, so it keeps
 * the same cloth wherever it appears.
 *
 * @package Daguah\Core
 *
 * @var array $attributes Block attributes.
 */

declare( strict_types=1 );

use Daguah\Core\Calendar;
use Daguah\Core\Cloth;
use Daguah\Core\Taxonomies;

defined( 'ABSPATH' ) || exit;

$daguah_ids   = array_values( array_filter( array_map( 'absint', (array) ( $attributes['ids'] ?? array() ) ) ) );
$daguah_limit = ! empty( $attributes['all'] ) ? 0 : max( 2, min( 12, (int) ( $attributes['limit'] ?? 8 ) ) );
$daguah_dates = ! empty( $attributes['dates'] );

$daguah_terms = get_terms(
	$daguah_ids
		? array(
			'taxonomy'   => Taxonomies::OCCASION,
			'include'    => $daguah_ids,
			'orderby'    => 'include',
			'hide_empty' => false,
		)
		: array(
			'taxonomy'   => Taxonomies::OCCASION,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'hide_empty' => true,
			'number'     => $daguah_limit,
		)
);

if ( ! is_array( $daguah_terms ) || ! $daguah_terms ) {
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		printf( '<p %s>%s</p>', get_block_wrapper_attributes(), esc_html__( 'No occasions with pieces yet. Tag pieces with an occasion on the product screen.', 'daguah' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core.
	}
	return;
}

$daguah_families = array( 'adire', 'kente', 'bogolan', 'kuba', 'mix' );

// The soonest calendar moment for each occasion.
$daguah_next = array();
if ( $daguah_dates ) {
	foreach ( Calendar::upcoming( 50 ) as $daguah_moment ) {
		if ( $daguah_moment['occasion'] && ! isset( $daguah_next[ $daguah_moment['occasion'] ] ) ) {
			$daguah_next[ $daguah_moment['occasion'] ] = $daguah_moment;
		}
	}
}
?>
<ul <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<?php foreach ( $daguah_terms as $daguah_term ) : ?>
		<li>
			<a class="daguah-occasion" href="<?php echo esc_url( (string) get_term_link( $daguah_term ) ); ?>">
				<span class="daguah-occasion__cloth daguah-cloth-box" aria-hidden="true">
					<?php echo Cloth::svg( 'occasion|' . $daguah_term->slug, $daguah_families[ $daguah_term->term_id % count( $daguah_families ) ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from integers and a fixed palette. ?>
				</span>
				<span class="daguah-occasion__label">
					<span class="daguah-occasion__name"><?php echo esc_html( $daguah_term->name ); ?></span>
					<span class="daguah-occasion__count">
						<?php
						/* translators: %d: number of pieces */
						echo esc_html( sprintf( _n( '%d piece', '%d pieces', (int) $daguah_term->count, 'daguah' ), (int) $daguah_term->count ) );
						?>
					</span>
					<?php if ( isset( $daguah_next[ $daguah_term->term_id ] ) ) : ?>
						<span class="daguah-occasion__next">
							<?php
							$daguah_m = $daguah_next[ $daguah_term->term_id ];
							echo esc_html(
								$daguah_m['now']
									? __( 'On now', 'daguah' )
									/* translators: %s: dates */
									: sprintf( __( 'Next: %s', 'daguah' ), Calendar::date_label( $daguah_m ) )
							);
							?>
						</span>
					<?php endif; ?>
				</span>
			</a>
		</li>
	<?php endforeach; ?>
</ul>
