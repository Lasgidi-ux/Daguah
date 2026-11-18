<?php
/**
 * Lantern row block.
 *
 * A strip of cloth like a stall's canopy, a gold thread along its edge, and
 * lanterns hanging from the thread. The warm up is pure CSS (opacity only),
 * staggered by each lantern's index, so it needs no script and plays even
 * before scripts load. Decorative: hidden from assistive technology.
 *
 * @package Daguah\Core
 *
 * @var array $attributes Block attributes.
 */

declare( strict_types=1 );

use Daguah\Core\Cloth;

defined( 'ABSPATH' ) || exit;

$daguah_count  = max( 3, min( 15, (int) ( $attributes['count'] ?? 9 ) ) );
$daguah_seed   = (string) ( $attributes['seed'] ?? 'DAGUAH lanterns' );
$daguah_family = Cloth::normalise_family( (string) ( $attributes['family'] ?? 'kente' ) );

// Lanterns hang at a few different drops so the row reads as strung by hand.
$daguah_drops = array( 0, 12, 4, 16, 8, 2, 14, 6, 10 );

$daguah_lantern = '<svg viewBox="0 0 24 40" width="24" height="40" focusable="false">'
	. '<rect class="daguah-lantern__glow" x="0" y="10" width="24" height="24" rx="12"/>'
	. '<rect class="daguah-lantern__cap" x="7" y="6" width="10" height="4" rx="1"/>'
	. '<rect class="daguah-lantern__body" x="5" y="10" width="14" height="20" rx="4"/>'
	. '<rect class="daguah-lantern__band" x="5" y="18" width="14" height="2"/>'
	. '<rect class="daguah-lantern__cap" x="8" y="30" width="8" height="3" rx="1"/>'
	. '</svg>';
?>
<div <?php echo get_block_wrapper_attributes( array( 'aria-hidden' => 'true' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<div class="daguah-lantern-row__cloth daguah-cloth-box">
		<?php echo Cloth::svg( $daguah_seed, $daguah_family ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from integers and a fixed palette. ?>
	</div>
	<ol class="daguah-lantern-row__lanterns">
		<?php for ( $daguah_i = 0; $daguah_i < $daguah_count; $daguah_i++ ) : ?>
			<li class="daguah-lantern" style="<?php echo esc_attr( sprintf( '--i:%d;--drop:%dpx', $daguah_i, $daguah_drops[ $daguah_i % count( $daguah_drops ) ] ) ); ?>">
				<span class="daguah-lantern__string"></span>
				<?php echo $daguah_lantern; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup above. ?>
			</li>
		<?php endfor; ?>
	</ol>
</div>
