<?php
/**
 * Title: Home: How DAGUAH Hold works
 * Slug: daguah/home-hold
 * Categories: daguah
 * Description: The three steps of buyer protection. Numbered because they happen in this order.
 *
 * @package Daguah
 */

defined( 'ABSPATH' ) || exit;

$daguah_steps = array(
	array( __( 'You pay once, in your currency.', 'daguah' ), __( 'Card, Apple Pay, Google Pay, bank transfer or mobile money.', 'daguah' ) ),
	array( __( 'We hold the payment while the seller ships with tracking.', 'daguah' ), __( 'Follow the parcel from the stall to your door.', 'daguah' ) ),
	array( __( 'You confirm it arrived as described, and the seller is paid.', 'daguah' ), __( 'Something wrong? Report it, and a person on our team looks at both sides and decides.', 'daguah' ) ),
);
?>
<!-- wp:group {"templateLock":"contentOnly","daguahReveal":true,"className":"daguah-section","layout":{"type":"constrained","contentSize":"1280px","justifyContent":"left"}} -->
<div class="wp-block-group daguah-section"><!-- wp:group {"className":"daguah-section__head","layout":{"type":"default"}} -->
<div class="wp-block-group daguah-section__head"><!-- wp:paragraph {"className":"daguah-eyebrow"} -->
<p class="daguah-eyebrow"><?php esc_html_e( 'DAGUAH Hold', 'daguah' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php echo wp_kses( __( 'Your money waits with us <br>until your parcel arrives', 'daguah' ), array( 'br' => array() ) ); ?></h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:group {"tagName":"ol","className":"daguah-grid-3 daguah-steps","layout":{"type":"default"}} -->
<ol class="wp-block-group daguah-grid-3 daguah-steps">
<?php foreach ( $daguah_steps as $daguah_i => list( $daguah_title, $daguah_text ) ) : ?>
<!-- wp:group {"tagName":"li","className":"daguah-step","layout":{"type":"default"}} -->
<li class="wp-block-group daguah-step"><!-- wp:paragraph {"className":"daguah-step__number"} -->
<p class="daguah-step__number"><?php echo esc_html( (string) ( $daguah_i + 1 ) ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3,"className":"title-1"} -->
<h3 class="wp-block-heading title-1"><?php echo esc_html( $daguah_title ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-style-body-6"} -->
<p class="is-style-body-6"><?php echo esc_html( $daguah_text ); ?></p>
<!-- /wp:paragraph --></li>
<!-- /wp:group -->
<?php endforeach; ?>
</ol>
<!-- /wp:group -->

<!-- wp:paragraph {"className":"is-style-body-5"} -->
<p class="is-style-body-5"><a href="/help/"><?php esc_html_e( 'Read how disputes are decided', 'daguah' ); ?></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
