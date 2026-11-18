<?php
/**
 * Calendar moments, DAGUAH Circles and Market Live sessions.
 *
 * Each is edited in the block editor. Their fields appear in a "Details"
 * panel in the sidebar (src/editor-panels.js), driven by the field lists in
 * fields() so the panel and the stored data never drift apart.
 *
 * Circles are always private: their members' names and cities are only
 * shown through the circle board endpoint, to people who hold the invite
 * link or belong to the circle.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

namespace Daguah\Core;

use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Post types and their fields.
 */
final class Post_Types {

	public const MOMENT = 'calendar_moment';
	public const CIRCLE = 'circle';
	public const LIVE   = 'live_session';

	/* Circle fields kept out of REST. */
	public const MEMBERS    = 'daguah_members';
	public const INVITE_KEY = 'daguah_invite_key';

	/**
	 * Hooks.
	 */
	public static function init(): void {
		add_action( 'init', array( self::class, 'register' ), 15 );
		add_action( 'enqueue_block_editor_assets', array( self::class, 'editor_assets' ) );
		add_action( 'add_meta_boxes_' . self::CIRCLE, array( self::class, 'members_box' ) );
		add_filter( 'wp_insert_post_data', array( self::class, 'keep_circles_private' ), 10, 2 );
		add_action( 'save_post_' . self::CIRCLE, array( self::class, 'ensure_invite_key' ) );
	}

	/**
	 * Field definitions per post type. Used to register meta and to build
	 * the editor panel.
	 *
	 * Types: date, datetime, text, url, number, toggle, select, vendor,
	 * product, products, occasion, category.
	 *
	 * @return array<string, array<string, array>>
	 */
	public static function fields(): array {
		return array(
			self::MOMENT => array(
				'daguah_starts'     => array(
					'label' => __( 'Starts', 'daguah' ),
					'type'  => 'date',
				),
				'daguah_ends'       => array(
					'label' => __( 'Ends', 'daguah' ),
					'type'  => 'date',
					'help'  => __( 'Leave empty for a single day.', 'daguah' ),
				),
				'daguah_recurring'  => array(
					'label' => __( 'Comes round every year', 'daguah' ),
					'type'  => 'toggle',
				),
				'daguah_occasion'   => array(
					'label' => __( 'Occasion to show', 'daguah' ),
					'type'  => 'occasion',
					'help'  => __( 'The collection under the ribbon shows pieces tagged with this occasion.', 'daguah' ),
				),
				'daguah_category'   => array(
					'label' => __( 'Only this category (optional)', 'daguah' ),
					'type'  => 'category',
				),
				'daguah_limit'      => array(
					'label' => __( 'Pieces to show', 'daguah' ),
					'type'  => 'number',
					'min'   => 2,
					'max'   => 24,
				),
				'daguah_date_note'  => array(
					'label' => __( 'Date note', 'daguah' ),
					'type'  => 'text',
					'help'  => __( 'Shown beside the date, for example "Date depends on the moon sighting".', 'daguah' ),
				),
			),
			self::CIRCLE => array(
				'daguah_host'            => array(
					'label' => __( 'Host user ID', 'daguah' ),
					'type'  => 'number',
					'min'   => 0,
				),
				'daguah_fabric_product'  => array(
					'label' => __( 'Fabric', 'daguah' ),
					'type'  => 'product',
				),
				'daguah_yards_per_person' => array(
					'label' => __( 'Yards per person', 'daguah' ),
					'type'  => 'number',
					'min'   => 1,
					'max'   => 20,
				),
				'daguah_delivery_mode'   => array(
					'label'   => __( 'Delivery', 'daguah' ),
					'type'    => 'select',
					'options' => array(
						'split'  => __( 'Each member at their own address', 'daguah' ),
						'single' => __( 'Everything to the host', 'daguah' ),
					),
				),
				'daguah_closes'          => array(
					'label' => __( 'Closes to new members', 'daguah' ),
					'type'  => 'date',
				),
			),
			self::LIVE   => array(
				'daguah_stall'      => array(
					'label' => __( 'Stall', 'daguah' ),
					'type'  => 'vendor',
				),
				'daguah_market'     => array(
					'label' => __( 'Market', 'daguah' ),
					'type'  => 'text',
					'help'  => __( 'For example Makola Market, Accra.', 'daguah' ),
				),
				'daguah_starts_at'  => array(
					'label' => __( 'Starts (UTC)', 'daguah' ),
					'type'  => 'datetime',
				),
				'daguah_ends_at'    => array(
					'label' => __( 'Ends (UTC)', 'daguah' ),
					'type'  => 'datetime',
				),
				'daguah_stream_url' => array(
					'label' => __( 'Stream link', 'daguah' ),
					'type'  => 'url',
					'help'  => __( 'YouTube Live or Mux.', 'daguah' ),
				),
				'daguah_products'   => array(
					'label' => __( 'Pieces in the tray', 'daguah' ),
					'type'  => 'products',
				),
			),
		);
	}

