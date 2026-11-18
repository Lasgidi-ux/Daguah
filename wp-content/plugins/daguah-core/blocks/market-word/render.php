<?php
/**
 * Market word block.
 *
 * NATIVE SPEAKER REVIEW NEEDED before launch: the spellings and tone marks
 * of every word (Ọjà, Ahịa, Kasuwa, Soko, Gebeya, Zando, Souk, Marché) and
 * whether each is the word people would actually use. Editors can change
 * the list in the block sidebar.
 *
 * Screen readers hear "Market" once; the cycling words are decorative.
 *
 * @package Daguah\Core
 *
 * @var array $attributes Block attributes.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$daguah_words = array_values(
	array_filter(
		(array) ( $attributes['words'] ?? array() ),
		static fn( $w ) => is_array( $w ) && '' !== trim( (string) ( $w['word'] ?? '' ) )
	)
);

if ( ! $daguah_words ) {
	return;
}

$daguah_interval = max( 1500, min( 8000, (int) ( $attributes['interval'] ?? 2600 ) ) );
?>
<span <?php echo get_block_wrapper_attributes( array( 'data-interval' => (string) $daguah_interval ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<span class="screen-reader-text"><?php esc_html_e( 'Market', 'daguah' ); ?></span>
	<span class="daguah-market-word__stack" aria-hidden="true">
		<?php foreach ( $daguah_words as $daguah_i => $daguah_word ) : ?>
			<span class="daguah-market-word__item<?php echo 0 === $daguah_i ? ' is-active' : ''; ?>" lang="<?php echo esc_attr( sanitize_key( (string) ( $daguah_word['lang'] ?? '' ) ) ); ?>" title="<?php echo esc_attr( sprintf( /* translators: %s: language name */ __( 'Market, in %s', 'daguah' ), (string) ( $daguah_word['language'] ?? '' ) ) ); ?>">
				<span class="daguah-market-word__word"><?php echo esc_html( (string) $daguah_word['word'] ); ?></span>
				<span class="daguah-market-word__lang"><?php echo esc_html( (string) ( $daguah_word['language'] ?? '' ) ); ?></span>
			</span>
		<?php endforeach; ?>
	</span>
</span>
