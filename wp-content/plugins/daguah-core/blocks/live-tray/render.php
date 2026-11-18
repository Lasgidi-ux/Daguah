<?php
/**
 * Market Live tray block.
 *
 * The pieces the stall is showing in this session, as ordinary product
 * cards, so price, fit and the full landed price work as they do in the shop.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

use Daguah\Core\Live;
use Daguah\Core\Product_Card;

defined( 'ABSPATH' ) || exit;

$daguah_post_id = (int) ( $block->context['postId'] ?? 0 ) ?: (int) get_the_ID();

if ( ( ! $daguah_post_id || \Daguah\Core\Post_Types::LIVE !== get_post_type( $daguah_post_id ) ) && defined( 'REST_REQUEST' ) && REST_REQUEST ) {
	$daguah_first   = Live::schedule()[0] ?? null;
	$daguah_post_id = $daguah_first ? $daguah_first['id'] : 0;
}

$daguah_s = Live::session( $daguah_post_id );

if ( ! $daguah_s || ! $daguah_s['products'] ) {
	return;
}
?>
<section <?php echo get_block_wrapper_attributes( array( 'aria-labelledby' => 'daguah-tray-title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<div class="daguah-section__head">
		<p class="daguah-eyebrow"><?php esc_html_e( 'In the tray', 'daguah' ); ?></p>
		<h2 id="daguah-tray-title" class="heading-4">
			<?php echo Live::PAST === $daguah_s['status'] ? esc_html__( 'The pieces from this session', 'daguah' ) : esc_html__( 'Buy what you see, while you watch', 'daguah' ); ?>
		</h2>
	</div>

	<?php
	echo Product_Card::grid( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside Product_Card.
		$daguah_s['products'],
		__( 'Everything in this tray has sold. The stall has more pieces like them.', 'daguah' )
	);
	?>
</section>
