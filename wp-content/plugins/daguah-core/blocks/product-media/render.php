<?php
/**
 * Product media block: photos, the short video, or the stall's cloth.
 *
 * Photos scroll sideways with snapping and no script. The video loads only
 * when the buyer asks for it, so nothing is fetched from YouTube, Vimeo or
 * Mux until then. Until a piece has photos, it wears its stall's cloth with
 * a note describing the photo that should be taken.
 *
 * @package Daguah\Core
 *
 * @var array     $attributes Block attributes.
 * @var \WP_Block $block      Block instance.
 */

declare( strict_types=1 );

use Daguah\Core\Product_Data;
use Daguah\Core\Vendor;
use Daguah\Core\Cloth;

use function Daguah\Core\block_product;

defined( 'ABSPATH' ) || exit;

$daguah_product = block_product( $block ?? null );

if ( ! $daguah_product ) {
	return;
}

$daguah_data   = Product_Data::get( $daguah_product->get_id() );
$daguah_images = array_values( array_filter( array_merge( array( (int) $daguah_product->get_image_id() ), array_map( 'intval', $daguah_product->get_gallery_image_ids() ) ) ) );
$daguah_vendor = (int) get_post_field( 'post_author', $daguah_product->get_id() );

// Embed address for the short video, from the link the seller gave.
$daguah_embed = '';
$daguah_video = $daguah_data['video_url'];
if ( '' !== $daguah_video ) {
	$daguah_host = (string) wp_parse_url( $daguah_video, PHP_URL_HOST );
	$daguah_path = (string) wp_parse_url( $daguah_video, PHP_URL_PATH );
	parse_str( (string) wp_parse_url( $daguah_video, PHP_URL_QUERY ), $daguah_query );

	if ( str_contains( $daguah_host, 'youtube.com' ) && ! empty( $daguah_query['v'] ) ) {
		$daguah_embed = 'https://www.youtube-nocookie.com/embed/' . rawurlencode( (string) $daguah_query['v'] );
	} elseif ( 'youtu.be' === $daguah_host ) {
		$daguah_embed = 'https://www.youtube-nocookie.com/embed/' . rawurlencode( trim( $daguah_path, '/' ) );
	} elseif ( str_contains( $daguah_host, 'vimeo.com' ) && preg_match( '/(\d+)/', $daguah_path, $daguah_m ) ) {
		$daguah_embed = 'https://player.vimeo.com/video/' . $daguah_m[1];
	} elseif ( 'stream.mux.com' === $daguah_host ) {
		$daguah_embed = 'https://player.mux.com/' . rawurlencode( (string) preg_replace( '/\.m3u8$/', '', trim( $daguah_path, '/' ) ) );
	}
}
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<?php if ( $daguah_images ) : ?>
		<ul class="daguah-media__track" aria-label="<?php esc_attr_e( 'Photos', 'daguah' ); ?>">
			<?php foreach ( $daguah_images as $daguah_i => $daguah_image ) : ?>
				<li class="daguah-media__slide">
					<?php
					echo wp_get_attachment_image(
						$daguah_image,
						'woocommerce_single',
						false,
						array(
							'sizes'   => '(min-width: 1024px) 50vw, 100vw',
							'loading' => 0 === $daguah_i ? 'eager' : 'lazy',
						)
					);
					?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php if ( count( $daguah_images ) > 1 ) : ?>
			<p class="caption-5 is-muted daguah-media__count">
				<?php
				/* translators: %d: number of photos */
				echo esc_html( sprintf( _n( '%d photo. Swipe to see it.', '%d photos. Swipe to see them all.', count( $daguah_images ), 'daguah' ), count( $daguah_images ) ) );
				?>
			</p>
		<?php endif; ?>
	<?php else : ?>
		<figure class="daguah-media__placeholder">
			<div class="daguah-media__cloth daguah-cloth-box">
				<?php echo $daguah_vendor ? Vendor::cloth_svg( $daguah_vendor ) : Cloth::svg( $daguah_product->get_name(), 'mix' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from integers and a fixed palette. ?>
			</div>
			<?php if ( '' !== $daguah_data['shot_note'] ) : ?>
				<figcaption class="caption-2">
					<?php echo esc_html( sprintf( /* translators: %s: description of the photo to take */ __( 'Photo to come: %s', 'daguah' ), $daguah_data['shot_note'] ) ); ?>
				</figcaption>
			<?php endif; ?>
		</figure>
	<?php endif; ?>

	<?php if ( '' !== $daguah_embed ) : ?>
		<div class="daguah-media__video">
			<button type="button" class="wp-element-button is-style-outline daguah-media__play" data-embed="<?php echo esc_url( $daguah_embed ); ?>">
				<?php echo daguah_icon( 'video-camera' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?>
				<?php esc_html_e( 'Watch the short video', 'daguah' ); ?>
			</button>
			<p class="caption-5 is-muted"><?php esc_html_e( 'Plays from an outside video service once you press play.', 'daguah' ); ?></p>
		</div>
	<?php endif; ?>
</div>
