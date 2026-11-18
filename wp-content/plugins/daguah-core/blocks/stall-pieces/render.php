<?php
/**
 * Stall pieces block.
 *
 * On a stall page Dokan has already made the main query that stall's
 * pieces, paged. Above the grid, the stall's categories link into the shop
 * with the stall filter on, where every other filter works too.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

use Daguah\Core\Product_Card;
use Daguah\Core\Stalls;
use Daguah\Core\Vendor;

defined( 'ABSPATH' ) || exit;

global $wp_query;

$daguah_id = Stalls::current();

if ( ! $daguah_id ) {
	return;
}

$daguah_in_editor = defined( 'REST_REQUEST' ) && REST_REQUEST;
$daguah_ids       = $daguah_in_editor
	? array_slice( Stalls::piece_ids( $daguah_id ), 0, 8 )
	: array_map( static fn( $post ) => (int) $post->ID, (array) ( $wp_query->posts ?? array() ) );
$daguah_cats      = Stalls::categories( $daguah_id );
$daguah_all       = Stalls::shop_url( $daguah_id );
$daguah_name      = Vendor::store_name( $daguah_id );
?>
<section <?php echo get_block_wrapper_attributes( array( 'aria-labelledby' => 'daguah-stall-pieces' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<div class="daguah-stall-pieces__head">
		<h2 id="daguah-stall-pieces" class="heading-4">
			<?php
			/* translators: %s: stall name */
			echo esc_html( sprintf( __( 'On the stall at %s', 'daguah' ), $daguah_name ) );
			?>
		</h2>

		<?php if ( $daguah_cats && '' !== $daguah_all ) : ?>
			<ul class="daguah-stall-pieces__cats" aria-label="<?php esc_attr_e( 'Shop this stall by category', 'daguah' ); ?>">
				<?php foreach ( $daguah_cats as $daguah_cat ) : ?>
					<li>
						<a class="daguah-chip" href="<?php echo esc_url( Stalls::shop_url( $daguah_id, $daguah_cat['slug'] ) ); ?>">
							<?php echo esc_html( $daguah_cat['name'] ); ?>
							<span class="daguah-chip__count"><?php echo esc_html( number_format_i18n( $daguah_cat['count'] ) ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
				<li>
					<a class="daguah-chip daguah-chip--quiet" href="<?php echo esc_url( $daguah_all ); ?>">
						<?php esc_html_e( 'Filter by size, fit and delivery', 'daguah' ); ?>
						<?php echo daguah_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
					</a>
				</li>
			</ul>
		<?php endif; ?>
	</div>

	<?php
	echo Product_Card::grid( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside Product_Card.
		$daguah_ids,
		/* translators: %s: stall name */
		sprintf( __( '%s has nothing on the stall right now. New pieces go up most weeks.', 'daguah' ), $daguah_name ),
		function_exists( 'wc_get_page_permalink' ) ? sprintf( '<a class="wp-element-button is-style-outline" href="%s">%s</a>', esc_url( wc_get_page_permalink( 'shop' ) ), esc_html__( 'Browse the whole market', 'daguah' ) ) : ''
	);

	$daguah_pages = $daguah_in_editor ? array() : paginate_links(
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
		<nav class="daguah-results__pages" aria-label="<?php esc_attr_e( 'More pieces from this stall', 'daguah' ); ?>">
			<?php echo wp_kses_post( implode( '', $daguah_pages ) ); ?>
		</nav>
	<?php endif; ?>
</section>
