<?php
/**
 * Block styles and pattern categories.
 *
 * The named text styles from the design skill are offered on paragraphs so an
 * editor picks "Body 2" or "Caption 4" instead of a loose size and weight.
 *
 * @package Daguah
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function (): void {
		$text_styles = array(
			'title-1'   => __( 'Title 1', 'daguah' ),
			'title-2'   => __( 'Title 2', 'daguah' ),
			'title-3'   => __( 'Title 3', 'daguah' ),
			'title-4'   => __( 'Title 4', 'daguah' ),
			'title-5'   => __( 'Title 5', 'daguah' ),
			'body-1'    => __( 'Body 1', 'daguah' ),
			'body-2'    => __( 'Body 2', 'daguah' ),
			'body-3'    => __( 'Body 3', 'daguah' ),
			'body-4'    => __( 'Body 4', 'daguah' ),
			'body-5'    => __( 'Body 5', 'daguah' ),
			'body-6'    => __( 'Body 6', 'daguah' ),
			'caption-1' => __( 'Caption 1', 'daguah' ),
			'caption-2' => __( 'Caption 2', 'daguah' ),
			'caption-3' => __( 'Caption 3', 'daguah' ),
			'caption-4' => __( 'Caption 4', 'daguah' ),
			'caption-5' => __( 'Caption 5', 'daguah' ),
		);

		foreach ( $text_styles as $name => $label ) {
			register_block_style(
				'core/paragraph',
				array(
					'name'  => $name,
					'label' => $label,
				)
			);
		}

		register_block_style(
			'core/heading',
			array(
				'name'  => 'hero-heading',
				'label' => __( 'Hero heading', 'daguah' ),
			)
		);

		register_block_style(
			'core/button',
			array(
				'name'  => 'ghost',
				'label' => __( 'Quiet', 'daguah' ),
			)
		);

		register_block_style(
			'core/group',
			array(
				'name'  => 'card',
				'label' => __( 'Card', 'daguah' ),
			)
		);

		register_block_pattern_category(
			'daguah',
			array( 'label' => __( 'DAGUAH', 'daguah' ) )
		);
	}
);