	/**
	 * Register the post types and their meta.
	 */
	public static function register(): void {
		$common = array(
			'show_in_rest' => true,
			'show_ui'      => true,
			'show_in_menu' => Settings::PAGE,
			'map_meta_cap' => true,
		);

		register_post_type(
			self::MOMENT,
			$common + array(
				'labels'          => self::labels( __( 'Calendar moments', 'daguah' ), __( 'Calendar moment', 'daguah' ) ),
				'public'          => false,
				'rest_base'       => 'calendar-moments',
				'capability_type' => 'post',
				// The editor is what puts these in the block editor, with the Details panel.
				// Its body is an optional longer description of the moment.
				'supports'        => array( 'title', 'editor', 'excerpt', 'custom-fields', 'page-attributes' ),
			)
		);

		register_post_type(
			self::CIRCLE,
			$common + array(
				'labels'          => self::labels( __( 'Circles', 'daguah' ), __( 'Circle', 'daguah' ) ),
				'public'          => false,
				'rest_base'       => 'circles',
				'capability_type' => 'post',
				// The body is the host's note to the family, shown on the invite page.
				'supports'        => array( 'title', 'editor', 'author', 'custom-fields' ),
			)
		);

		register_post_type(
			self::LIVE,
			$common + array(
				'labels'          => self::labels( __( 'Live sessions', 'daguah' ), __( 'Live session', 'daguah' ) ),
				'public'          => true,
				'has_archive'     => false,
				'rewrite'         => array( 'slug' => 'live' ),
				'rest_base'       => 'live-sessions',
				'capability_type' => 'post',
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
			)
		);

		foreach ( self::fields() as $post_type => $fields ) {
			foreach ( $fields as $key => $field ) {
				register_post_meta( $post_type, $key, self::meta_args( $field ) );
			}
		}

		register_post_meta(
			self::CIRCLE,
			self::MEMBERS,
			array(
				'type'              => 'array',
				'single'            => true,
				'default'           => array(),
				'show_in_rest'      => false,
				'sanitize_callback' => array( self::class, 'clean_members' ),
				'auth_callback'     => static fn( $allowed, $key, $post_id ) => current_user_can( 'edit_post', (int) $post_id ),
			)
		);
	}

