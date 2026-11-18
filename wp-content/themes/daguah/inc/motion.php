<?php
/**
 * "Reveal on scroll" block support.
 *
 * Motion is opt in per block. The editor toggle (src/editor.js) sets the
 * daguahReveal attribute; this file registers it for server rendered blocks
 * and adds the class the front end observer looks for.
 *
 * @package Daguah
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

add_filter(
	'register_block_type_args',
	static function ( array $args ): array {
		$args['attributes'] = is_array( $args['attributes'] ?? null ) ? $args['attributes'] : array();

		$args['attributes']['daguahReveal'] = array(
			'type'    => 'boolean',
			'default' => false,
		);

		return $args;
	}
);

add_filter(
	'render_block',
	static function ( string $content, array $block ): string {
		if ( empty( $block['attrs']['daguahReveal'] ) || '' === trim( $content ) ) {
			return $content;
		}

		$tags = new WP_HTML_Tag_Processor( $content );

		if ( $tags->next_tag() ) {
			$tags->add_class( 'daguah-reveal' );
			return $tags->get_updated_html();
		}

		return $content;
	},
	10,
	2
);
