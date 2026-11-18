<?php
/**
 * Title: Home: Final call to action
 * Slug: daguah/home-final-cta
 * Categories: daguah
 * Description: The hero's promise and button again, at the end of the page.
 *
 * @package Daguah
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"templateLock":"contentOnly","daguahReveal":true,"className":"daguah-section","layout":{"type":"constrained","contentSize":"1280px","justifyContent":"left"}} -->
<div class="wp-block-group daguah-section"><!-- wp:group {"className":"daguah-final","layout":{"type":"default"}} -->
<div class="wp-block-group daguah-final"><!-- wp:heading -->
<h2 class="wp-block-heading"><?php echo wp_kses( __( 'Wherever you are, <br>the market is open.', 'daguah' ), array( 'br' => array() ) ); ?></h2>
<!-- /wp:heading -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/shop/"><?php esc_html_e( 'Start shopping', 'daguah' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:paragraph {"className":"is-style-caption-2"} -->
<p class="is-style-caption-2"><?php esc_html_e( 'Your payment is held until your order arrives.', 'daguah' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
