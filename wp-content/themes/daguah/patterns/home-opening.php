<?php
/**
 * Title: Home: The Opening
 * Slug: daguah/home-opening
 * Categories: daguah
 * Description: The hero. A cloth canopy with lanterns that warm up one by one, the word for market cycling through eight languages, then the headline, over a looping film of hands at work.
 *
 * The film is assets/video/opening.mp4, built by `npm run hero-video` from
 * the clips in media/hero. inc/opening-video.php adds the phone version,
 * the still, a pause button, and holds it still for reduced motion.
 *
 * The proof line is PROTOTYPE DATA from the brief. Replace it with real
 * figures before launch, or remove it.
 *
 * @package Daguah
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:cover {"url":"<?php echo esc_url( get_theme_file_uri( 'assets/video/opening.mp4' ) ); ?>","dimRatio":30,"overlayColor":"night","isUserOverlayColor":true,"backgroundType":"video","align":"full","className":"daguah-opening","layout":{"type":"default"}} -->
<div class="wp-block-cover alignfull daguah-opening"><span aria-hidden="true" class="wp-block-cover__background has-night-background-color has-background-dim-30 has-background-dim"></span><video class="wp-block-cover__video-background intrinsic-ignore" autoplay muted loop playsinline src="<?php echo esc_url( get_theme_file_uri( 'assets/video/opening.mp4' ) ); ?>" data-object-fit="cover"></video><div class="wp-block-cover__inner-container"><!-- wp:daguah/lantern-row {"count":11,"seed":"DAGUAH canopy","family":"kente","align":"full"} /-->

<!-- wp:group {"templateLock":"contentOnly","className":"daguah-opening__content","layout":{"type":"default"}} -->
<div class="wp-block-group daguah-opening__content"><!-- wp:daguah/market-word {"className":"daguah-settle"} /-->

<!-- wp:heading {"level":1,"className":"is-style-hero-heading daguah-settle"} -->
<h1 class="wp-block-heading is-style-hero-heading daguah-settle"><?php echo wp_kses( __( 'Wherever you are, <br>the market is open.', 'daguah' ), array( 'br' => array() ) ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-style-body-3 daguah-measure daguah-settle"} -->
<p class="is-style-body-3 daguah-measure daguah-settle"><?php esc_html_e( 'Heritage fashion, fabric and accessories from verified African makers and the diaspora. Your payment is held until your order arrives.', 'daguah' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"daguah-settle"} -->
<div class="wp-block-buttons daguah-settle"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/shop/"><?php esc_html_e( 'Start shopping', 'daguah' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:paragraph {"className":"is-style-caption-2 daguah-proof daguah-settle"} -->
<p class="is-style-caption-2 daguah-proof daguah-settle"><?php esc_html_e( '4,183 pieces from 612 verified sellers, shipping to 37 countries', 'daguah' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->
