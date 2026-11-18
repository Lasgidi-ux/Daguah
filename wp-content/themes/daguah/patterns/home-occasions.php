<?php
/**
 * Title: Home: Shop by occasion
 * Slug: daguah/home-occasions
 * Categories: daguah
 * Description: Occasions as cloth tiles.
 *
 * @package Daguah
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"templateLock":"contentOnly","daguahReveal":true,"className":"daguah-section","layout":{"type":"constrained","contentSize":"1280px","justifyContent":"left"}} -->
<div class="wp-block-group daguah-section"><!-- wp:group {"className":"daguah-section__head","layout":{"type":"default"}} -->
<div class="wp-block-group daguah-section__head"><!-- wp:paragraph {"className":"daguah-eyebrow"} -->
<p class="daguah-eyebrow"><?php esc_html_e( 'Shop by occasion', 'daguah' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php echo wp_kses( __( 'Aso ebi for Lagos, <br>kente for Leeds', 'daguah' ), array( 'br' => array() ) ); ?></h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:daguah/occasion-grid /--></div>
<!-- /wp:group -->
