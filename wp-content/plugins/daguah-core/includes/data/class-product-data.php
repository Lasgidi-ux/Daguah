<?php
/**
 * Product fields: measurements, maker, origin town, delivery corridors,
 * short video, Heritage Passport, authenticity notes and the intended shot.
 *
 * Stored as post meta, registered with schemas so they validate the same way
 * from the admin screen, the REST API and the seed command. Edited in a
 * DAGUAH tab inside WooCommerce's product data box.
 *
 * Fabric, condition and size are WooCommerce attributes, and origin country
 * is the origin_country taxonomy (see Taxonomies), so they are not repeated here.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

namespace Daguah\Core;

use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Product meta: registration, reading, editing.
 */
final class Product_Data {

	public const MEASUREMENTS = 'daguah_measurements';
	public const MAKER        = 'daguah_maker';
	public const ORIGIN_TOWN  = 'daguah_origin_town';
	public const CORRIDORS    = 'daguah_corridors';
	public const VIDEO        = 'daguah_video_url';
	public const PASSPORT     = 'daguah_passport';
	public const AUTHENTICITY = 'daguah_authenticity';
	public const SHOT_NOTE    = 'daguah_shot_note';

	private const NONCE = 'daguah_product_nonce';

	/**
	 * Measurement keys in centimetres, with labels.
	 *
	 * @return array<string, string>
	 */
	public static function measurement_labels(): array {
		return array(
			'bust'     => __( 'Bust or chest', 'daguah' ),
			'waist'    => __( 'Waist', 'daguah' ),
			'hip'      => __( 'Hip', 'daguah' ),
			'length'   => __( 'Length', 'daguah' ),
			'shoulder' => __( 'Shoulder', 'daguah' ),
		);
	}

	/**
	 * Hooks.
	 */
	public static function init(): void {
		add_action( 'init', array( self::class, 'register' ), 20 );
		add_filter( 'woocommerce_product_data_tabs', array( self::class, 'tab' ) );
		add_action( 'woocommerce_product_data_panels', array( self::class, 'panel' ) );
		add_action( 'woocommerce_admin_process_product_object', array( self::class, 'save' ) );
	}

	/**
	 * Register every field on the product post type.
	 */
	public static function register(): void {
		if ( ! post_type_exists( 'product' ) ) {
			return;
		}

		$auth = static fn( $allowed, $key, $post_id ) => current_user_can( 'edit_post', (int) $post_id );

		$number = array(
			'type'    => array( 'number', 'null' ),
			'minimum' => 1,
			'maximum' => 400,
		);

		register_post_meta(
			'product',
			self::MEASUREMENTS,
			array(
				'type'              => 'object',
				'single'            => true,
				'default'           => array(),
				'sanitize_callback' => array( self::class, 'clean_measurements' ),
				'auth_callback'     => $auth,
				'show_in_rest'      => array(
					'schema' => array(
						'type'                 => 'object',
						'additionalProperties' => false,
						'properties'           => array_fill_keys( array_keys( self::measurement_labels() ), $number ),
					),
				),
			)
		);

		foreach ( array( self::MAKER, self::ORIGIN_TOWN, self::SHOT_NOTE ) as $key ) {
			register_post_meta(
				'product',
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'sanitize_callback' => static fn( $value ) => sanitize_text_field( (string) $value ),
					'auth_callback'     => $auth,
					'show_in_rest'      => true,
				)
			);
		}

