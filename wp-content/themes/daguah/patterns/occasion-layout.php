<?php
/**
 * Title: Occasion layout
 * Slug: daguah/occasion-layout
 * Categories: daguah
 * Inserter: no
 * Description: The occasion's cloth, name, next date and family prompt, then filters beside the pieces. Used by occasion pages.
 *
 * @package Daguah
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"daguah-shop","layout":{"type":"constrained","contentSize":"1280px","justifyContent":"left"}} -->
<div class="wp-block-group daguah-shop"><!-- wp:woocommerce/breadcrumbs /-->

<!-- wp:daguah/occasion-intro /-->

<!-- wp:woocommerce/store-notices /-->

<!-- wp:group {"className":"daguah-shop__layout","layout":{"type":"default"}} -->
<div class="wp-block-group daguah-shop__layout"><!-- wp:daguah/shop-filters /-->

<!-- wp:daguah/product-results /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
