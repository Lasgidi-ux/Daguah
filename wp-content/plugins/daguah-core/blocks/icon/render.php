<?php
/**
 * Icon block.
 *
 * @package Daguah\Core
 *
 * @var array $attributes Block attributes.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$daguah_svg = daguah_icon( (string) ( $attributes['name'] ?? '' ) );

if ( '' === $daguah_svg ) {
	return;
}

printf(
	'<span %1$s>%2$s</span>',
	get_block_wrapper_attributes(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core.
	$daguah_svg // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG shipped with the plugin.
);
