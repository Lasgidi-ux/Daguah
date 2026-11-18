<?php
/**
 * Featured stalls block.
 *
 * Chosen stalls in the order picked, or the most verified stalls, longest
 * selling first. Each card wears the stall's cached cloth.
 *
 * @package Daguah\Core
 *
 * @var array $attributes Block attributes.
 */

declare( strict_types=1 );

use Daguah\Core\Vendor;

defined( 'ABSPATH' ) || exit;

$daguah_ids   = array_values( array_filter( array_map( 'absint', (array) ( $attributes['ids'] ?? array() ) ) ) );
$daguah_limit = max( 1, min( 12, (int) ( $attributes['limit'] ?? 4 ) ) );

if ( ! $daguah_ids ) {
	$daguah_stalls = array_map(
		static fn( $id ) => Vendor::get( (int) $id ),
		get_users(
			array(
				'role'   => 'seller',
				'fields' => 'ID',
				'number' => 200,
			)
		)
	);

	usort(
		$daguah_stalls,
		static fn( $a, $b ) => array( -$a['verified_level'], $a['selling_since'] ?: PHP_INT_MAX ) <=> array( -$b['verified_level'], $b['selling_since'] ?: PHP_INT_MAX )
	);

	$daguah_ids = array_slice( array_column( $daguah_stalls, 'id' ), 0, $daguah_limit );
}

$daguah_cards = array_filter( array_map( static fn( $id ) => Vendor::card( (int) $id ), $daguah_ids ) );

if ( ! $daguah_cards ) {
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		printf( '<p %s>%s</p>', get_block_wrapper_attributes(), esc_html__( 'No stalls yet. Stalls appear here once sellers are approved in Dokan.', 'daguah' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core.
	}
	return;
}
?>
<ul <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<?php foreach ( $daguah_cards as $daguah_card ) : ?>
		<li><?php echo $daguah_card; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside Vendor::card. ?></li>
	<?php endforeach; ?>
</ul>
