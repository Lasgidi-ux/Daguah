<?php
/**
 * FAQ structured data from ordinary Details blocks.
 *
 * Any Group block with the class daguah-faq is read as an FAQ: each Details
 * block inside it is one question (its summary) and one answer (its
 * content). The page gets one FAQPage JSON-LD script built from exactly the
 * words on the page, so editing a question in the Site Editor updates the
 * schema with it.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

namespace Daguah\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Collects questions while blocks render, prints them in the footer.
 */
final class Faq_Schema {

	/**
	 * Questions found on this page.
	 *
	 * @var array<int, array{q: string, a: string}>
	 */
	private static array $items = array();

	/**
	 * Hooks.
	 */
	public static function init(): void {
		add_filter( 'render_block_core/group', array( self::class, 'collect' ), 10, 2 );
		add_action( 'wp_footer', array( self::class, 'print' ), 20 );
	}

	/**
	 * Read questions from a daguah-faq group as it renders.
	 *
	 * @param string $content Rendered group.
	 * @param array  $block   Parsed block.
	 */
	public static function collect( string $content, array $block ): string {
		$class = (string) ( $block['attrs']['className'] ?? '' );

		if ( ! preg_match( '/(^|\s)daguah-faq(\s|$)/', $class ) || is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $content;
		}

		foreach ( self::details( $block['innerBlocks'] ?? array() ) as $details ) {
			$question = trim( wp_strip_all_tags( (string) ( $details['attrs']['summary'] ?? '' ) ) );
			$answer   = '';

			foreach ( $details['innerBlocks'] ?? array() as $inner ) {
				$answer .= ' ' . wp_strip_all_tags( render_block( $inner ) );
			}

			$answer = trim( (string) preg_replace( '/\s+/u', ' ', $answer ) );

			if ( '' !== $question && '' !== $answer ) {
				self::$items[] = array(
					'q' => $question,
					'a' => $answer,
				);
			}
		}

		return $content;
	}

	/**
	 * Details blocks anywhere inside a list of blocks.
	 *
	 * @param array $blocks Parsed blocks.
	 */
	private static function details( array $blocks ): array {
		$found = array();

		foreach ( $blocks as $block ) {
			if ( 'core/details' === ( $block['blockName'] ?? '' ) ) {
				$found[] = $block;
			} elseif ( ! empty( $block['innerBlocks'] ) ) {
				$found = array_merge( $found, self::details( $block['innerBlocks'] ) );
			}
		}

		return $found;
	}

	/**
	 * One FAQPage script, if the page had questions.
	 */
	public static function print(): void {
		if ( ! self::$items ) {
			return;
		}

		$schema = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => array_map(
				static fn( array $item ) => array(
					'@type'          => 'Question',
					'name'           => $item['q'],
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => $item['a'],
					),
				),
				self::$items
			),
		);

		wp_print_inline_script_tag(
			(string) wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ),
			array( 'type' => 'application/ld+json' )
		);
	}
}
