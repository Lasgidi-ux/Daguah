<?php
/**
 * Seed and reset.
 *
 * Seeding is idempotent. Everything it creates carries a _daguah_seed marker
 * with a stable key, so a second run finds and updates what the first run
 * made instead of duplicating it. Reset deletes only marked content: terms,
 * pages and users that existed before seeding are never touched.
 *
 * Pages and the navigation are created when missing and then left alone, so
 * edits made in the Site Editor survive a reseed. Stalls, pieces, moments,
 * the sample circle, the live session and the Journal article are brought
 * back to the seed data on every run.
 *
 * Uses WooCommerce's and Dokan's own APIs, so their hooks and caches stay
 * consistent. All email is held back while seeding.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

namespace Daguah\Core;

use WC_Install;
use WC_Product_Attribute;
use WC_Product_Simple;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Demo content for staging and review.
 */
final class Seeder {

	public const MARK = '_daguah_seed';

	/**
	 * Seed data.
	 *
	 * @var array
	 */
	private array $data;

	/**
	 * Progress reporter.
	 *
	 * @var callable
	 */
	private $log;

	/**
	 * IDs made or found this run, by type and seed key.
	 *
	 * @var array<string, array<string, int>>
	 */
	private array $ids = array();

	/**
	 * Counts for the summary.
	 *
	 * @var array<string, int>
	 */
	private array $counts = array();

	/**
	 * Set up.
	 *
	 * @param callable|null $log Receives one line of progress at a time.
	 */
	private function __construct( ?callable $log ) {
		$this->data = require __DIR__ . '/data.php';
		$this->log  = $log ?? static function (): void {};
	}

	/**
	 * Why seeding cannot run, or null if it can.
	 */
	public static function requirements(): ?WP_Error {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new WP_Error( 'daguah_needs_woocommerce', __( 'Activate WooCommerce before seeding.', 'daguah' ) );
		}

		if ( ! function_exists( 'dokan' ) ) {
			return new WP_Error( 'daguah_needs_dokan', __( 'Activate Dokan before seeding.', 'daguah' ) );
		}

