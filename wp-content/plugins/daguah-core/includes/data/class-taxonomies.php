<?php
/**
 * Taxonomies and product attributes.
 *
 * occasion and origin_country are DAGUAH taxonomies on products. Fabric,
 * condition and size are WooCommerce attributes (pa_fabric, pa_condition,
 * pa_size) so the shop's filter blocks can use them.
 *
 * Origin country is the taxonomy, not a separate field: one source of truth
 * that is both filterable and shown on the product. Each term stores its ISO
 * code as term meta.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

namespace Daguah\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Registers taxonomies and makes sure the attributes exist.
 */
final class Taxonomies {

	public const OCCASION = 'occasion';
	public const ORIGIN   = 'origin_country';

	/**
	 * Attribute name (without pa_) to label.
	 *
	 * @return array<string, string>
	 */
	public static function attributes(): array {
		return array(
			'fabric'    => __( 'Fabric', 'daguah' ),
			'condition' => __( 'Condition', 'daguah' ),
			'size'      => __( 'Size', 'daguah' ),
		);
	}

	/**
	 * Hooks.
	 */
	public static function init(): void {
		add_action( 'init', array( self::class, 'register' ), 5 );
	}

	/**
	 * Register occasion and origin_country.
	 */
	public static function register(): void {
		register_taxonomy(
			self::OCCASION,
			array( 'product' ),
			array(
				'labels'            => array(
					'name'          => __( 'Occasions', 'daguah' ),
					'singular_name' => __( 'Occasion', 'daguah' ),
					'search_items'  => __( 'Search occasions', 'daguah' ),
					'all_items'     => __( 'All occasions', 'daguah' ),
					'edit_item'     => __( 'Edit occasion', 'daguah' ),
					'add_new_item'  => __( 'Add occasion', 'daguah' ),
					'menu_name'     => __( 'Occasions', 'daguah' ),
					'not_found'     => __( 'No occasions yet.', 'daguah' ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'occasion' ),
			)
		);

		register_taxonomy(
			self::ORIGIN,
			array( 'product' ),
			array(
				'labels'            => array(
					'name'          => __( 'Origin countries', 'daguah' ),
					'singular_name' => __( 'Origin country', 'daguah' ),
					'search_items'  => __( 'Search countries', 'daguah' ),
					'all_items'     => __( 'All countries', 'daguah' ),
					'edit_item'     => __( 'Edit country', 'daguah' ),
					'add_new_item'  => __( 'Add country', 'daguah' ),
					'menu_name'     => __( 'Origin countries', 'daguah' ),
					'not_found'     => __( 'No countries yet.', 'daguah' ),
				),
				'public'            => true,
				// Hierarchical only for the checkbox picker. Countries are a flat list.
				'hierarchical'      => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'made-in' ),
			)
		);

		register_term_meta(
			self::ORIGIN,
			'daguah_iso',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => static fn( $value ) => strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', (string) $value ) ?? '', 0, 2 ) ),
			)
		);
	}

	/**
	 * Create the fabric, condition and size attributes if they are missing.
	 *
	 * @return array<string, int> Attribute name to attribute ID.
	 */
	public static function ensure_attributes(): array {
		if ( ! function_exists( 'wc_create_attribute' ) ) {
			return array();
		}

		$ids = array();

		foreach ( self::attributes() as $slug => $label ) {
			$id = (int) wc_attribute_taxonomy_id_by_name( $slug );

			if ( ! $id ) {
				$created = wc_create_attribute(
					array(
						'name'         => $label,
						'slug'         => $slug,
						'type'         => 'select',
						'order_by'     => 'menu_order',
						'has_archives' => false,
					)
				);
				$id      = is_wp_error( $created ) ? 0 : (int) $created;
			}

			// A new attribute's taxonomy is not registered until the next request.
			$taxonomy = 'pa_' . $slug;
			if ( $id && ! taxonomy_exists( $taxonomy ) ) {
				register_taxonomy( $taxonomy, array( 'product' ), array( 'hierarchical' => false, 'show_ui' => false, 'query_var' => true, 'rewrite' => false ) );
			}

			$ids[ $slug ] = $id;
		}

		return $ids;
	}

	/**
	 * ISO code of a product's origin country, or an empty string.
	 *
	 * @param int $product_id Product ID.
	 */
	public static function origin_iso( int $product_id ): string {
		$terms = get_the_terms( $product_id, self::ORIGIN );

		if ( ! is_array( $terms ) || ! $terms ) {
			return '';
		}

		return (string) get_term_meta( $terms[0]->term_id, 'daguah_iso', true );
	}
}
