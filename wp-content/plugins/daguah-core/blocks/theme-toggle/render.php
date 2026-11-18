<?php
/**
 * Dusk and noon toggle block.
 *
 * Shows the mode the visitor would switch to: a sun at dusk, a moon at noon.
 * Without JS the control is hidden, since it could not do anything.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

printf(
	'<button %1$s type="button" aria-label="%2$s" data-label-noon="%2$s" data-label-dusk="%3$s"><span class="daguah-theme-toggle__noon">%4$s</span><span class="daguah-theme-toggle__dusk">%5$s</span></button>',
	get_block_wrapper_attributes( array( 'class' => 'daguah-theme-toggle daguah-icon-button' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core.
	esc_attr__( 'Switch to Market at Noon', 'daguah' ),
	esc_attr__( 'Switch to Market at Dusk', 'daguah' ),
	daguah_icon( 'sun' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG shipped with the plugin.
	daguah_icon( 'moon' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG shipped with the plugin.
);