		return null;
	}

	/**
	 * Create or update all seed content.
	 *
	 * @param callable|null $log Progress reporter.
	 * @return array<string, int>|WP_Error Counts by type.
	 */
	public static function run( ?callable $log = null ) {
		$problem = self::requirements();

		if ( $problem ) {
			return $problem;
		}

		$seeder = new self( $log );

		return $seeder->with_quiet_admin( array( $seeder, 'seed_all' ) );
	}

	/**
	 * Delete everything seeding created.
	 *
	 * @param callable|null $log Progress reporter.
	 * @return array<string, int> Counts by type.
	 */
	public static function reset( ?callable $log = null ): array {
		$seeder = new self( $log );

		return $seeder->with_quiet_admin( array( $seeder, 'reset_all' ) );
	}

	/**
	 * Run a step as an administrator with outgoing mail held back.
	 *
	 * @param callable $step Work to do.
	 * @return mixed
	 */
	private function with_quiet_admin( callable $step ) {
		$previous_user = get_current_user_id();

		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			$admins = get_users(
				array(
					'role'   => 'administrator',
					'number' => 1,
					'fields' => 'ID',
				)
			);
			if ( $admins ) {
				wp_set_current_user( (int) $admins[0] );
			}
		}

		$no_mail = static fn() => true;
		add_filter( 'pre_wp_mail', $no_mail, 999 );
		add_filter( 'wp_send_new_user_notification_to_admin', '__return_false', 999 );
		add_filter( 'wp_send_new_user_notification_to_user', '__return_false', 999 );

		try {
			return $step();
		} finally {
			remove_filter( 'pre_wp_mail', $no_mail, 999 );
			remove_filter( 'wp_send_new_user_notification_to_admin', '__return_false', 999 );
			remove_filter( 'wp_send_new_user_notification_to_user', '__return_false', 999 );
			wp_set_current_user( $previous_user );
		}
	}

	/**
	 * Report progress.
	 *
	 * @param string $line Message.
	 */
	private function say( string $line ): void {
		( $this->log )( $line );
	}

	/**
	 * Count one item of a type.
	 *
	 * @param string $type Type.
	 */
	private function tally( string $type ): void {
		$this->counts[ $type ] = ( $this->counts[ $type ] ?? 0 ) + 1;
	}

	/* Seeding --------------------------------------------------------------- */

	/**
	 * Every step, in dependency order.
	 *
	 * @return array<string, int>
	 */
	public function seed_all(): array {
		$this->site();
		$this->terms();
		$this->vendors();
		$this->products();
		$this->moments();
		$this->circle();
		$this->live_session();
		$this->journal();
		$this->pages();
		$this->navigation();

		flush_rewrite_rules( false );

		return $this->counts;
	}

	/**
	 * Store and site options, and the demo settings if none are saved yet.
	 */
	private function site(): void {
		update_option( 'blogname', 'DAGUAH' );
		update_option( 'blogdescription', 'Wherever you are, the market is open.' );

		if ( '' === (string) get_option( 'permalink_structure' ) ) {
			update_option( 'permalink_structure', '/%postname%/' );
		}

		update_option( 'woocommerce_currency', Countries::BASE_CURRENCY );
		update_option( 'woocommerce_default_country', 'GB' );
		update_option( 'woocommerce_weight_unit', 'kg' );
		update_option( 'woocommerce_dimension_unit', 'cm' );
		update_option( 'woocommerce_enable_guest_checkout', 'yes' );
		update_option( 'woocommerce_coming_soon', 'no' );

		if ( false === get_option( Settings::OPTION ) ) {
			add_option( Settings::OPTION, Settings::defaults() );
		}

		if ( class_exists( WC_Install::class ) && wc_get_page_id( 'shop' ) <= 0 ) {
			WC_Install::create_pages();
		}

		$this->say( 'Store set to pounds sterling, United Kingdom, centimetres, guest checkout on.' );
	}

	/**
	 * Categories, origin countries, occasions and attribute terms.
	 */
	private function terms(): void {
		foreach ( $this->data['categories'] as $slug => $name ) {
			$this->ids['category'][ $slug ] = $this->term( 'product_cat', $name, $slug );
		}

		foreach ( $this->data['countries'] as $iso => $name ) {
			$id = $this->term( Taxonomies::ORIGIN, $name, sanitize_title( $name ) );
			update_term_meta( $id, 'daguah_iso', $iso );
			$this->ids['country'][ $iso ] = $id;
		}

		foreach ( $this->data['occasions'] as $slug => list( $name, $description ) ) {
			$this->ids['occasion'][ $slug ] = $this->term( Taxonomies::OCCASION, $name, $slug, $description );
		}

		$this->ids['attribute'] = Taxonomies::ensure_attributes();

		foreach ( $this->data['attributes'] as $attribute => $names ) {
			foreach ( $names as $name ) {
				$this->ids[ 'pa_' . $attribute ][ $name ] = $this->term( 'pa_' . $attribute, $name, sanitize_title( $name ) );
			}
		}

		$this->say( sprintf( 'Terms ready: %d categories, %d countries, %d occasions, 3 attributes.', count( $this->data['categories'] ), count( $this->data['countries'] ), count( $this->data['occasions'] ) ) );
	}

	/**
	 * Find or create a term. Only terms created here are marked.
	 *
	 * @param string $taxonomy    Taxonomy.
	 * @param string $name        Name.
	 * @param string $slug        Slug.
	 * @param string $description Description.
	 */
	private function term( string $taxonomy, string $name, string $slug, string $description = '' ): int {
		$existing = get_term_by( 'slug', $slug, $taxonomy );

		if ( $existing ) {
			if ( '' !== $description && get_term_meta( $existing->term_id, self::MARK, true ) ) {
				wp_update_term( $existing->term_id, $taxonomy, array( 'description' => $description ) );
			}

			return (int) $existing->term_id;
		}

		$created = wp_insert_term(
			$name,
			$taxonomy,
			array(
				'slug'        => $slug,
				'description' => $description,
			)
		);

		if ( is_wp_error( $created ) ) {
			$this->say( sprintf( 'Could not create %s "%s": %s', $taxonomy, $name, $created->get_error_message() ) );
			return (int) ( $created->get_error_data( 'term_exists' ) ?? 0 );
		}

		update_term_meta( (int) $created['term_id'], self::MARK, $slug );
		$this->tally( 'terms' );

		return (int) $created['term_id'];
	}

	/**
	 * Stalls, created through Dokan.
	 */
	private function vendors(): void {
		foreach ( $this->data['vendors'] as $key => $v ) {
			$found = get_users(
				array(
					'meta_key'   => self::MARK, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Seeding only.
					'meta_value' => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Seeding only.
					'number'     => 1,
					'fields'     => 'ID',
				)
			);

			$address = array(
				'street_1' => '',
				'city'     => $v['city'],
				'zip'      => '',
				'country'  => $v['country'],
				'state'    => '',
			);

			if ( $found ) {
				$id     = (int) $found[0];
				$vendor = dokan()->vendor->get( $id );
				$vendor->set_store_name( $v['store'] );
				$vendor->set_address( $address );
				$vendor->save();
				update_user_meta( $id, 'dokan_store_name', $v['store'] );
			} else {
				$email = $v['slug'] . '@vendors.daguah.test';

				if ( email_exists( $email ) || username_exists( $v['slug'] ) ) {
					$this->say( sprintf( 'Skipped stall %s: a user with that email or username already exists and was not made by the seed.', $v['store'] ) );
					continue;
				}

				$vendor = dokan()->vendor->create(
					array(
						'email'         => $email,
						'user_login'    => $v['slug'],
						'user_nicename' => $v['slug'],
						'display_name'  => $v['store'],
						'first_name'    => $v['first'],
						'last_name'     => $v['last'],
						'store_name'    => $v['store'],
						'address'       => $address,
						'enabled'       => true,
						'trusted'       => true,
					)
				);

				if ( is_wp_error( $vendor ) ) {
					$this->say( sprintf( 'Could not create stall %s: %s', $v['store'], $vendor->get_error_message() ) );
					continue;
				}

				$id = (int) $vendor->get_id();
				update_user_meta( $id, self::MARK, $key );
				$this->tally( 'stalls created' );
			}

			update_user_meta( $id, 'first_name', $v['first'] );
			update_user_meta( $id, 'last_name', $v['last'] );
			update_user_meta( $id, Vendor::REGION, $v['region'] );
			update_user_meta( $id, Vendor::SHIPS_FROM, $v['ships_from'] );
			update_user_meta( $id, Vendor::SINCE, $v['since'] );
			update_user_meta( $id, Vendor::RESPONSE, $v['response'] );
			update_user_meta( $id, Vendor::STORY, $v['story'] );
			update_user_meta( $id, Vendor::VERIFIED, $v['verified'] );
			Vendor::refresh_cloth( $id );

			$this->ids['vendor'][ $key ] = $id;
		}

		$this->say( sprintf( 'Stalls ready: %d.', count( $this->ids['vendor'] ?? array() ) ) );
	}

	/**
	 * Products, created through WooCommerce, owned by their stall.
	 */
	private function products(): void {
		foreach ( $this->data['products'] as $key => $p ) {
			$vendor_id = $this->ids['vendor'][ $p['vendor'] ] ?? 0;

			if ( ! $vendor_id ) {
				$this->say( sprintf( 'Skipped "%s": its stall was not created.', $p['name'] ) );
				continue;
			}

			$id      = $this->find_post( 'product', $key );
			$product = $id ? wc_get_product( $id ) : null;
			$product = $product instanceof WC_Product_Simple ? $product : new WC_Product_Simple();

			if ( ! $product->get_id() ) {
				$product->set_slug( sanitize_title( $p['name'] ) );
			}

			$product->set_name( $p['name'] );
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'visible' );
			$product->set_regular_price( wc_format_decimal( $p['price'], 2 ) );
			$product->set_short_description( $p['short'] );
			$product->set_description( $p['description'] );
			$product->set_manage_stock( true );
			$product->set_stock_quantity( (int) $p['stock'] );
			$product->set_sold_individually( 1 === (int) $p['stock'] );
			$product->set_category_ids( array_filter( array( $this->ids['category'][ $p['category'] ] ?? 0 ) ) );
			$product->set_attributes( $this->attributes_for( $p ) );

			$vendor_from = (string) $this->data['vendors'][ $p['vendor'] ]['ships_from'];
			$from        = (string) ( $p['ships_from'] ?? $vendor_from );

			Product_Data::write(
				$product,
				array(
					'measurements' => $p['measurements'],
					'maker'        => $p['maker'],
					'origin_town'  => $p['town'],
					'corridors'    => $this->corridors_from( $from ),
					'passport'     => $p['passport'] ?? array(),
					'authenticity' => $p['authenticity'],
					'shot_note'    => $p['shot'],
					'video_url'    => '',
				)
			);

			$product->update_meta_data( self::MARK, $key );
			$id = (int) $product->save();

			if ( ! $id ) {
				$this->say( sprintf( 'Could not save "%s".', $p['name'] ) );
				continue;
			}

			// Dokan treats the post author as the stall that sells the piece.
			if ( (int) get_post_field( 'post_author', $id ) !== $vendor_id ) {
				wp_update_post(
					array(
						'ID'          => $id,
						'post_author' => $vendor_id,
					)
				);
			}

			wp_set_object_terms( $id, array_values( array_filter( array_map( fn( $slug ) => $this->ids['occasion'][ $slug ] ?? 0, $p['occasions'] ) ) ), Taxonomies::OCCASION );
			wp_set_object_terms( $id, array_filter( array( $this->ids['country'][ $p['country'] ] ?? 0 ) ), Taxonomies::ORIGIN );

			$this->ids['product'][ $key ] = $id;
			$this->tally( 'products' );
		}

		$this->say( sprintf( 'Pieces ready: %d.', count( $this->ids['product'] ?? array() ) ) );
	}

	/**
	 * Attribute objects for fabric, condition and size.
	 *
	 * @param array $p Product seed data.
	 * @return WC_Product_Attribute[]
	 */
	private function attributes_for( array $p ): array {
		$attributes = array();
		$position   = 0;

		foreach ( array( 'fabric', 'condition', 'size' ) as $name ) {
			$term = $this->ids[ 'pa_' . $name ][ $p[ $name ] ] ?? 0;
			$id   = (int) ( $this->ids['attribute'][ $name ] ?? 0 );

			if ( ! $term || ! $id ) {
				continue;
			}

			$attribute = new WC_Product_Attribute();
			$attribute->set_id( $id );
			$attribute->set_name( 'pa_' . $name );
			$attribute->set_options( array( $term ) );
			$attribute->set_position( $position++ );
			$attribute->set_visible( true );
			$attribute->set_variation( false );

			$attributes[ 'pa_' . $name ] = $attribute;
		}

		return $attributes;
	}

	/**
	 * Corridor rows for a ship from country.
	 *
	 * @param string $from ISO code.
	 */
	private function corridors_from( string $from ): array {
		$rows = array();

		foreach ( $this->data['corridors'][ $from ] ?? array() as $to => list( $min, $max, $price ) ) {
			$rows[] = array(
				'from'     => $from,
				'to'       => $to,
				'min_days' => $min,
				'max_days' => $max,
				'price'    => $price,
			);
		}

		return $rows;
	}

	/**
	 * Calendar moments.
	 */
	private function moments(): void {
		foreach ( $this->data['moments'] as $key => list( $title, $excerpt, $starts, $ends, $recurring, $occasion, $category, $order, $note ) ) {
			$id = $this->upsert_post(
				Post_Types::MOMENT,
				$key,
				array(
					'post_title'   => $title,
					'post_excerpt' => $excerpt,
					'post_status'  => 'publish',
					'menu_order'   => $order,
				)
			);

			if ( ! $id ) {
				continue;
			}

			update_post_meta( $id, 'daguah_starts', $starts );
			update_post_meta( $id, 'daguah_ends', $ends );
			update_post_meta( $id, 'daguah_recurring', (bool) $recurring );
			update_post_meta( $id, 'daguah_occasion', $this->ids['occasion'][ $occasion ] ?? 0 );
			update_post_meta( $id, 'daguah_category', $category ? ( $this->ids['category'][ $category ] ?? 0 ) : 0 );
			update_post_meta( $id, 'daguah_limit', 8 );
			update_post_meta( $id, 'daguah_date_note', $note );
		}

		$this->say( sprintf( 'Calendar moments ready: %d.', count( $this->data['moments'] ) ) );
	}

	/**
	 * The sample circle. Its invite key is made on first save and kept.
	 */
	private function circle(): void {
		$c  = $this->data['circle'];
		$id = $this->upsert_post(
			Post_Types::CIRCLE,
			'sample-circle',
			array(
				'post_title'  => $c['title'],
				'post_status' => 'private',
			)
		);

		if ( ! $id ) {
			return;
		}

		update_post_meta( $id, 'daguah_fabric_product', $this->ids['product'][ $c['product'] ] ?? 0 );
		update_post_meta( $id, 'daguah_yards_per_person', $c['yards_per_person'] );
		update_post_meta( $id, 'daguah_delivery_mode', $c['delivery_mode'] );
		update_post_meta( $id, 'daguah_closes', $c['closes'] );
		update_post_meta( $id, Post_Types::MEMBERS, $c['members'] );
		Post_Types::ensure_invite_key( $id );

		$this->say( sprintf( 'Sample circle ready: %d members in %d countries.', count( $c['members'] ), count( array_unique( array_column( $c['members'], 'country' ) ) ) ) );
	}

	/**
	 * The Market Live sessions.
	 */
	private function live_session(): void {
		$sessions = array_merge( array( 'makola-live' => $this->data['live'] ), $this->data['live_more'] ?? array() );

		// Times in the data are relative ("+3 days 17:00"), read in UTC.
		$at = static fn( string $when ): string => preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $when ) ? $when : gmdate( 'Y-m-d\TH:i', (int) strtotime( $when . ' UTC' ) );

		foreach ( $sessions as $key => $l ) {
			$id = $this->upsert_post(
				Post_Types::LIVE,
				$key,
				array(
					'post_title'   => $l['title'],
					'post_excerpt' => $l['excerpt'],
					'post_content' => self::paragraph( $l['content'] ),
					'post_status'  => 'publish',
				)
			);

			if ( ! $id ) {
				continue;
			}

			update_post_meta( $id, 'daguah_stall', $this->ids['vendor'][ $l['vendor'] ] ?? 0 );
			update_post_meta( $id, 'daguah_market', $l['market'] );
			update_post_meta( $id, 'daguah_starts_at', $at( $l['starts'] ) );
			update_post_meta( $id, 'daguah_ends_at', $at( $l['ends'] ) );
			update_post_meta( $id, 'daguah_stream_url', '' );
			update_post_meta( $id, 'daguah_products', array_values( array_filter( array_map( fn( $k ) => $this->ids['product'][ $k ] ?? 0, $l['products'] ) ) ) );
		}

		$this->say( sprintf( 'Market Live sessions ready: %d.', count( $sessions ) ) );
	}

	/**
	 * The launch Journal article.
	 */
	private function journal(): void {
		$j       = $this->data['journal'];
		$content = '';

		foreach ( $j['content'] as list( $tag, $text ) ) {
			$content .= 'h2' === $tag
				? '<!-- wp:heading -->' . "\n" . '<h2 class="wp-block-heading">' . esc_html( $text ) . '</h2>' . "\n" . '<!-- /wp:heading -->' . "\n\n"
				: self::paragraph( $text ) . "\n\n";
		}

		$this->upsert_post(
			'post',
			'journal-kente',
			array(
				'post_title'   => $j['title'],
				'post_name'    => $j['slug'],
				'post_excerpt' => $j['excerpt'],
				'post_content' => trim( $content ),
				'post_status'  => 'publish',
			)
		);

		$this->say( 'Journal article ready.' );
	}

	/**
	 * Pages: created when missing, never overwritten.
	 */
	private function pages(): void {
		foreach ( $this->data['pages'] as $slug => list( $title, $content, $template ) ) {
			$page = get_page_by_path( $slug );

			if ( ! $page ) {
				$id = wp_insert_post(
					array(
						'post_type'    => 'page',
						'post_status'  => 'publish',
						'post_name'    => $slug,
						'post_title'   => $title,
						'post_content' => $this->page_content( $content ),
						'meta_input'   => array( self::MARK => 'page-' . $slug ),
					)
				);

				if ( ! $id || is_wp_error( $id ) ) {
					$this->say( sprintf( 'Could not create page %s.', $title ) );
					continue;
				}

				$this->tally( 'pages created' );
			} else {
				$id = (int) $page->ID;

				// A page the seed made that still holds the "arrives in stage" stub
				// gets its real opening once that stage is built. Edited pages are left alone.
				if ( get_post_meta( $id, self::MARK, true ) && str_contains( (string) $page->post_content, 'This page arrives in stage' ) && ! str_starts_with( $content, 'stage:' ) ) {
					wp_update_post(
						array(
							'ID'           => $id,
							'post_content' => $this->page_content( $content ),
						)
					);
				}
			}

			// Apply the template once the theme provides it.
			if ( '' !== $template && function_exists( 'get_block_template' ) && get_block_template( get_stylesheet() . '//' . $template ) ) {
				update_post_meta( $id, '_wp_page_template', $template );
			}

			$this->ids['page'][ $slug ] = (int) $id;
		}

		// The home page arrives in Stage 3 as the daguah/home pattern. Until
		// then the system sheet is the front page.
		$front = $this->ids['page']['system'] ?? 0;

		if ( \WP_Block_Patterns_Registry::get_instance()->is_registered( 'daguah/home' ) ) {
			$home = get_page_by_path( 'home' );
			$front = $home ? (int) $home->ID : (int) wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_name'    => 'home',
					'post_title'   => 'Home',
					'post_content' => '<!-- wp:pattern {"slug":"daguah/home"} /-->',
					'meta_input'   => array( self::MARK => 'page-home' ),
				)
			);
		}

		if ( $front ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $front );
		}

		if ( ! empty( $this->ids['page']['journal'] ) ) {
			update_option( 'page_for_posts', $this->ids['page']['journal'] );
		}

		$this->say( sprintf( 'Pages ready: %d.', count( $this->ids['page'] ?? array() ) ) );
	}

	/**
	 * Body for a seeded page.
	 *
	 * @param string $content Content code from the seed data.
	 */
	private function page_content( string $content ): string {
		if ( str_starts_with( $content, 'intro:' ) ) {
			$intro = (string) ( $this->data['intros'][ substr( $content, 6 ) ] ?? '' );
			return '' !== $intro ? self::paragraph( $intro ) : '';
		}

		if ( str_starts_with( $content, 'stage:' ) ) {
			return self::paragraph( sprintf( 'This page arrives in stage %s of the build. It is here now so that no link on the site leads nowhere.', substr( $content, 6 ) ) );
		}

		return match ( $content ) {
			'about'   => self::paragraph( 'DAGUAH is a marketplace for heritage fashion, fabric and accessories, made by verified African makers and by the diaspora. It is built for the family in Peckham buying aso ebi for a wedding in Lagos, and for the weaver in Bonwire who wants to sell to them directly.' )
				. "\n\n" . self::paragraph( 'Every piece shows where it was made and who made it, what it costs to reach your door, and whether it will fit. Your payment is held until it arrives.' ),
			'legal'   => self::paragraph( 'The wording for this page is being prepared with DAGUAH\'s legal adviser and will be published before launch.' ),
			'credits' => self::paragraph( 'The cloth patterns on DAGUAH are placeholders until the textile artists who will draw them are confirmed. Their names and their work will be credited here.' ),
			default   => '',
		};
	}

	/**
	 * The primary navigation, unless the site already has a real one.
	 *
	 * WordPress makes a fallback menu that just lists every page the first
	 * time a header renders without one. That fallback does not count: the
	 * seeded menu is created anyway, and as the newest menu the header uses it.
	 */
	private function navigation(): void {
		if ( $this->find_post( 'wp_navigation', 'navigation' ) ) {
			$this->say( 'Navigation already seeded; left as it is.' );
			return;
		}

		$menus = get_posts(
			array(
				'post_type'   => 'wp_navigation',
				'post_status' => 'publish',
				'numberposts' => 20,
			)
		);

		foreach ( $menus as $menu ) {
			$is_fallback = '' === trim( (string) preg_replace( '/<!--\s*wp:page-list\b[^>]*\/-->/', '', (string) $menu->post_content ) );

			if ( ! $is_fallback ) {
				$this->say( sprintf( 'A menu called "%s" already exists; left as it is.', $menu->post_title ) );
				return;
			}
		}

		$markup = '';
		foreach ( $this->data['navigation'] as $slug => $label ) {
			$markup .= sprintf(
				'<!-- wp:navigation-link %s /-->',
				wp_json_encode(
					array(
						'label'          => $label,
						'url'            => '/' . $slug . '/',
						'kind'           => 'custom',
						'isTopLevelLink' => true,
					)
				)
			);
		}

		wp_insert_post(
			array(
				'post_type'    => 'wp_navigation',
				'post_status'  => 'publish',
				'post_name'    => 'primary',
				'post_title'   => 'Primary',
				'post_content' => $markup,
				'meta_input'   => array( self::MARK => 'navigation' ),
			)
		);

		$this->say( 'Navigation created.' );
	}

	/* Helpers -------------------------------------------------------------- */

	/**
	 * ID of a marked post, or 0.
	 *
	 * @param string $post_type Post type.
	 * @param string $key       Seed key.
	 */
	private function find_post( string $post_type, string $key ): int {
		$found = get_posts(
			array(
				'post_type'   => $post_type,
				'post_status' => 'any',
				'numberposts' => 1,
				'fields'      => 'ids',
				'meta_key'    => self::MARK, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Seeding only.
				'meta_value'  => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Seeding only.
			)
		);

		return $found ? (int) $found[0] : 0;
	}

	/**
	 * Create or update a marked post.
	 *
	 * @param string $post_type Post type.
	 * @param string $key       Seed key.
	 * @param array  $fields    Post fields.
	 */
	private function upsert_post( string $post_type, string $key, array $fields ): int {
		$id     = $this->find_post( $post_type, $key );
		$fields = array_merge( $fields, array( 'post_type' => $post_type ) );

		if ( $id ) {
			$fields['ID'] = $id;
			$result       = wp_update_post( $fields, true );
		} else {
			$fields['meta_input'] = array( self::MARK => $key );
			$result               = wp_insert_post( $fields, true );
			if ( ! is_wp_error( $result ) ) {
				$this->tally( $post_type );
			}
		}

		if ( is_wp_error( $result ) ) {
			$this->say( sprintf( 'Could not save %s "%s": %s', $post_type, $fields['post_title'] ?? $key, $result->get_error_message() ) );
			return 0;
		}

		return (int) $result;
	}

	/**
	 * A paragraph block.
	 *
	 * @param string $text Plain text.
	 */
	private static function paragraph( string $text ): string {
		return '<!-- wp:paragraph -->' . "\n" . '<p>' . esc_html( $text ) . '</p>' . "\n" . '<!-- /wp:paragraph -->';
	}

	/* Reset ---------------------------------------------------------------- */

	/**
	 * Delete everything marked.
	 *
	 * @return array<string, int>
	 */
	public function reset_all(): array {
		require_once ABSPATH . 'wp-admin/includes/user.php';

		$types = array( 'product', Post_Types::MOMENT, Post_Types::CIRCLE, Post_Types::LIVE, 'post', 'page', 'wp_navigation' );
		$posts = get_posts(
			array(
				'post_type'   => $types,
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
				'meta_key'    => self::MARK, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Reset only.
			)
		);

		$front = (int) get_option( 'page_on_front' );

		foreach ( $posts as $id ) {
			$product = 'product' === get_post_type( $id ) && function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;

			if ( $product ) {
				$product->delete( true );
			} else {
				wp_delete_post( (int) $id, true );
			}

			$this->tally( 'posts deleted' );
		}

		if ( $front && in_array( $front, array_map( 'intval', $posts ), true ) ) {
			update_option( 'show_on_front', 'posts' );
			update_option( 'page_on_front', 0 );
		}

		$users = get_users(
			array(
				'meta_key' => self::MARK, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Reset only.
				'fields'   => 'ID',
			)
		);

		foreach ( $users as $user_id ) {
			wp_delete_user( (int) $user_id );
			$this->tally( 'stalls deleted' );
		}

		$taxonomies = array_filter(
			array( 'product_cat', Taxonomies::OCCASION, Taxonomies::ORIGIN, 'pa_fabric', 'pa_condition', 'pa_size' ),
			'taxonomy_exists'
		);

		$terms = get_terms(
			array(
				'taxonomy'   => array_values( $taxonomies ),
				'hide_empty' => false,
				'meta_key'   => self::MARK, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Reset only.
			)
		);

		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			wp_delete_term( $term->term_id, $term->taxonomy );
			$this->tally( 'terms deleted' );
		}

		$this->say( 'Seeded content removed. Settings, attributes and anything not made by the seed were left in place.' );

		return $this->counts;
	}
}
