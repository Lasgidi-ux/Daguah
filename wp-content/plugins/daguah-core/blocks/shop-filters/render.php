<?php
/**
 * Shop filters block.
 *
 * A plain GET form, so filtering works without scripts and every filtered
 * view is a link. Categories are links (each category is its own page);
 * everything else is a checkbox. With the shop script, choices apply as soon
 * as they change and "Fits me" uses the buyer's Fit Mirror measurements.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

use Daguah\Core\Geo;
use Daguah\Core\Shop_Query;
use Daguah\Core\Taxonomies;

defined( 'ABSPATH' ) || exit;

$daguah_p      = Shop_Query::params_from_request();
$daguah_base   = Shop_Query::base_url();
$daguah_count  = count( Shop_Query::active_filters( $daguah_p, $daguah_base ) );
$daguah_search = get_search_query();
$daguah_order  = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only.
$daguah_shop   = function_exists( 'wc_get_page_id' ) ? (string) get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/shop/' );
$daguah_cat    = is_tax( 'product_cat' ) ? get_queried_object() : null;

$daguah_facets = array(
	'occasion'  => array( __( 'Occasion', 'daguah' ), Taxonomies::OCCASION ),
	'fabric'    => array( __( 'Fabric', 'daguah' ), 'pa_fabric' ),
	'condition' => array( __( 'Condition', 'daguah' ), 'pa_condition' ),
	'size'      => array( __( 'Size', 'daguah' ), 'pa_size' ),
	'origin'    => array( __( 'Made in', 'daguah' ), Taxonomies::ORIGIN ),
);

$daguah_terms = static function ( string $taxonomy ): array {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return array();
	}
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
			'orderby'    => str_starts_with( $taxonomy, 'pa_' ) ? 'menu_order' : 'name',
		)
	);
	return is_array( $terms ) ? $terms : array();
};

$daguah_categories = $daguah_terms( 'product_cat' );
$daguah_categories = array_filter( $daguah_categories, static fn( $t ) => 'uncategorized' !== $t->slug );
?>
<details <?php echo get_block_wrapper_attributes( array( 'class' => 'daguah-filters-drawer' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?> open>
	<summary class="daguah-filters-drawer__summary">
		<?php esc_html_e( 'Filters', 'daguah' ); ?>
		<?php if ( $daguah_count ) : ?>
			<span class="daguah-filters-drawer__count"><?php echo esc_html( (string) $daguah_count ); ?></span>
		<?php endif; ?>
	</summary>

	<form class="daguah-filters" method="get" action="<?php echo esc_url( $daguah_base ); ?>">
		<?php if ( '' !== $daguah_search ) : ?>
			<input type="hidden" name="s" value="<?php echo esc_attr( $daguah_search ); ?>">
			<input type="hidden" name="post_type" value="product">
		<?php endif; ?>
		<?php if ( '' !== $daguah_order ) : ?>
			<input type="hidden" name="orderby" value="<?php echo esc_attr( $daguah_order ); ?>">
		<?php endif; ?>
		<input type="hidden" name="cur" value="<?php echo esc_attr( $daguah_p['cur'] ?? '' ); ?>">
		<input type="hidden" name="fit" value="<?php echo esc_attr( isset( $daguah_p['fit'] ) ? implode( ',', array_map( static fn( $k, $v ) => $k . ':' . $v, array_keys( $daguah_p['fit'] ), $daguah_p['fit'] ) ) : '' ); ?>">

		<?php if ( $daguah_categories ) : ?>
			<nav class="daguah-filters__group" aria-label="<?php esc_attr_e( 'Categories', 'daguah' ); ?>">
				<p class="daguah-filters__legend"><?php esc_html_e( 'Category', 'daguah' ); ?></p>
				<ul class="daguah-filters__cats">
					<li><a href="<?php echo esc_url( Shop_Query::url( $daguah_shop, $daguah_p ) ); ?>"<?php echo $daguah_cat ? '' : ' aria-current="page"'; ?>><?php esc_html_e( 'Everything', 'daguah' ); ?></a></li>
					<?php foreach ( $daguah_categories as $daguah_term ) : ?>
						<li><a href="<?php echo esc_url( Shop_Query::url( (string) get_term_link( $daguah_term ), $daguah_p ) ); ?>"<?php echo $daguah_cat && $daguah_cat->term_id === $daguah_term->term_id ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $daguah_term->name ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>

		<fieldset class="daguah-filters__group">
			<legend class="daguah-filters__legend"><?php esc_html_e( 'Delivery', 'daguah' ); ?></legend>
			<label class="daguah-filters__select">
				<span class="caption-4"><?php esc_html_e( 'Delivering to', 'daguah' ); ?></span>
				<select name="to" data-daguah-country<?php echo isset( $daguah_p['to'] ) ? ' data-keep="1"' : ''; ?>>
					<?php foreach ( Geo::buyer_countries() as $daguah_code => $daguah_info ) : ?>
						<option value="<?php echo esc_attr( $daguah_code ); ?>" <?php selected( $daguah_p['to'] ?? 'GB', $daguah_code ); ?>><?php echo esc_html( $daguah_info['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label class="daguah-check"><input type="checkbox" name="ships" value="1" <?php checked( ! empty( $daguah_p['ships'] ) ); ?>><span><?php esc_html_e( 'Only pieces that ship to me', 'daguah' ); ?></span></label>
			<label class="daguah-filters__select">
				<span class="caption-4"><?php esc_html_e( 'Arrives within', 'daguah' ); ?></span>
				<select name="days">
					<option value=""><?php esc_html_e( 'Any time', 'daguah' ); ?></option>
					<?php foreach ( array( 5, 7, 10, 14 ) as $daguah_days ) : ?>
						<option value="<?php echo esc_attr( (string) $daguah_days ); ?>" <?php selected( $daguah_p['days'] ?? 0, $daguah_days ); ?>><?php echo esc_html( sprintf( /* translators: %d: days */ __( '%d days', 'daguah' ), $daguah_days ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</fieldset>

		<fieldset class="daguah-filters__group">
			<legend class="daguah-filters__legend"><?php esc_html_e( 'Fit', 'daguah' ); ?></legend>
			<label class="daguah-check"><input type="checkbox" name="fit_toggle" value="1" <?php checked( ! empty( $daguah_p['fit'] ) ); ?>><span><?php esc_html_e( 'Fits me', 'daguah' ); ?></span></label>
			<p class="daguah-filters__fit-help caption-5">
				<?php esc_html_e( 'Save your measurements in Fit Mirror to use this.', 'daguah' ); ?>
				<button type="button" class="daguah-link-button" data-open-fit-mirror><?php esc_html_e( 'Open Fit Mirror', 'daguah' ); ?></button>
			</p>
		</fieldset>

		<?php foreach ( $daguah_facets as $daguah_key => list( $daguah_label, $daguah_taxonomy ) ) : ?>
			<?php
			$daguah_list = $daguah_terms( $daguah_taxonomy );
			if ( ! $daguah_list ) {
				continue;
			}
			?>
			<fieldset class="daguah-filters__group">
				<legend class="daguah-filters__legend"><?php echo esc_html( $daguah_label ); ?></legend>
				<?php foreach ( $daguah_list as $daguah_term ) : ?>
					<label class="daguah-check">
						<input type="checkbox" name="<?php echo esc_attr( $daguah_key ); ?>[]" value="<?php echo esc_attr( $daguah_term->slug ); ?>" <?php checked( in_array( $daguah_term->slug, $daguah_p[ $daguah_key ] ?? array(), true ) ); ?>>
						<span><?php echo esc_html( $daguah_term->name ); ?></span>
					</label>
				<?php endforeach; ?>
			</fieldset>
		<?php endforeach; ?>

		<fieldset class="daguah-filters__group">
			<legend class="daguah-filters__legend">
				<?php esc_html_e( 'Price', 'daguah' ); ?>
				<span class="daguah-filters__currency"><?php echo esc_html( $daguah_p['cur'] ?? 'GBP' ); ?></span>
			</legend>
			<div class="daguah-filters__range">
				<label><span class="caption-4"><?php esc_html_e( 'From', 'daguah' ); ?></span><input type="number" name="min" min="0" step="1" inputmode="numeric" value="<?php echo esc_attr( isset( $daguah_p['min'] ) ? (string) $daguah_p['min'] : '' ); ?>"></label>
				<label><span class="caption-4"><?php esc_html_e( 'To', 'daguah' ); ?></span><input type="number" name="max" min="0" step="1" inputmode="numeric" value="<?php echo esc_attr( isset( $daguah_p['max'] ) ? (string) $daguah_p['max'] : '' ); ?>"></label>
			</div>
		</fieldset>

		<fieldset class="daguah-filters__group">
			<legend class="daguah-filters__legend"><?php esc_html_e( 'Sellers', 'daguah' ); ?></legend>
			<label class="daguah-check"><input type="checkbox" name="verified" value="1" <?php checked( ! empty( $daguah_p['verified'] ) ); ?>><span><?php esc_html_e( 'Verified sellers only', 'daguah' ); ?></span></label>
		</fieldset>

		<div class="daguah-filters__actions">
			<button type="submit" class="wp-element-button"><?php esc_html_e( 'Show pieces', 'daguah' ); ?></button>
			<?php if ( $daguah_count ) : ?>
				<a class="daguah-filters__clear body-5" href="<?php echo esc_url( '' !== $daguah_search ? add_query_arg( array( 's' => $daguah_search, 'post_type' => 'product' ), home_url( '/' ) ) : $daguah_base ); ?>"><?php esc_html_e( 'Clear all', 'daguah' ); ?></a>
			<?php endif; ?>
		</div>
	</form>
</details>