		register_post_meta(
			'product',
			self::AUTHENTICITY,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => static fn( $value ) => sanitize_textarea_field( (string) $value ),
				'auth_callback'     => $auth,
				'show_in_rest'      => true,
			)
		);

		register_post_meta(
			'product',
			self::VIDEO,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => array( self::class, 'clean_video_url' ),
				'auth_callback'     => $auth,
				'show_in_rest'      => array( 'schema' => array( 'type' => 'string', 'format' => 'uri' ) ),
			)
		);

		register_post_meta(
			'product',
			self::CORRIDORS,
			array(
				'type'              => 'array',
				'single'            => true,
				'default'           => array(),
				'sanitize_callback' => array( self::class, 'clean_corridors' ),
				'auth_callback'     => $auth,
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'                 => 'object',
							'additionalProperties' => false,
							'properties'           => array(
								'from'     => array( 'type' => 'string', 'pattern' => '^[A-Z]{2}$' ),
								'to'       => array( 'type' => 'string', 'enum' => array_keys( Countries::zones() ) ),
								'min_days' => array( 'type' => 'integer', 'minimum' => 0, 'maximum' => 90 ),
								'max_days' => array( 'type' => 'integer', 'minimum' => 0, 'maximum' => 90 ),
								'price'    => array( 'type' => 'number', 'minimum' => 0 ),
							),
						),
					),
				),
			)
		);

		register_post_meta(
			'product',
			self::PASSPORT,
			array(
				'type'              => 'array',
				'single'            => true,
				'default'           => array(),
				'sanitize_callback' => array( self::class, 'clean_passport' ),
				'auth_callback'     => $auth,
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'                 => 'object',
							'additionalProperties' => false,
							'properties'           => array(
								'year'  => array( 'type' => 'integer', 'minimum' => 1900, 'maximum' => 2100 ),
								'place' => array( 'type' => 'string' ),
								'story' => array( 'type' => 'string' ),
							),
						),
					),
				),
			)
		);
	}

	/* Cleaning ------------------------------------------------------------ */

	/**
	 * Keep known measurements between 1 and 400 cm, to one decimal place.
	 *
	 * @param mixed $value Raw value.
	 * @return array<string, float>
	 */
	public static function clean_measurements( $value ): array {
		$value = is_array( $value ) ? $value : array();
		$clean = array();

		foreach ( array_keys( self::measurement_labels() ) as $key ) {
			if ( ! isset( $value[ $key ] ) || '' === $value[ $key ] || null === $value[ $key ] ) {
				continue;
			}

			$number = (float) $value[ $key ];

			if ( $number >= 1 && $number <= 400 ) {
				$clean[ $key ] = round( $number, 1 );
			}
		}

		return $clean;
	}

	/**
	 * Keep corridors with a known ship from country and delivery zone.
	 * Rows with nothing filled in are dropped.
	 *
	 * @param mixed $value Raw rows.
	 * @return array<int, array{from: string, to: string, min_days: int, max_days: int, price: float}>
	 */
	public static function clean_corridors( $value ): array {
		$rows  = is_array( $value ) ? $value : array();
		$clean = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$from = strtoupper( sanitize_key( (string) ( $row['from'] ?? '' ) ) );
			$to   = strtoupper( sanitize_key( (string) ( $row['to'] ?? '' ) ) );

			if ( ! array_key_exists( $from, Countries::origins() ) || ! array_key_exists( $to, Countries::zones() ) ) {
				continue;
			}

			$min = max( 0, min( 90, (int) ( $row['min_days'] ?? 0 ) ) );
			$max = max( $min, min( 90, (int) ( $row['max_days'] ?? $min ) ) );

			$clean[] = array(
				'from'     => $from,
				'to'       => $to,
				'min_days' => $min,
				'max_days' => $max,
				'price'    => round( max( 0.0, (float) ( $row['price'] ?? 0 ) ), 2 ),
			);
		}

		return $clean;
	}

	/**
	 * Keep Heritage Passport lines that have a story.
	 *
	 * @param mixed $value Raw rows.
	 * @return array<int, array{year: int, place: string, story: string}>
	 */
	public static function clean_passport( $value ): array {
		$rows  = is_array( $value ) ? $value : array();
		$clean = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$story = sanitize_text_field( (string) ( $row['story'] ?? '' ) );

			if ( '' === $story ) {
				continue;
			}

			$year    = (int) ( $row['year'] ?? 0 );
			$clean[] = array(
				'year'  => $year >= 1900 && $year <= 2100 ? $year : 0,
				'place' => sanitize_text_field( (string) ( $row['place'] ?? '' ) ),
				'story' => mb_substr( $story, 0, 280 ),
			);
		}

		return $clean;
	}

	/**
	 * Short video: https only, from hosts the product page can embed.
	 *
	 * @param mixed $value Raw URL.
	 */
	public static function clean_video_url( $value ): string {
		$url = esc_url_raw( trim( (string) $value ), array( 'https' ) );

		if ( '' === $url ) {
			return '';
		}

		$host    = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$allowed = (array) apply_filters( 'daguah_video_hosts', array( 'youtube.com', 'www.youtube.com', 'youtu.be', 'vimeo.com', 'player.vimeo.com', 'stream.mux.com' ) );

		return in_array( $host, $allowed, true ) || ( '' !== $host && str_ends_with( $host, '.' . wp_parse_url( home_url(), PHP_URL_HOST ) ) ) ? $url : '';
	}

	/* Reading -------------------------------------------------------------- */

	/**
	 * Every DAGUAH field for a product, cleaned, plus its attributes and origin.
	 *
	 * @param int $product_id Product ID.
	 */
	public static function get( int $product_id ): array {
		$meta = static fn( string $key ) => get_post_meta( $product_id, $key, true );

		return array(
			'measurements'   => self::clean_measurements( $meta( self::MEASUREMENTS ) ),
			'maker'          => (string) $meta( self::MAKER ),
			'origin_town'    => (string) $meta( self::ORIGIN_TOWN ),
			'origin_country' => Taxonomies::origin_iso( $product_id ),
			'corridors'      => self::clean_corridors( $meta( self::CORRIDORS ) ),
			'video_url'      => (string) $meta( self::VIDEO ),
			'passport'       => self::clean_passport( $meta( self::PASSPORT ) ),
			'authenticity'   => (string) $meta( self::AUTHENTICITY ),
			'shot_note'      => (string) $meta( self::SHOT_NOTE ),
			'fabric'         => self::attribute_names( $product_id, 'pa_fabric' ),
			'condition'      => self::attribute_names( $product_id, 'pa_condition' ),
			'size'           => self::attribute_names( $product_id, 'pa_size' ),
		);
	}

	/**
	 * The corridor that delivers to a zone, or null if the piece does not ship there.
	 * When several match, the fastest wins.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $zone       Delivery zone code.
	 */
	public static function corridor_to( int $product_id, string $zone ): ?array {
		$matches = array_values(
			array_filter(
				self::clean_corridors( get_post_meta( $product_id, self::CORRIDORS, true ) ),
				static fn( array $row ) => $row['to'] === $zone
			)
		);

		if ( ! $matches ) {
			return null;
		}

		usort( $matches, static fn( $a, $b ) => array( $a['max_days'], $a['price'] ) <=> array( $b['max_days'], $b['price'] ) );

		return $matches[0];
	}

	/**
	 * Term names for one attribute taxonomy.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $taxonomy   pa_ taxonomy.
	 * @return string[]
	 */
	private static function attribute_names( int $product_id, string $taxonomy ): array {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		$terms = get_the_terms( $product_id, $taxonomy );

		return is_array( $terms ) ? wp_list_pluck( $terms, 'name' ) : array();
	}

	/* Editing -------------------------------------------------------------- */

	/**
	 * Add the DAGUAH tab to the product data box.
	 *
	 * @param array $tabs Tabs.
	 */
	public static function tab( array $tabs ): array {
		$tabs['daguah'] = array(
			'label'    => __( 'DAGUAH', 'daguah' ),
			'target'   => 'daguah_product_data',
			'class'    => array(),
			'priority' => 65,
		);

		return $tabs;
	}

	/**
	 * The tab's fields. Repeaters show the saved rows plus two empty rows;
	 * save again for more. No script needed.
	 */
	public static function panel(): void {
		global $post;

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$data      = self::get( (int) $post->ID );
		$corridors = array_merge( $data['corridors'], array_fill( 0, 2, array( 'from' => '', 'to' => '', 'min_days' => '', 'max_days' => '', 'price' => '' ) ) );
		$passport  = array_merge( $data['passport'], array_fill( 0, 2, array( 'year' => '', 'place' => '', 'story' => '' ) ) );
		?>
		<div id="daguah_product_data" class="panel woocommerce_options_panel hidden daguah-admin">
			<?php wp_nonce_field( 'daguah_save_product', self::NONCE ); ?>

			<div class="options_group">
				<p class="form-field"><strong><?php esc_html_e( 'Measurements, in centimetres', 'daguah' ); ?></strong><br>
				<span class="description"><?php esc_html_e( 'Measure the piece lying flat and double where needed. Fit Mirror compares these with the buyer\'s own.', 'daguah' ); ?></span></p>
				<?php foreach ( self::measurement_labels() as $key => $label ) : ?>
					<p class="form-field">
						<label for="daguah-m-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
						<input type="number" step="0.5" min="1" max="400" class="short" id="daguah-m-<?php echo esc_attr( $key ); ?>" name="daguah[measurements][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( isset( $data['measurements'][ $key ] ) ? (string) $data['measurements'][ $key ] : '' ); ?>">
					</p>
				<?php endforeach; ?>
			</div>

			<div class="options_group">
				<p class="form-field">
					<label for="daguah-maker"><?php esc_html_e( 'Made by', 'daguah' ); ?></label>
					<input type="text" class="short" id="daguah-maker" name="daguah[maker]" value="<?php echo esc_attr( $data['maker'] ); ?>" placeholder="<?php esc_attr_e( 'The weaver, dyer or tailor', 'daguah' ); ?>">
				</p>
				<p class="form-field">
					<label for="daguah-town"><?php esc_html_e( 'Made in (town)', 'daguah' ); ?></label>
					<input type="text" class="short" id="daguah-town" name="daguah[origin_town]" value="<?php echo esc_attr( $data['origin_town'] ); ?>" placeholder="<?php esc_attr_e( 'Abeokuta', 'daguah' ); ?>">
					<span class="description"><?php esc_html_e( 'Pick the country in the Origin countries box.', 'daguah' ); ?></span>
				</p>
				<p class="form-field">
					<label for="daguah-video"><?php esc_html_e( 'Short video', 'daguah' ); ?></label>
					<input type="url" class="short" id="daguah-video" name="daguah[video_url]" value="<?php echo esc_attr( $data['video_url'] ); ?>" placeholder="https://">
					<span class="description"><?php esc_html_e( 'YouTube, Vimeo or Mux link.', 'daguah' ); ?></span>
				</p>
				<p class="form-field">
					<label for="daguah-auth"><?php esc_html_e( 'Authenticity notes', 'daguah' ); ?></label>
					<textarea class="short" rows="3" id="daguah-auth" name="daguah[authenticity]"><?php echo esc_textarea( $data['authenticity'] ); ?></textarea>
				</p>
				<p class="form-field">
					<label for="daguah-shot"><?php esc_html_e( 'Intended photo', 'daguah' ); ?></label>
					<input type="text" class="short" id="daguah-shot" name="daguah[shot_note]" value="<?php echo esc_attr( $data['shot_note'] ); ?>">
					<span class="description"><?php esc_html_e( 'Shown with the seller cloth until a real photo is added.', 'daguah' ); ?></span>
				</p>
			</div>

			<div class="options_group daguah-admin__group">
				<p><strong><?php esc_html_e( 'Delivery by corridor', 'daguah' ); ?></strong><br>
				<span class="description"><?php esc_html_e( 'Where it ships from, where it goes, how long it takes and what it costs in pounds. Empty rows are ignored; save to get more rows.', 'daguah' ); ?></span></p>
				<table class="widefat daguah-admin__table">
					<thead><tr>
						<th scope="col"><?php esc_html_e( 'Ships from', 'daguah' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Delivers to', 'daguah' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Min days', 'daguah' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Max days', 'daguah' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Price, £', 'daguah' ); ?></th>
					</tr></thead>
					<tbody>
					<?php foreach ( $corridors as $i => $row ) : ?>
						<tr>
							<td><select aria-label="<?php esc_attr_e( 'Ships from', 'daguah' ); ?>" name="daguah[corridors][<?php echo (int) $i; ?>][from]">
								<option value=""><?php esc_html_e( 'Choose', 'daguah' ); ?></option>
								<?php foreach ( Countries::origins() as $code => $name ) : ?>
									<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $row['from'], $code ); ?>><?php echo esc_html( $name ); ?></option>
								<?php endforeach; ?>
							</select></td>
							<td><select aria-label="<?php esc_attr_e( 'Delivers to', 'daguah' ); ?>" name="daguah[corridors][<?php echo (int) $i; ?>][to]">
								<option value=""><?php esc_html_e( 'Choose', 'daguah' ); ?></option>
								<?php foreach ( Countries::zones() as $code => $name ) : ?>
									<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $row['to'], $code ); ?>><?php echo esc_html( $name ); ?></option>
								<?php endforeach; ?>
							</select></td>
							<td><input aria-label="<?php esc_attr_e( 'Min days', 'daguah' ); ?>" type="number" min="0" max="90" name="daguah[corridors][<?php echo (int) $i; ?>][min_days]" value="<?php echo esc_attr( (string) $row['min_days'] ); ?>"></td>
							<td><input aria-label="<?php esc_attr_e( 'Max days', 'daguah' ); ?>" type="number" min="0" max="90" name="daguah[corridors][<?php echo (int) $i; ?>][max_days]" value="<?php echo esc_attr( (string) $row['max_days'] ); ?>"></td>
							<td><input aria-label="<?php esc_attr_e( 'Price in pounds', 'daguah' ); ?>" type="number" min="0" step="0.01" name="daguah[corridors][<?php echo (int) $i; ?>][price]" value="<?php echo esc_attr( (string) $row['price'] ); ?>"></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<div class="options_group daguah-admin__group">
				<p><strong><?php esc_html_e( 'Heritage Passport', 'daguah' ); ?></strong><br>
				<span class="description"><?php esc_html_e( 'For preloved pieces: one line for each time the piece was worn and passed on.', 'daguah' ); ?></span></p>
				<table class="widefat daguah-admin__table">
					<thead><tr>
						<th scope="col"><?php esc_html_e( 'Year', 'daguah' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Place', 'daguah' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Story', 'daguah' ); ?></th>
					</tr></thead>
					<tbody>
					<?php foreach ( $passport as $i => $row ) : ?>
						<tr>
							<td><input aria-label="<?php esc_attr_e( 'Year', 'daguah' ); ?>" type="number" min="1900" max="2100" name="daguah[passport][<?php echo (int) $i; ?>][year]" value="<?php echo esc_attr( $row['year'] ? (string) $row['year'] : '' ); ?>"></td>
							<td><input aria-label="<?php esc_attr_e( 'Place', 'daguah' ); ?>" type="text" name="daguah[passport][<?php echo (int) $i; ?>][place]" value="<?php echo esc_attr( $row['place'] ); ?>"></td>
							<td><input aria-label="<?php esc_attr_e( 'Story', 'daguah' ); ?>" type="text" maxlength="280" class="daguah-admin__wide" name="daguah[passport][<?php echo (int) $i; ?>][story]" value="<?php echo esc_attr( $row['story'] ); ?>"></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Save the tab. WooCommerce has already checked its own nonce and the
	 * user's right to edit; this checks ours as well.
	 *
	 * @param WC_Product $product Product being saved.
	 */
	public static function save( WC_Product $product ): void {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE ] ) ), 'daguah_save_product' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $product->get_id() ) ) {
			return;
		}

		// Each value is cleaned by its own method below.
		$input = isset( $_POST['daguah'] ) && is_array( $_POST['daguah'] ) ? wp_unslash( $_POST['daguah'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		self::write(
			$product,
			array(
				'measurements' => $input['measurements'] ?? array(),
				'maker'        => $input['maker'] ?? '',
				'origin_town'  => $input['origin_town'] ?? '',
				'video_url'    => $input['video_url'] ?? '',
				'authenticity' => $input['authenticity'] ?? '',
				'shot_note'    => $input['shot_note'] ?? '',
				'corridors'    => $input['corridors'] ?? array(),
				'passport'     => $input['passport'] ?? array(),
			)
		);
	}

	/**
	 * Write fields onto a product object. The caller saves the product.
	 *
	 * @param WC_Product $product Product.
	 * @param array      $fields  Any of the keys returned by get().
	 */
	public static function write( WC_Product $product, array $fields ): void {
		$map = array(
			'measurements' => array( self::MEASUREMENTS, array( self::class, 'clean_measurements' ) ),
			'maker'        => array( self::MAKER, static fn( $v ) => sanitize_text_field( (string) $v ) ),
			'origin_town'  => array( self::ORIGIN_TOWN, static fn( $v ) => sanitize_text_field( (string) $v ) ),
			'video_url'    => array( self::VIDEO, array( self::class, 'clean_video_url' ) ),
			'authenticity' => array( self::AUTHENTICITY, static fn( $v ) => sanitize_textarea_field( (string) $v ) ),
			'shot_note'    => array( self::SHOT_NOTE, static fn( $v ) => sanitize_text_field( (string) $v ) ),
			'corridors'    => array( self::CORRIDORS, array( self::class, 'clean_corridors' ) ),
			'passport'     => array( self::PASSPORT, array( self::class, 'clean_passport' ) ),
		);

		foreach ( $map as $field => list( $key, $clean ) ) {
			if ( array_key_exists( $field, $fields ) ) {
				$product->update_meta_data( $key, $clean( $fields[ $field ] ) );
			}
		}
	}
}
