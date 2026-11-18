<?php
/**
 * Menu toggle block.
 *
 * A link to the menu panel, so the menu opens without JS through :target.
 * The view script upgrades it to a button.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$daguah_open = __( 'Open menu', 'daguah' );

printf(
	'<a %1$s href="#daguah-mobile-menu" role="button" aria-controls="daguah-mobile-menu" aria-expanded="false" aria-label="%2$s" data-label-open="%2$s" data-label-close="%3$s" data-panel-label="%4$s"><span class="daguah-menu-toggle__lines" aria-hidden="true"><span></span><span></span></span></a>',
	get_block_wrapper_attributes( array( 'class' => 'daguah-menu-toggle daguah-icon-button' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core.
	esc_attr( $daguah_open ),
	esc_attr__( 'Close menu', 'daguah' ),
	esc_attr__( 'Menu', 'daguah' )
);
