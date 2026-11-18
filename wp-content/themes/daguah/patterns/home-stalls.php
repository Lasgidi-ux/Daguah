<?php
/**
 * Title: Home: Featured stalls
 * Slug: daguah/home-stalls
 * Categories: daguah
 * Description: Four stalls, each in its own cloth, with a link to every stall.
 *
 * @package Daguah
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"templateLock":"contentOnly","daguahReveal":true,"className":"daguah-section","layout":{"type":"constrained","contentSize":"1280px","justifyContent":"left"}} -->
<div class="wp-block-group daguah-section"><!-- wp:group {"className":"daguah-section__head","layout":{"type":"default"}} -->
<div class="wp-block-group daguah-section__head"><!-- wp:paragraph {"className":"daguah-eyebrow"} -->
<p class="daguah-eyebrow"><?php esc_html_e( 'The stalls', 'daguah' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Buy from the people who made it', 'daguah' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-style-body-3 daguah-section__lead"} -->
<p class="is-style-body-3 daguah-section__lead"><?php esc_html_e( 'Weavers, dyers, tailors and resellers, each checked before they can sell. Every stall wears a cloth drawn from where its craft comes from.', 'daguah' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:daguah/featured-stalls /-->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/stalls/"><?php esc_html_e( 'See every stall', 'daguah' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
