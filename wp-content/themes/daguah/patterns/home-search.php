<?php
/**
 * Title: Home: Product search
 * Slug: daguah/home-search
 * Categories: daguah
 * Description: A large search for pieces, with a few popular searches underneath.
 *
 * @package Daguah
 */

defined( 'ABSPATH' ) || exit;

$daguah_popular = array(
	'aso ebi lace'     => __( 'Aso ebi lace', 'daguah' ),
	'kente stole'      => __( 'Kente stole', 'daguah' ),
	'preloved agbada'  => __( 'Preloved agbada', 'daguah' ),
	'kitenge'          => __( 'Kitenge', 'daguah' ),
	'habesha kemis'    => __( 'Habesha kemis', 'daguah' ),
);

$daguah_links = array();
foreach ( $daguah_popular as $daguah_query => $daguah_label ) {
	$daguah_links[] = sprintf(
		'<a href="%s">%s</a>',
		esc_url( add_query_arg( array( 's' => $daguah_query, 'post_type' => 'product' ), '/' ) ),
		esc_html( $daguah_label )
	);
}
?>
<!-- wp:group {"templateLock":"contentOnly","daguahReveal":true,"className":"daguah-section daguah-search","layout":{"type":"constrained","contentSize":"1280px","justifyContent":"left"}} -->
<div class="wp-block-group daguah-section daguah-search"><!-- wp:group {"className":"daguah-section__head","layout":{"type":"default"}} -->
<div class="wp-block-group daguah-section__head"><!-- wp:heading {"className":"heading-4"} -->
<h2 class="wp-block-heading heading-4"><?php esc_html_e( 'Know what you are looking for?', 'daguah' ); ?></h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:search {"label":"<?php esc_attr_e( 'Search the market', 'daguah' ); ?>","showLabel":false,"placeholder":"<?php esc_attr_e( 'Search adire, kente, aso oke, a stall or a town', 'daguah' ); ?>","buttonText":"<?php esc_attr_e( 'Search', 'daguah' ); ?>","buttonUseIcon":true,"query":{"post_type":"product"}} /-->

<!-- wp:paragraph {"className":"is-style-caption-2 daguah-search__popular"} -->
<p class="is-style-caption-2 daguah-search__popular"><?php echo esc_html__( 'Popular:', 'daguah' ) . ' ' . implode( ' · ', $daguah_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each link escaped above. ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
