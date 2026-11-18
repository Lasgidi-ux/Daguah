<?php
/**
 * Title: Home: FAQ
 * Slug: daguah/home-faq
 * Categories: daguah
 * Description: Eight questions people ask before their first order. The page gets FAQ structured data from whatever these say.
 *
 * The answers describe policies (who decides disputes, how sellers are
 * checked, where DAGUAH ships). They are drafted from the brief and need the
 * client's confirmation before launch.
 *
 * @package Daguah
 */

defined( 'ABSPATH' ) || exit;

$daguah_faq = array(
	array(
		__( 'How does DAGUAH Hold protect my money?', 'daguah' ),
		__( 'You pay once at checkout and DAGUAH holds the payment. The seller ships with tracking, and the money is released only after you confirm the order arrived as described. If something is wrong, report it from your order page. A person on our team reviews the evidence from both sides and decides: refund, part refund or release.', 'daguah' ),
	),
	array(
		__( 'What if the piece does not fit?', 'daguah' ),
		__( 'Every listing shows measurements in centimetres, and Fit Mirror compares them with yours before you buy. If it still does not fit, the seller\'s returns rules are on the listing, and we can point you to a tailor for a small alteration.', 'daguah' ),
	),
	array(
		__( 'Which countries do you ship to?', 'daguah' ),
		__( 'At launch: the United Kingdom, the United States, Canada, the European Union, Nigeria, Ghana and Kenya. More countries are being added, and every listing shows whether it ships to you.', 'daguah' ),
	),
	array(
		__( 'Will I pay customs duties?', 'daguah' ),
		__( 'Sometimes. Before you pay, the landed price shows the item, delivery and an estimate of duties for your country, in your currency. The estimate is a guide. Customs in your country sets the final amount.', 'daguah' ),
	),
	array(
		__( 'How do sellers get paid?', 'daguah' ),
		__( 'In their own currency, after the buyer confirms delivery. Every fee is shown before the seller lists, so the payout is never a surprise.', 'daguah' ),
	),
	array(
		__( 'How are sellers verified?', 'daguah' ),
		__( 'Every seller passes an identity check. Sellers of higher priced pieces also join a video call, and we check samples when a listing makes an authenticity claim such as handwoven kente.', 'daguah' ),
	),
	array(
		__( 'Can I sell preloved pieces?', 'daguah' ),
		__( 'Yes. Each preloved piece carries a Heritage Passport: a short story card of where it has been worn, which travels with the piece to its next owner.', 'daguah' ),
	),
	array(
		__( 'How long does delivery take?', 'daguah' ),
		__( 'It depends on the route, and every listing shows the range for your address. As a guide, Lagos to London takes 6 to 9 days.', 'daguah' ),
	),
);
?>
<!-- wp:group {"templateLock":"contentOnly","daguahReveal":true,"className":"daguah-section","layout":{"type":"constrained","contentSize":"1280px","justifyContent":"left"}} -->
<div class="wp-block-group daguah-section"><!-- wp:group {"className":"daguah-section__head","layout":{"type":"default"}} -->
<div class="wp-block-group daguah-section__head"><!-- wp:paragraph {"className":"daguah-eyebrow"} -->
<p class="daguah-eyebrow"><?php esc_html_e( 'Questions', 'daguah' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Before your first order', 'daguah' ); ?></h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"daguah-faq","layout":{"type":"default"}} -->
<div class="wp-block-group daguah-faq">
<?php foreach ( $daguah_faq as list( $daguah_question, $daguah_answer ) ) : ?>
<!-- wp:details {"summary":"<?php echo esc_attr( $daguah_question ); ?>"} -->
<details class="wp-block-details"><summary><?php echo esc_html( $daguah_question ); ?></summary><!-- wp:paragraph -->
<p><?php echo esc_html( $daguah_answer ); ?></p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->
<?php endforeach; ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group -->
