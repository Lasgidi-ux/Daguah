<?php
/**
 * Cultural calendar block.
 *
 * The first moment's collection is rendered on the server, so the section
 * is complete without scripts. Each moment is a link to its occasion; the
 * view script turns those links into tabs that swap the collection in place.
 *
 * @package Daguah\Core
 *
 * @var array $attributes Block attributes.
 */

declare( strict_types=1 );

use Daguah\Core\Calendar;
use Daguah\Core\Product_Card;

defined( 'ABSPATH' ) || exit;

$daguah_moments = Calendar::upcoming( max( 3, min( 10, (int) ( $attributes['limit'] ?? 8 ) ) ) );

if ( ! $daguah_moments ) {
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		printf( '<p %s>%s</p>', get_block_wrapper_attributes(), esc_html__( 'No calendar moments yet. Add them under DAGUAH, Calendar moments.', 'daguah' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core.
	}
	return;
}

$daguah_first    = $daguah_moments[0];
$daguah_panel_id = wp_unique_id( 'daguah-calendar-panel-' );
?>
<div <?php echo get_block_wrapper_attributes( array( 'data-endpoint' => esc_url_raw( rest_url( 'daguah/v1/moments/' ) ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<div class="daguah-calendar__ribbon" role="tablist" aria-label="<?php esc_attr_e( 'Cultural calendar', 'daguah' ); ?>">
		<?php foreach ( $daguah_moments as $daguah_i => $daguah_moment ) : ?>
			<a
				class="daguah-calendar__moment<?php echo 0 === $daguah_i ? ' is-selected' : ''; ?>"
				href="<?php echo esc_url( $daguah_moment['link'] ); ?>"
				role="tab"
				id="<?php echo esc_attr( $daguah_panel_id . '-tab-' . $daguah_moment['id'] ); ?>"
				aria-controls="<?php echo esc_attr( $daguah_panel_id ); ?>"
				aria-selected="<?php echo 0 === $daguah_i ? 'true' : 'false'; ?>"
				tabindex="<?php echo 0 === $daguah_i ? '0' : '-1'; ?>"
				data-moment="<?php echo esc_attr( (string) $daguah_moment['id'] ); ?>"
				data-excerpt="<?php echo esc_attr( $daguah_moment['excerpt'] ); ?>"
				data-note="<?php echo esc_attr( $daguah_moment['note'] ); ?>"
			>
				<span class="daguah-calendar__date">
					<?php echo esc_html( Calendar::date_label( $daguah_moment ) ); ?>
					<?php if ( $daguah_moment['now'] ) : ?>
						<span class="daguah-tag"><?php esc_html_e( 'Now', 'daguah' ); ?></span>
					<?php endif; ?>
				</span>
				<span class="daguah-calendar__title"><?php echo esc_html( $daguah_moment['title'] ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>

	<div class="daguah-calendar__panel" id="<?php echo esc_attr( $daguah_panel_id ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $daguah_panel_id . '-tab-' . $daguah_first['id'] ); ?>">
		<div class="daguah-calendar__head">
			<p class="daguah-calendar__excerpt body-3"><?php echo esc_html( $daguah_first['excerpt'] ); ?></p>
			<p class="caption-2 daguah-calendar__note"<?php echo '' === $daguah_first['note'] ? ' hidden' : ''; ?>><?php echo esc_html( $daguah_first['note'] ); ?></p>
			<a class="daguah-calendar__all body-4" href="<?php echo esc_url( $daguah_first['link'] ); ?>"><?php esc_html_e( 'See everything for this', 'daguah' ); ?></a>
		</div>
		<div class="daguah-calendar__collection" aria-live="polite">
			<?php echo Product_Card::grid( Calendar::collection_ids( $daguah_first ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside Product_Card. ?>
		</div>
	</div>
</div>
