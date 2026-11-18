<?php
/**
 * Title: Home: Testimonials
 * Slug: daguah/home-testimonials
 * Categories: daguah
 * Description: Three short stories from buyers and sellers.
 *
 * PROTOTYPE CONTENT from the brief. These people and quotes are invented
 * for the demo. Replace them with real, permissioned testimonials before
 * launch.
 *
 * @package Daguah
 */

defined( 'ABSPATH' ) || exit;

$daguah_quotes = array(
	array( __( 'I bought aso oke for my cousin\'s wedding in Lagos without leaving London, and it fit the first time.', 'daguah' ), 'Adaeze Okonkwo, Peckham' ),
	array( __( 'The kente stole came with the weaver\'s name and town. My father cried a little.', 'daguah' ), 'Kofi Mensah, Houston' ),
	array( __( 'I sold three preloved kitenge dresses in a week, and the money landed in shillings for my sister.', 'daguah' ), 'Wanjiru Kamau, Toronto' ),
);
?>
<!-- wp:group {"templateLock":"contentOnly","daguahReveal":true,"className":"daguah-section","layout":{"type":"constrained","contentSize":"1280px","justifyContent":"left"}} -->
<div class="wp-block-group daguah-section"><!-- wp:group {"className":"daguah-section__head","layout":{"type":"default"}} -->
<div class="wp-block-group daguah-section__head"><!-- wp:paragraph {"className":"daguah-eyebrow"} -->
<p class="daguah-eyebrow"><?php esc_html_e( 'From the market', 'daguah' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Families who shop across borders', 'daguah' ); ?></h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"daguah-grid-3","layout":{"type":"default"}} -->
<div class="wp-block-group daguah-grid-3">
<?php foreach ( $daguah_quotes as list( $daguah_quote, $daguah_cite ) ) : ?>
<!-- wp:quote {"className":"daguah-quote"} -->
<blockquote class="wp-block-quote daguah-quote"><!-- wp:paragraph -->
<p><?php echo esc_html( $daguah_quote ); ?></p>
<!-- /wp:paragraph --><cite><?php echo esc_html( $daguah_cite ); ?></cite></blockquote>
<!-- /wp:quote -->
<?php endforeach; ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group -->
