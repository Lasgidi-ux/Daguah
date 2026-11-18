<?php
/**
 * Product results block: the pieces in the current shop view.
 *
 * Reads the page's main query (already filtered by Shop_Query), so sorting,
 * paging and every filter come from the address.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

use Daguah\Core\Product_Card;
use Daguah\Core\Shop_Query;

defined( 'ABSPATH' ) || exit;

global $wp_query;

$daguah_p      = Shop_Query::params_from_request();
$daguah_base   = Shop_Query::base_url();
$daguah_chips  = Shop_Query::active_filters( $daguah_p, $daguah_base );
$daguah_total  = (int) ( $wp_query->found_posts ?? 0 );
$daguah_ids    = array_map( static fn( $post ) => (int) $post->ID, (array) ( $wp_query->posts ?? array() ) );
$daguah_order  = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'menu_order'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only.
$daguah_search = get_search_query();

$daguah_orders = array(
	'menu_order' => __( 'Our picks', 'daguah' ),
	'date'       => __( 'Newest', 'daguah' ),
	'price'      => __( 'Price, low to high', 'daguah' ),
	'price-desc' => __( 'Price, high to low', 'daguah' ),
);

$daguah_clear = '' !== $daguah_search ? add_query_arg( array( 's' => $daguah_search, 'post_type' => 'product' ), home_url( '/' ) ) : $daguah_base;
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<div class="daguah-results__bar">
		<p class="daguah-results__count body-5" role="status">
			<?php
			/* translators: %s: number of pieces */
			echo esc_html( sprintf( _n( '%s piece', '%s pieces', $daguah_total, 'daguah' ), number_format_i18n( $daguah_total ) ) );
			?>
		</p>

		<form class="daguah-results__sort" method="get" action="<?php echo esc_url( $daguah_base ); ?>">
			<?php
			// Keep every filter when the order changes.
			foreach ( wp_unslash( $_GET ) as $daguah_key => $daguah_value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only, each value escaped below.
				if ( 'orderby' === $daguah_key || 'paged' === $daguah_key ) {
					continue;
				}
				foreach ( (array) $daguah_value as $daguah_one ) {
					if ( is_scalar( $daguah_one ) ) {
						printf( '<input type="hidden" name="%s" value="%s">', esc_attr( is_array( $daguah_value ) ? $daguah_key . '[]' : $daguah_key ), esc_attr( (string) $daguah_one ) );
					}
				}
			}
			?>
			<label>
				<span class="caption-4"><?php esc_html_e( 'Sort by', 'daguah' ); ?></span>
				<select name="orderby" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()">
					<?php foreach ( $daguah_orders as $daguah_value => $daguah_label ) : ?>
						<option value="<?php echo esc_attr( $daguah_value ); ?>" <?php selected( $daguah_order, $daguah_value ); ?>><?php echo esc_html( $daguah_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<noscript><button type="submit" class="wp-element-button is-style-outline"><?php esc_html_e( 'Sort', 'daguah' ); ?></button></noscript>
		</form>

		<div class="daguah-results__save">
			<?php if ( is_user_logged_in() ) : ?>
				<button type="button" class="wp-element-button is-style-outline daguah-save-search"><?php esc_html_e( 'Save this search', 'daguah' ); ?></button>
				<span class="daguah-save-search__status caption-5" role="status"></span>
			<?php elseif ( function_exists( 'wc_get_page_permalink' ) ) : ?>
				<a class="caption-2" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Sign in to save this search', 'daguah' ); ?></a>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( $daguah_chips ) : ?>
		<ul class="daguah-results__chips" aria-label="<?php esc_attr_e( 'Active filters', 'daguah' ); ?>">
			<?php foreach ( $daguah_chips as $daguah_chip ) : ?>
				<li>
					<a class="daguah-chip" href="<?php echo esc_url( $daguah_chip['url'] ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: filter name */ __( 'Remove filter: %s', 'daguah' ), $daguah_chip['label'] ) ); ?>">
						<?php echo esc_html( $daguah_chip['label'] ); ?>
						<?php echo daguah_icon( 'x' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php
	echo Product_Card::grid( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside Product_Card.
		$daguah_ids,
		$daguah_chips ? __( 'No pieces match all of these filters. Try removing one.', 'daguah' ) : __( 'Nothing on the stalls here yet. New pieces arrive every week.', 'daguah' ),
		$daguah_chips ? sprintf( '<a class="wp-element-button is-style-outline" href="%s">%s</a>', esc_url( $daguah_clear ), esc_html__( 'Clear all filters', 'daguah' ) ) : ''
	);

	$daguah_pages = paginate_links(
		array(
			'total'     => (int) ( $wp_query->max_num_pages ?? 1 ),
			'current'   => max( 1, (int) get_query_var( 'paged' ) ),
			'type'      => 'array',
			'prev_text' => __( 'Previous', 'daguah' ),
			'next_text' => __( 'Next', 'daguah' ),
		)
	);
	?>

	<?php if ( $daguah_pages ) : ?>
		<nav class="daguah-results__pages" aria-label="<?php esc_attr_e( 'More pieces', 'daguah' ); ?>">
			<?php echo wp_kses_post( implode( '', $daguah_pages ) ); ?>
		</nav>
	<?php endif; ?>
</div>