	/**
	 * Meta arguments for one field.
	 *
	 * @param array $field Field definition.
	 */
	private static function meta_args( array $field ): array {
		$auth = static fn( $allowed, $key, $post_id ) => current_user_can( 'edit_post', (int) $post_id );
		$args = array(
			'single'        => true,
			'auth_callback' => $auth,
			'show_in_rest'  => true,
		);

		switch ( $field['type'] ) {
			case 'toggle':
				return $args + array(
					'type'    => 'boolean',
					'default' => false,
				);

			case 'number':
				$min = (float) ( $field['min'] ?? 0 );
				$max = (float) ( $field['max'] ?? PHP_INT_MAX );

				return $args + array(
					'type'              => 'number',
					'default'           => 0,
					'sanitize_callback' => static fn( $v ) => min( $max, max( $min, (float) $v ) ),
				);

			case 'vendor':
			case 'product':
			case 'occasion':
			case 'category':
				return $args + array(
					'type'              => 'integer',
					'default'           => 0,
					'sanitize_callback' => 'absint',
				);

			case 'products':
				return array_merge(
					$args,
					array(
						'type'              => 'array',
						'default'           => array(),
						'sanitize_callback' => static fn( $v ) => array_values( array_unique( array_filter( array_map( 'absint', (array) $v ) ) ) ),
						'show_in_rest'      => array( 'schema' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ) ),
					)
				);

			case 'date':
				return $args + array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => static fn( $v ) => preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $v ) ? (string) $v : '',
				);

			case 'datetime':
				return $args + array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => static fn( $v ) => preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', substr( (string) $v, 0, 16 ) ) ? substr( (string) $v, 0, 16 ) : '',
				);

			case 'url':
				return $args + array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => array( Product_Data::class, 'clean_video_url' ),
				);

			case 'select':
				$options = array_keys( $field['options'] );

				return $args + array(
					'type'              => 'string',
					'default'           => (string) $options[0],
					'sanitize_callback' => static fn( $v ) => in_array( (string) $v, $options, true ) ? (string) $v : (string) $options[0],
				);

			default:
				return $args + array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => static fn( $v ) => sanitize_text_field( (string) $v ),
				);
		}
	}

	/**
	 * Standard labels.
	 *
	 * @param string $plural   Plural name.
	 * @param string $singular Singular name.
	 */
	private static function labels( string $plural, string $singular ): array {
		return array(
			'name'          => $plural,
			'singular_name' => $singular,
			/* translators: %s: item name */
			'add_new_item'  => sprintf( __( 'Add %s', 'daguah' ), strtolower( $singular ) ),
			/* translators: %s: item name */
			'edit_item'     => sprintf( __( 'Edit %s', 'daguah' ), strtolower( $singular ) ),
			'all_items'     => $plural,
			/* translators: %s: items name */
			'not_found'     => sprintf( __( 'No %s yet.', 'daguah' ), strtolower( $plural ) ),
		);
	}

	/* Circles -------------------------------------------------------------- */

	/**
	 * Members: name, city, country, currency, paid or waiting, and the order
	 * and user they belong to.
	 *
	 * @param mixed $value Raw rows.
	 */
	public static function clean_members( $value ): array {
		$clean = array();

		foreach ( (array) $value as $row ) {
			if ( ! is_array( $row ) || '' === trim( (string) ( $row['name'] ?? '' ) ) ) {
				continue;
			}

			$currency = strtoupper( (string) ( $row['currency'] ?? '' ) );

			$clean[] = array(
				'name'     => sanitize_text_field( (string) $row['name'] ),
				'city'     => sanitize_text_field( (string) ( $row['city'] ?? '' ) ),
				'country'  => strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', (string) ( $row['country'] ?? '' ) ) ?? '', 0, 2 ) ),
				'currency' => Countries::is_currency( $currency ) ? $currency : 'GBP',
				'status'   => 'paid' === ( $row['status'] ?? '' ) ? 'paid' : 'waiting',
				'order_id' => absint( $row['order_id'] ?? 0 ),
				'user_id'  => absint( $row['user_id'] ?? 0 ),
				'is_host'  => ! empty( $row['is_host'] ),
			);
		}

		return $clean;
	}

	/**
	 * Circles never become public, whatever status is asked for.
	 *
	 * @param array $data    Post data about to be saved.
	 * @param array $postarr Raw post array.
	 */
	public static function keep_circles_private( array $data, array $postarr ): array {
		if ( self::CIRCLE === ( $data['post_type'] ?? '' ) && in_array( $data['post_status'] ?? '', array( 'publish', 'future' ), true ) ) {
			$data['post_status'] = 'private';
		}

		return $data;
	}

	/**
	 * Every circle gets an unguessable invite key the first time it is saved.
	 *
	 * @param int $post_id Circle ID.
	 */
	public static function ensure_invite_key( int $post_id ): void {
		if ( wp_is_post_revision( $post_id ) || '' !== (string) get_post_meta( $post_id, self::INVITE_KEY, true ) ) {
			return;
		}

		update_post_meta( $post_id, self::INVITE_KEY, wp_generate_password( 32, false ) );
	}

	/**
	 * Read only list of members in the circle editor.
	 */
	public static function members_box(): void {
		add_meta_box(
			'daguah-circle-members',
			__( 'Members', 'daguah' ),
			static function ( WP_Post $post ): void {
				$members = self::clean_members( get_post_meta( $post->ID, self::MEMBERS, true ) );

				if ( ! $members ) {
					echo '<p>' . esc_html__( 'No one has joined yet. Members are added when they accept the invite link.', 'daguah' ) . '</p>';
					return;
				}

				echo '<table class="widefat striped"><thead><tr>';
				foreach ( array( __( 'Name', 'daguah' ), __( 'City', 'daguah' ), __( 'Currency', 'daguah' ), __( 'Status', 'daguah' ), __( 'Order', 'daguah' ) ) as $heading ) {
					echo '<th scope="col">' . esc_html( $heading ) . '</th>';
				}
				echo '</tr></thead><tbody>';

				foreach ( $members as $m ) {
					$order = $m['order_id'] ? '#' . $m['order_id'] : __( 'Not yet created', 'daguah' );
					printf(
						'<tr><td>%1$s%2$s</td><td>%3$s, %4$s</td><td>%5$s</td><td>%6$s</td><td>%7$s</td></tr>',
						esc_html( $m['name'] ),
						$m['is_host'] ? ' <em>' . esc_html__( '(host)', 'daguah' ) . '</em>' : '',
						esc_html( $m['city'] ),
						esc_html( Countries::name( $m['country'] ) ),
						esc_html( $m['currency'] ),
						'paid' === $m['status'] ? esc_html__( 'Paid', 'daguah' ) : esc_html__( 'Waiting', 'daguah' ),
						esc_html( $order )
					);
				}

				echo '</tbody></table>';
			},
			self::CIRCLE,
			'normal'
		);
	}

	/* Editor panel ---------------------------------------------------------- */

	/**
	 * Load the Details panel with its field list and choices.
	 */
	public static function editor_assets(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ! array_key_exists( (string) $screen->post_type, self::fields() ) ) {
			return;
		}

		$asset_path = DAGUAH_CORE_DIR . 'build/editor-panels.asset.php';
		$asset      = is_readable( $asset_path ) ? require $asset_path : array();

		wp_enqueue_script(
			'daguah-editor-panels',
			DAGUAH_CORE_URL . 'build/editor-panels.js',
			(array) ( $asset['dependencies'] ?? array() ),
			(string) ( $asset['version'] ?? DAGUAH_CORE_VERSION ),
			true
		);
		wp_set_script_translations( 'daguah-editor-panels', 'daguah' );

		wp_add_inline_script(
			'daguah-editor-panels',
			'window.daguahPanels = ' . wp_json_encode(
				array(
					'fields'  => array( (string) $screen->post_type => self::fields()[ $screen->post_type ] ),
					'choices' => self::choices(),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Choices for the vendor, product, occasion and category pickers.
	 */
	public static function choices(): array {
		$terms = static function ( string $taxonomy ): array {
			$list = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				)
			);

			return is_array( $list ) ? array_map( static fn( $t ) => array( 'value' => $t->term_id, 'label' => $t->name ), $list ) : array();
		};

		$vendors = get_users(
			array(
				'role__in' => array( 'seller', 'administrator' ),
				'fields'   => array( 'ID' ),
				'number'   => 500,
			)
		);

		$products = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 500,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'fields'         => 'ids',
			)
		);

		return array(
			'vendors'    => array_values(
				array_filter(
					array_map(
						static fn( $u ) => Vendor::is_vendor( (int) $u->ID ) ? array( 'value' => (int) $u->ID, 'label' => Vendor::store_name( (int) $u->ID ) ) : null,
						$vendors
					)
				)
			),
			'products'   => array_map( static fn( $id ) => array( 'value' => (int) $id, 'label' => get_the_title( $id ) ), $products ),
			'occasions'  => taxonomy_exists( Taxonomies::OCCASION ) ? $terms( Taxonomies::OCCASION ) : array(),
			'categories' => taxonomy_exists( 'product_cat' ) ? $terms( 'product_cat' ) : array(),
		);
	}
}
