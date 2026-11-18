<?php
/**
 * Lantern words block: the tagline reveal the design skill requires.
 *
 * Each line is its own line break, so lines break where the thought breaks.
 * Words start at 30% of the text colour; the view script lights them in
 * reading order as they cross a line 60% down the screen. Without the script
 * (or with reduced motion) every word shows at full colour.
 *
 * @package Daguah\Core
 *
 * @var array $attributes Block attributes.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$daguah_lines = array_values( array_filter( array_map( 'trim', array_map( 'strval', (array) ( $attributes['lines'] ?? array() ) ) ) ) );

if ( ! $daguah_lines ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<p class="daguah-lantern-words__text">
		<?php foreach ( $daguah_lines as $daguah_line ) : ?>
			<span class="daguah-lantern-words__line">
				<?php
				$daguah_words = preg_split( '/\s+/u', $daguah_line ) ?: array();
				foreach ( $daguah_words as $daguah_word ) {
					printf( '<span class="daguah-lantern-words__word">%s</span> ', esc_html( $daguah_word ) );
				}
				?>
			</span>
		<?php endforeach; ?>
	</p>
</div>
