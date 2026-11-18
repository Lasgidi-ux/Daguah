<?php
/**
 * Title: System sheet
 * Slug: daguah/system-sheet
 * Categories: daguah
 * Inserter: no
 * Description: Review page for the visual system: colour, type, buttons, cards, cloth and motion.
 *
 * Prototype content throughout (names, prices, places). It is here to judge
 * the system, not to be read as fact.
 *
 * @package Daguah
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$daguah_cloth = static function ( string $seed, string $family ): string {
	return class_exists( '\Daguah\Core\Cloth' ) ? \Daguah\Core\Cloth::svg( $seed, $family ) : '';
};

$daguah_icon = static function ( string $name ): string {
	return function_exists( 'daguah_icon' ) ? daguah_icon( $name ) : '';
};

$daguah_swatches = array(
	array( 'night', '#131209', __( 'Base background', 'daguah' ) ),
	array( 'night-raised', '#1F1F1F', __( 'Cards and raised surfaces', 'daguah' ) ),
	array( 'night-line', '#313131', __( 'Borders and dividers', 'daguah' ) ),
	array( 'lantern', '#D9A21B', __( 'Primary actions and the Thread', 'daguah' ) ),
	array( 'indigo', '#24306B', __( 'Secondary surfaces, links at noon', 'daguah' ) ),
	array( 'laterite', '#B5532A', __( 'Occasion and sale tags', 'daguah' ) ),
	array( 'palm', '#2F5D46', __( 'Verified and success', 'daguah' ) ),
	array( 'shea', '#F2EADB', __( 'Text at dusk, base at noon', 'daguah' ) ),
);

$daguah_type = array(
	array( 'Heading 1', 'heading-1', __( 'Wherever you are, the market is open.', 'daguah' ) ),
	array( 'Heading 2', 'heading-2', __( 'Made at home. Worn everywhere.', 'daguah' ) ),
	array( 'Heading 3', 'heading-3', __( 'Carry home with you.', 'daguah' ) ),
	array( 'Heading 4', 'heading-4', __( 'Heritage that keeps moving.', 'daguah' ) ),
	array( 'Heading 5', 'heading-5', __( 'Shop by occasion', 'daguah' ) ),
	array( 'Heading 6', 'heading-6', __( 'Featured stalls', 'daguah' ) ),
	array( 'Title 1', 'title-1', 'Ọjà · Ahịa · Kasuwa · Soko · Gebeya · Zando · Souk · Marché' ),
	array( 'Title 3', 'title-3', __( 'Indigo adire wrap skirt, hand dyed', 'daguah' ) ),
	array( 'Body 3', 'body-3', __( 'Heritage fashion, fabric and accessories from verified African makers and the diaspora. Your payment is held until your order arrives.', 'daguah' ) ),
	array( 'Body 6', 'body-6', __( 'Dyed in Abeokuta, arriving in Croydon in 6 to 9 days.', 'daguah' ) ),
	array( 'Caption 2', 'caption-2', __( 'Selling since 2011. Replies within 3 hours.', 'daguah' ) ),
	array( 'Caption 3', 'caption-3', __( 'Preloved, like new', 'daguah' ) ),
	array( 'Geist Mono', 'body-2 is-mono', '£64.50   ₦131,480   74 cm   Order 10482' ),
);

$daguah_cards = array(
	array(
		'seed'   => 'Ìyá Àdìrẹ Studio|yoruba',
		'family' => 'adire',
		'tag'    => array( '', __( 'New', 'daguah' ) ),
		'title'  => __( 'Indigo adire wrap skirt, hand dyed', 'daguah' ),
		'stall'  => 'Ìyá Àdìrẹ Studio, Abeokuta',
		'price'  => '£64.50',
		'shot'   => __( 'Intended shot: hands lifting the skirt from the dye pit, late afternoon light.', 'daguah' ),
	),
	array(
		'seed'   => 'Bonwire Loom House|ashanti',
		'family' => 'kente',
		'tag'    => array( 'daguah-tag--verified', __( 'Verified maker', 'daguah' ) ),
		'title'  => __( 'Kente strip stole, gold and green', 'daguah' ),
		'stall'  => 'Bonwire Loom House, Ashanti Region',
		'price'  => '£118.00',
		'shot'   => __( 'Intended shot: the weaver at the loom, stole half finished, shot from the side.', 'daguah' ),
	),
	array(
		'seed'   => 'Peckham Preloved by Funmi|london',
		'family' => 'mix',
		'tag'    => array( '', __( 'Preloved, like new', 'daguah' ) ),
		'title'  => __( 'Preloved agbada in cream damask, worn once', 'daguah' ),
		'stall'  => 'Peckham Preloved by Funmi, London',
		'price'  => '£142.00',
		'shot'   => __( 'Intended shot: the agbada on a hanger against a Peckham shop window.', 'daguah' ),
	),
);

$daguah_cloths = array(
	array( 'Ìyá Àdìrẹ Studio|yoruba', 'adire', 'Adire', __( 'Yoruba lands. Ìyá Àdìrẹ Studio, Abeokuta.', 'daguah' ) ),
	array( 'Bonwire Loom House|ashanti', 'kente', 'Kente', __( 'Ashanti. Bonwire Loom House.', 'daguah' ) ),
	array( 'Ségou|mali', 'bogolan', 'Bògòlan', __( 'Mali. Waiting for its first seller.', 'daguah' ) ),
	array( 'Maison Kinshasa Wax|congo', 'kuba', 'Kuba', __( 'Congo. Maison Kinshasa Wax, Brussels.', 'daguah' ) ),
	array( 'Gikomba Thread Co.|nairobi', 'mix', __( 'Seeded mix', 'daguah' ), __( 'Everywhere else. Gikomba Thread Co., Nairobi.', 'daguah' ) ),
	array( 'Habesha Edit|washington dc', 'mix', __( 'Seeded mix', 'daguah' ), __( 'Same family, different seller, different cloth. Habesha Edit.', 'daguah' ) ),
);
?>
<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|900"}},"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"className":"is-style-caption-3 is-muted"} -->
<p class="is-style-caption-3 is-muted"><?php esc_html_e( 'Stage 1 review', 'daguah' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"className":"is-style-hero-heading"} -->
<h1 class="wp-block-heading is-style-hero-heading"><?php esc_html_e( 'The DAGUAH system, before a single page is built.', 'daguah' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-style-body-3 is-muted daguah-measure"} -->
<p class="is-style-body-3 is-muted daguah-measure"><?php esc_html_e( 'Colour, type, buttons, cards, cloth and motion. Every value here comes from one token file, so the Site Editor can only offer what belongs to the brand. Use the sun in the header to see Market at Noon.', 'daguah' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading {"className":"heading-4"} -->
<h2 class="wp-block-heading heading-4"><?php esc_html_e( 'Colour', 'daguah' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:html -->
<div class="daguah-sheet__grid">
<?php foreach ( $daguah_swatches as $daguah_swatch ) : ?>
	<div class="daguah-swatch">
		<div class="daguah-swatch__chip" style="background:<?php echo esc_attr( $daguah_swatch[1] ); ?>"></div>
		<p class="title-4"><?php echo esc_html( $daguah_swatch[0] ); ?></p>
		<p class="caption-4 is-mono is-muted"><?php echo esc_html( $daguah_swatch[1] ); ?></p>
		<p class="caption-5"><?php echo esc_html( $daguah_swatch[2] ); ?></p>
	</div>
<?php endforeach; ?>
</div>
<!-- /wp:html --></div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading {"className":"heading-4"} -->
<h2 class="wp-block-heading heading-4"><?php esc_html_e( 'Type', 'daguah' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:html -->
<div class="daguah-sheet__type">
<?php foreach ( $daguah_type as $daguah_row ) : ?>
	<div>
		<p class="caption-4 is-mono is-muted"><?php echo esc_html( $daguah_row[0] ); ?></p>
		<p class="<?php echo esc_attr( $daguah_row[1] ); ?>"><?php echo esc_html( $daguah_row[2] ); ?></p>
	</div>
<?php endforeach; ?>
</div>
<!-- /wp:html --></div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading {"className":"heading-4"} -->
<h2 class="wp-block-heading heading-4"><?php esc_html_e( 'Buttons', 'daguah' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-style-caption-2"} -->
<p class="is-style-caption-2"><?php esc_html_e( 'Hover, press and tab through these. Press scales to 98%, focus shows a ring, loading breathes instead of spinning.', 'daguah' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/shop/"><?php esc_html_e( 'Start shopping', 'daguah' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/open-your-stall/"><?php esc_html_e( 'Open your stall', 'daguah' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-ghost"} -->
<div class="wp-block-button is-style-ghost"><a class="wp-block-button__link wp-element-button" href="/help/"><?php esc_html_e( 'See how DAGUAH Hold works', 'daguah' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"fontSize":"sm"} -->
<div class="wp-block-button has-custom-font-size has-sm-font-size"><a class="wp-block-button__link wp-element-button" href="/shop/"><?php esc_html_e( 'Header size', 'daguah' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:html -->
<div class="daguah-sheet__row">
	<button type="button" class="wp-element-button" disabled><?php esc_html_e( 'Sold out', 'daguah' ); ?></button>
	<button type="button" class="wp-element-button" aria-busy="true"><?php esc_html_e( 'Saving your fit profile', 'daguah' ); ?></button>
	<span class="daguah-tag"><?php esc_html_e( 'Wedding guest', 'daguah' ); ?></span>
	<span class="daguah-tag daguah-tag--verified"><?php echo $daguah_icon( 'seal-check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?><?php esc_html_e( 'Verified seller', 'daguah' ); ?></span>
	<span class="daguah-tag daguah-tag--quiet"><?php esc_html_e( 'Ships in 6 to 9 days', 'daguah' ); ?></span>
</div>
<!-- /wp:html --></div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading {"className":"heading-4"} -->
<h2 class="wp-block-heading heading-4"><?php esc_html_e( 'Cards', 'daguah' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-style-caption-2"} -->
<p class="is-style-caption-2"><?php esc_html_e( 'Outer radius 16, padding 8, so the image takes 16 minus 8. Until real photography exists, each card carries its seller cloth and a note on the intended shot. The last card is the loading state.', 'daguah' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<div class="daguah-sheet__grid daguah-sheet__grid--wide">
<?php foreach ( $daguah_cards as $daguah_card ) : ?>
	<a class="daguah-card is-interactive" href="/shop/">
		<div class="daguah-card__media daguah-cloth-box" style="aspect-ratio:4/5"><?php echo $daguah_cloth( $daguah_card['seed'], $daguah_card['family'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from integers and a fixed palette. ?></div>
		<div class="daguah-card__body">
			<span><span class="daguah-tag <?php echo esc_attr( $daguah_card['tag'][0] ); ?>"><?php echo esc_html( $daguah_card['tag'][1] ); ?></span></span>
			<p class="title-3"><?php echo esc_html( $daguah_card['title'] ); ?></p>
			<p class="caption-2"><?php echo esc_html( $daguah_card['stall'] ); ?></p>
			<p class="body-1 is-mono"><?php echo esc_html( $daguah_card['price'] ); ?></p>
			<p class="caption-5"><?php echo esc_html( $daguah_card['shot'] ); ?></p>
		</div>
	</a>
<?php endforeach; ?>
	<div class="daguah-card" aria-hidden="true">
		<span class="daguah-skeleton daguah-skeleton--media"></span>
		<div class="daguah-card__body">
			<span class="daguah-skeleton daguah-skeleton--line daguah-skeleton--short"></span>
			<span class="daguah-skeleton daguah-skeleton--line"></span>
			<span class="daguah-skeleton daguah-skeleton--line daguah-skeleton--short"></span>
		</div>
	</div>
</div>
<!-- /wp:html --></div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading {"className":"heading-4"} -->
<h2 class="wp-block-heading heading-4"><?php esc_html_e( 'The Cloth', 'daguah' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-style-caption-2"} -->
<p class="is-style-caption-2"><?php esc_html_e( 'Generated from the seller name and region, on the server, so the same seller always wears the same cloth. These motifs are placeholders until the artists supply theirs.', 'daguah' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<div class="daguah-sheet__grid daguah-sheet__grid--three">
<?php foreach ( $daguah_cloths as $daguah_item ) : ?>
	<div class="daguah-card">
		<div class="daguah-card__media daguah-cloth-box daguah-sheet__cloth"><?php echo $daguah_cloth( $daguah_item[0], $daguah_item[1] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from integers and a fixed palette. ?></div>
		<div class="daguah-card__body">
			<p class="title-3"><?php echo esc_html( $daguah_item[2] ); ?></p>
			<p class="caption-2"><?php echo esc_html( $daguah_item[3] ); ?></p>
		</div>
	</div>
<?php endforeach; ?>
</div>
<!-- /wp:html --></div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading {"className":"heading-4"} -->
<h2 class="wp-block-heading heading-4"><?php esc_html_e( 'Motion', 'daguah' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-style-caption-2"} -->
<p class="is-style-caption-2"><?php esc_html_e( 'One curve for everything. Reveals are switched on per block in the editor sidebar, run once, and become a plain fade for visitors who prefer reduced motion.', 'daguah' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:group {"daguahReveal":true,"className":"is-style-card","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-card"><!-- wp:daguah/cloth {"seed":"Market at Dusk","family":"kuba","height":160,"animate":true} /-->

<!-- wp:group {"className":"daguah-card__body","layout":{"type":"default"}} -->
<div class="wp-block-group daguah-card__body"><!-- wp:paragraph {"className":"is-style-title-3"} -->
<p class="is-style-title-3"><?php esc_html_e( 'This card rose 32px and faded in as you reached it.', 'daguah' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"is-style-caption-2"} -->
<p class="is-style-caption-2"><?php esc_html_e( 'The cloth above wove itself in, one part at a time.', 'daguah' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
