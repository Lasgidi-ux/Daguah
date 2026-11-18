<?php
/**
 * Send Home block.
 *
 * Pick the country the gift is going to, and see the pieces that deliver
 * there, fastest first. The country is a plain GET field (?to=NG), so the
 * picker works without JavaScript and the view can be shared.
 *
 * @package Daguah\Core
 *
 * @var array $attributes Block attributes.
 */

declare( strict_types=1 );

use Daguah\Core\Countries;
use Daguah\Core\Geo;
use Daguah\Core\Product_Card;
use Daguah\Core\Settings;
use Daguah\Core\Shop_Query;

defined( 'ABSPATH' ) || exit;

if ( ! Settings::flag( 'send_home' ) ) {
	printf( '<p %1$s>%2$s</p>', get_block_wrapper_attributes( array( 'class' => 'daguah-notice body-5' ) ), esc_html__( 'Send Home is resting for now. You can still order any piece to any address at checkout.', 'daguah' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core.
	return;
}

$daguah_countries = Geo::buyer_countries();
$daguah_quick     = array_values( array_intersect( array( 'NG', 'GH', 'KE' ), array_keys( $daguah_countries ) ) );
$daguah_to        = strtoupper( sanitize_key( wp_unslash( $_GET['to'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read only, checked against the list.
$daguah_to        = array_key_exists( $daguah_to, $daguah_countries ) ? $daguah_to : ( $daguah_quick[0] ?? (string) array_key_first( $daguah_countries ) );
$daguah_zone      = (string) Countries::zone_for( $daguah_to );
$daguah_limit     = max( 4, min( 24, (int) ( $attributes['limit'] ?? 8 ) ) );
$daguah_base      = is_singular() ? (string) get_permalink() : home_url( '/send-home/' );
$daguah_name      = (string) ( $daguah_countries[ $daguah_to ]['name'] ?? Countries::name( $daguah_to ) );

$daguah_ids = '' === $daguah_zone ? array() : get_posts(
	array(
		'post_type'    => 'product',
		'post_status'  => 'publish',
		'numberposts'  => $daguah_limit,
		'fields'       => 'ids',
		'meta_key'     => '_daguah_days_' . $daguah_zone, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Indexed per zone for exactly this.
		'orderby'      => array(
			'meta_value_num' => 'ASC',
			'menu_order'     => 'ASC',
		),
		'post__not_in' => Shop_Query::hidden_ids(), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- A handful of IDs.
	)
);

$daguah_shop = function_exists( 'wc_get_page_permalink' )
	? Shop_Query::url(
		(string) wc_get_page_permalink( 'shop' ),
		array(
			'to'    => $daguah_to,
			'ships' => 1,
		)
	)
	: '';

$daguah_fastest = $daguah_ids ? (int) get_post_meta( (int) $daguah_ids[0], '_daguah_days_' . $daguah_zone, true ) : 0;
?>
<section <?php echo get_block_wrapper_attributes( array( 'aria-labelledby' => 'daguah-send-title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<div class="daguah-send__picker">
		<h2 id="daguah-send-title" class="heading-4"><?php esc_html_e( 'Where is it going?', 'daguah' ); ?></h2>

		<ul class="daguah-send__quick" aria-label="<?php esc_attr_e( 'Popular destinations', 'daguah' ); ?>">
			<?php foreach ( $daguah_quick as $daguah_code ) : ?>
				<li>
					<a class="daguah-chip<?php echo $daguah_code === $daguah_to ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'to', $daguah_code, $daguah_base ) . '#daguah-send-title' ); ?>" <?php echo $daguah_code === $daguah_to ? 'aria-current="true"' : ''; ?>>
						<?php echo esc_html( $daguah_countries[ $daguah_code ]['name'] ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

		<form class="daguah-send__form" method="get" action="<?php echo esc_url( $daguah_base ); ?>">
			<label for="daguah-send-to" class="caption-4"><?php esc_html_e( 'Another country', 'daguah' ); ?></label>
			<select id="daguah-send-to" class="daguah-input" name="to" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()">
				<?php foreach ( $daguah_countries as $daguah_code => $daguah_c ) : ?>
					<option value="<?php echo esc_attr( $daguah_code ); ?>" <?php selected( $daguah_to, $daguah_code ); ?>><?php echo esc_html( $daguah_c['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
			<noscript><button type="submit" class="wp-element-button is-style-outline"><?php esc_html_e( 'Show', 'daguah' ); ?></button></noscript>
		</form>
	</div>

	<p class="body-5 daguah-send__summary" role="status">
		<?php
		if ( $daguah_ids ) {
			echo esc_html(
				sprintf(
					/* translators: 1: country, 2: number of days */
					_n( 'Pieces that deliver to %1$s, fastest first. The quickest arrives in %2$d day.', 'Pieces that deliver to %1$s, fastest first. The quickest arrives in %2$d days.', $daguah_fastest, 'daguah' ),
					$daguah_name,
					$daguah_fastest
				)
			);
		}
		?>
	</p>

	<?php
	echo Product_Card::grid( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside Product_Card.
		$daguah_ids,
		/* translators: %s: country */
		sprintf( __( 'Nothing on the stalls delivers to %s yet. We are adding routes every month.', 'daguah' ), $daguah_name )
	);
	?>

	<?php if ( $daguah_ids && '' !== $daguah_shop ) : ?>
		<p>
			<a class="wp-element-button is-style-outline" href="<?php echo esc_url( $daguah_shop ); ?>">
				<?php
				/* translators: %s: country */
				echo esc_html( sprintf( __( 'Every piece that ships to %s', 'daguah' ), $daguah_name ) );
				?>
				<?php echo daguah_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
			</a>
		</p>
	<?php endif; ?>
</section>
