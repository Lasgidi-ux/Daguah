<?php
/**
 * Cloth block.
 *
 * @package Daguah\Core
 *
 * @var array $attributes Block attributes.
 */

declare( strict_types=1 );

use Daguah\Core\Cloth;

defined( 'ABSPATH' ) || exit;

$daguah_seed   = isset( $attributes['seed'] ) ? (string) $attributes['seed'] : 'DAGUAH';
$daguah_family = Cloth::normalise_family( (string) ( $attributes['family'] ?? 'mix' ) );
$daguah_height = max( 48, min( 640, (int) ( $attributes['height'] ?? 160 ) ) );

$daguah_extra = array(
	'style'       => 'height:' . $daguah_height . 'px',
	'data-seed'   => $daguah_seed,
	'data-family' => $daguah_family,
);

if ( ! empty( $attributes['animate'] ) ) {
	$daguah_extra['data-animate'] = 'true';
}

printf(
	'<div %1$s>%2$s</div>',
	get_block_wrapper_attributes( $daguah_extra ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core.
	Cloth::svg( $daguah_seed, $daguah_family ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from integers and a fixed palette only.
);
