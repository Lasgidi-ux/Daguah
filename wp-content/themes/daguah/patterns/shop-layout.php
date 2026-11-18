<?php
/**
 * Title: Shop layout
 * Slug: daguah/shop-layout
 * Categories: daguah
 * Inserter: no
 * Description: Breadcrumbs, title, filters beside the results. Used by the shop, category, occasion and country pages.
 *
 * @package Daguah
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"daguah-shop","layout":{"type":"constrained","contentSize":"1280px","justifyContent":"left"}} -->
<div class="wp-block-group daguah-shop"><!-- wp:woocommerce/breadcrumbs /-->

<!-- wp:group {"className":"daguah-shop__head","layout":{"type":"default"}} -->
<div class="wp-block-group daguah-shop__head"><!-- wp:query-title {"type":"archive","showPrefix":false} /-->

<!-- wp:term-description {"className":"is-style-body-3 daguah-section__lead"} /--></div>
<!-- /wp:group -->

<!-- wp:woocommerce/store-notices /-->

<!-- wp:group {"className":"daguah-shop__layout","layout":{"type":"default"}} -->
<div class="wp-block-group daguah-shop__layout"><!-- wp:daguah/shop-filters /-->

<!-- wp:daguah/product-results /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
