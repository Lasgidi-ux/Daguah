<?php
/**
 * Title: Home: Benefits
 * Slug: daguah/home-benefits
 * Categories: daguah
 * Description: Four promises every piece comes with.
 *
 * @package Daguah
 */

defined( 'ABSPATH' ) || exit;

$daguah_benefits = array(
	array( 'shield-check', __( 'Paid only when it arrives.', 'daguah' ), __( 'DAGUAH Hold keeps your money until you confirm delivery.', 'daguah' ) ),
	array( 'receipt', __( 'See the full price first.', 'daguah' ), __( 'Item, delivery and duties in your own currency before you pay.', 'daguah' ) ),
	array( 'ruler', __( 'Know it fits.', 'daguah' ), __( 'Save your measurements once and every piece tells you if it fits.', 'daguah' ) ),
	array( 'map-pin', __( 'Know who made it.', 'daguah' ), __( 'Every piece shows where it was made and who made it.', 'daguah' ) ),
);
?>
<!-- wp:group {"templateLock":"contentOnly","daguahReveal":true,"className":"daguah-section","layout":{"type":"constrained","contentSize":"1280px","justifyContent":"left"}} -->
<div class="wp-block-group daguah-section"><!-- wp:group {"className":"daguah-section__head","layout":{"type":"default"}} -->
<div class="wp-block-group daguah-section__head"><!-- wp:paragraph {"className":"daguah-eyebrow"} -->
<p class="daguah-eyebrow"><?php esc_html_e( 'Every piece', 'daguah' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php echo wp_kses( __( 'Shop from across the world <br>with nothing left to guess', 'daguah' ), array( 'br' => array() ) ); ?></h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"daguah-grid-4","layout":{"type":"default"}} -->
<div class="wp-block-group daguah-grid-4">
<?php foreach ( $daguah_benefits as list( $daguah_icon, $daguah_title, $daguah_text ) ) : ?>
<!-- wp:group {"className":"daguah-benefit","layout":{"type":"default"}} -->
<div class="wp-block-group daguah-benefit"><!-- wp:daguah/icon {"name":"<?php echo esc_attr( $daguah_icon ); ?>"} /-->

<!-- wp:heading {"level":3,"className":"title-1"} -->
<h3 class="wp-block-heading title-1"><?php echo esc_html( $daguah_title ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-style-body-6"} -->
<p class="is-style-body-6"><?php echo esc_html( $daguah_text ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group -->
