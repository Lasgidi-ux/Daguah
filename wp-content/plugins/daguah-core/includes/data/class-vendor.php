<?php
/**
 * Stall fields for Dokan vendors, and each stall's cloth.
 *
 * Region (which picks the cloth family), selling since year, typical
 * response time, story, voice intro, verified level and the country the
 * stall ships from. Stored as user meta.
 *
 * Admins edit these on the user's profile screen. Sellers edit everything
 * except the verified level from their own profile; the seller studio
 * restyle in Stage 6 moves that form into the Dokan dashboard.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

namespace Daguah\Core;

use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Vendor meta and cloth cache.
 */
final class Vendor {

	public const REGION     = 'daguah_region';
	public const SINCE      = 'daguah_selling_since';
	public const RESPONSE   = 'daguah_response_hours';
	public const STORY      = 'daguah_story';
	public const VOICE      = 'daguah_voice_intro';
	public const VERIFIED   = 'daguah_verified_level';
	public const SHIPS_FROM = 'daguah_ships_from';
	public const CLOTH      = 'daguah_cloth';

	private const NONCE = 'daguah_vendor_nonce';

	/**
	 * What each verified level means. Each level includes the ones below it.
	 *
	 * @return array<int, string>
	 */
	public static function verified_levels(): array {
		return array(
			0 => __( 'Not yet verified', 'daguah' ),
			1 => __( 'Identity checked', 'daguah' ),
			2 => __( 'Identity checked and video call', 'daguah' ),
			3 => __( 'Identity, video call and samples checked', 'daguah' ),
		);
	}

	/**
	 * Hooks.
	 */
	public static function init(): void {
		add_action( 'init', array( self::class, 'register' ) );

		add_action( 'show_user_profile', array( self::class, 'profile_fields' ) );
		add_action( 'edit_user_profile', array( self::class, 'profile_fields' ) );
		add_action( 'personal_options_update', array( self::class, 'save_profile' ) );
		add_action( 'edit_user_profile_update', array( self::class, 'save_profile' ) );

		// Make the cloth when a stall opens and when its name changes.
		add_action( 'dokan_new_seller_created', array( self::class, 'refresh_cloth' ) );
		add_action( 'dokan_store_profile_saved', array( self::class, 'refresh_cloth' ) );
		add_action( 'profile_update', array( self::class, 'refresh_cloth' ) );
	}

	/**
	 * Register user meta with cleaning. Not exposed over REST.
	 */
	public static function register(): void {
		$fields = array(
			self::REGION     => array( 'string', static fn( $v ) => sanitize_text_field( (string) $v ) ),
			self::SINCE      => array( 'integer', static fn( $v ) => self::clean_year( $v ) ),
			self::RESPONSE   => array( 'integer', static fn( $v ) => max( 0, min( 168, (int) $v ) ) ),
			self::STORY      => array( 'string', static fn( $v ) => sanitize_textarea_field( (string) $v ) ),
			self::VOICE      => array( 'integer', static fn( $v ) => self::clean_audio( $v ) ),
			self::VERIFIED   => array( 'integer', static fn( $v ) => array_key_exists( (int) $v, self::verified_levels() ) ? (int) $v : 0 ),
			self::SHIPS_FROM => array( 'string', static fn( $v ) => array_key_exists( strtoupper( (string) $v ), Countries::origins() ) ? strtoupper( (string) $v ) : '' ),
		);

		foreach ( $fields as $key => list( $type, $clean ) ) {
			register_meta(
				'user',
				$key,
				array(
					'type'              => $type,
					'single'            => true,
					'sanitize_callback' => $clean,
					'show_in_rest'      => false,
				)
			);
		}
	}

	/**
	 * A plausible year a stall started selling, or 0.
	 *
	 * @param mixed $value Raw value.
	 */
	public static function clean_year( $value ): int {
		$year = (int) $value;

		return $year >= 1950 && $year <= (int) gmdate( 'Y' ) ? $year : 0;
	}

	/**
	 * An attachment ID that is an audio file, or 0.
	 *
	 * @param mixed $value Raw value.
	 */
	public static function clean_audio( $value ): int {
		$id = (int) $value;

		return $id && str_starts_with( (string) get_post_mime_type( $id ), 'audio/' ) ? $id : 0;
	}

	/**
	 * Is this user a stall holder?
	 *
	 * @param int $user_id User ID.
	 */
	public static function is_vendor( int $user_id ): bool {
		if ( function_exists( 'dokan_is_user_seller' ) ) {
			return (bool) dokan_is_user_seller( $user_id );
		}

		$user = get_userdata( $user_id );

		return $user instanceof WP_User && in_array( 'seller', (array) $user->roles, true );
	}

	/**
	 * The stall's public name.
	 *
	 * @param int $user_id User ID.
	 */
	public static function store_name( int $user_id ): string {
		$name = (string) get_user_meta( $user_id, 'dokan_store_name', true );

		if ( '' === $name ) {
			$user = get_userdata( $user_id );
			$name = $user instanceof WP_User ? $user->display_name : '';
		}

		return $name;
	}

	/**
	 * All stall fields for a vendor.
	 *
	 * @param int $user_id User ID.
	 */
	public static function get( int $user_id ): array {
		$meta = static fn( string $key ) => get_user_meta( $user_id, $key, true );

		return array(
			'id'             => $user_id,
			'name'           => self::store_name( $user_id ),
			'url'            => function_exists( 'dokan_get_store_url' ) ? (string) dokan_get_store_url( $user_id ) : '',
			'region'         => (string) $meta( self::REGION ),
			'selling_since'  => (int) $meta( self::SINCE ),
			'response_hours' => (int) $meta( self::RESPONSE ),
			'story'          => (string) $meta( self::STORY ),
			'voice_intro'    => (int) $meta( self::VOICE ),
			'verified_level' => (int) $meta( self::VERIFIED ),
			'ships_from'     => (string) $meta( self::SHIPS_FROM ),
			'cloth_family'   => Cloth::family_for_region( (string) $meta( self::REGION ) ),
		);
	}

	/**
	 * The stall card: cloth, verified tag, name, place, one line of story,
	 * selling since and reply time. Used on the home page and product pages.
	 *
	 * @param int $user_id Vendor user ID.
	 */
	public static function card( int $user_id ): string {
		if ( ! self::is_vendor( $user_id ) ) {
			return '';
		}

		$stall   = self::get( $user_id );
		$profile = (array) get_user_meta( $user_id, 'dokan_profile_settings', true );
		$city    = (string) ( $profile['address']['city'] ?? '' );
		$country = (string) ( $profile['address']['country'] ?? $stall['ships_from'] );
		$place   = implode( ', ', array_filter( array( $city, $country ? Countries::name( $country ) : '' ) ) );
		$story   = $stall['story'];

		// The first sentence of the story, so every card stays the same length.
		if ( preg_match( '/^.+?[.!?](\s|$)/u', $story, $match ) ) {
			$story = trim( $match[0] );
		}

		$facts = array();
		if ( $stall['selling_since'] ) {
			/* translators: %d: year */
			$facts[] = sprintf( __( 'Selling since %d', 'daguah' ), $stall['selling_since'] );
		}
		if ( $stall['response_hours'] ) {
			/* translators: %d: number of hours */
			$facts[] = sprintf( _n( 'Replies within %d hour', 'Replies within %d hours', $stall['response_hours'], 'daguah' ), $stall['response_hours'] );
		}

		ob_start();
		?>
		<a class="daguah-stall-card" href="<?php echo esc_url( $stall['url'] ?: home_url( '/stalls/' ) ); ?>">
			<span class="daguah-stall-card__cloth daguah-cloth-box" aria-hidden="true">
				<?php echo self::cloth_svg( $user_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Cached SVG built from integers and a fixed palette. ?>
			</span>
			<span class="daguah-stall-card__body">
				<?php if ( $stall['verified_level'] > 0 ) : ?>
					<span><span class="daguah-tag daguah-tag--verified"><?php echo function_exists( 'daguah_icon' ) ? daguah_icon( 'seal-check' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted SVG. ?><?php echo esc_html( self::verified_levels()[ $stall['verified_level'] ] ); ?></span></span>
				<?php endif; ?>
				<span class="daguah-stall-card__name"><?php echo esc_html( $stall['name'] ); ?></span>
				<?php if ( $place ) : ?>
					<span class="daguah-stall-card__place"><?php echo esc_html( $place ); ?></span>
				<?php endif; ?>
				<?php if ( $story ) : ?>
					<span class="daguah-stall-card__story"><?php echo esc_html( $story ); ?></span>
				<?php endif; ?>
				<?php if ( $facts ) : ?>
					<span class="daguah-stall-card__facts">
						<?php foreach ( $facts as $fact ) : ?>
							<span><?php echo esc_html( $fact ); ?></span>
						<?php endforeach; ?>
					</span>
				<?php endif; ?>
			</span>
		</a>
		<?php
		return (string) ob_get_clean();
	}

	/* Cloth --------------------------------------------------------------- */

	/**
	 * Seed for a stall's cloth: store name and region. Change either and the
	 * stall gets a new cloth.
	 *
	 * @param int $user_id User ID.
	 */
	public static function cloth_seed( int $user_id ): string {
		return self::store_name( $user_id ) . '|' . strtolower( (string) get_user_meta( $user_id, self::REGION, true ) );
	}

	/**
	 * The stall's cloth as SVG, from the cache when the seed has not changed.
	 *
	 * @param int $user_id User ID.
	 */
	public static function cloth_svg( int $user_id ): string {
		$seed   = self::cloth_seed( $user_id );
		$family = Cloth::family_for_region( (string) get_user_meta( $user_id, self::REGION, true ) );
		$cache  = get_user_meta( $user_id, self::CLOTH, true );

		if ( is_array( $cache ) && ( $cache['seed'] ?? '' ) === $seed && ( $cache['family'] ?? '' ) === $family && ! empty( $cache['svg'] ) ) {
			return (string) $cache['svg'];
		}

		$svg = Cloth::svg( $seed, $family );

		update_user_meta(
			$user_id,
			self::CLOTH,
			array(
				'seed'   => $seed,
				'family' => $family,
				'svg'    => $svg,
			)
		);

		return $svg;
	}

	/**
	 * Regenerate a stall's cloth if its seed changed. Hooked to Dokan and
	 * profile saves; harmless for users who are not vendors.
	 *
	 * @param int|mixed $user_id User ID.
	 */
	public static function refresh_cloth( $user_id ): void {
		$user_id = (int) $user_id;

		if ( $user_id && self::is_vendor( $user_id ) ) {
			self::cloth_svg( $user_id );
		}
	}

	/* Profile screen ------------------------------------------------------ */

	/**
	 * Stall fields on the user profile screen.
	 *
	 * @param WP_User $user User being edited.
	 */
	public static function profile_fields( WP_User $user ): void {
		if ( ! self::is_vendor( $user->ID ) || ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}

		$v        = self::get( $user->ID );
		$is_admin = current_user_can( 'manage_options' );
		?>
		<h2><?php esc_html_e( 'DAGUAH stall', 'daguah' ); ?></h2>
		<?php wp_nonce_field( 'daguah_save_vendor_' . $user->ID, self::NONCE ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="daguah-region"><?php esc_html_e( 'Region', 'daguah' ); ?></label></th>
				<td>
					<input type="text" class="regular-text" id="daguah-region" name="daguah_vendor[region]" value="<?php echo esc_attr( $v['region'] ); ?>">
					<p class="description"><?php esc_html_e( 'Where the craft comes from, for example Yoruba lands, Ashanti, Mali or Congo. It chooses the cloth pattern for the stall.', 'daguah' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="daguah-ships-from"><?php esc_html_e( 'Ships from', 'daguah' ); ?></label></th>
				<td>
					<select id="daguah-ships-from" name="daguah_vendor[ships_from]">
						<option value=""><?php esc_html_e( 'Choose', 'daguah' ); ?></option>
						<?php foreach ( Countries::origins() as $code => $name ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $v['ships_from'], $code ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="daguah-since"><?php esc_html_e( 'Selling since', 'daguah' ); ?></label></th>
				<td><input type="number" min="1950" max="<?php echo esc_attr( gmdate( 'Y' ) ); ?>" id="daguah-since" name="daguah_vendor[selling_since]" value="<?php echo esc_attr( $v['selling_since'] ? (string) $v['selling_since'] : '' ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="daguah-response"><?php esc_html_e( 'Usually replies within, hours', 'daguah' ); ?></label></th>
				<td><input type="number" min="0" max="168" id="daguah-response" name="daguah_vendor[response_hours]" value="<?php echo esc_attr( $v['response_hours'] ? (string) $v['response_hours'] : '' ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="daguah-story"><?php esc_html_e( 'Story', 'daguah' ); ?></label></th>
				<td><textarea class="large-text" rows="4" id="daguah-story" name="daguah_vendor[story]"><?php echo esc_textarea( $v['story'] ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="daguah-voice"><?php esc_html_e( 'Voice intro, media library ID', 'daguah' ); ?></label></th>
				<td>
					<input type="number" min="0" id="daguah-voice" name="daguah_vendor[voice_intro]" value="<?php echo esc_attr( $v['voice_intro'] ? (string) $v['voice_intro'] : '' ); ?>">
					<p class="description"><?php esc_html_e( 'An audio file already in the media library. A recorder for sellers arrives with the seller studio.', 'daguah' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Verified level', 'daguah' ); ?></th>
				<td>
					<?php if ( $is_admin ) : ?>
						<select id="daguah-verified" name="daguah_vendor[verified_level]" aria-label="<?php esc_attr_e( 'Verified level', 'daguah' ); ?>">
							<?php foreach ( self::verified_levels() as $level => $label ) : ?>
								<option value="<?php echo esc_attr( (string) $level ); ?>" <?php selected( $v['verified_level'], $level ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					<?php else : ?>
						<?php echo esc_html( self::verified_levels()[ $v['verified_level'] ] ); ?>
						<p class="description"><?php esc_html_e( 'Set by the DAGUAH team after each check.', 'daguah' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save the stall fields. Only admins can change the verified level.
	 *
	 * @param int $user_id User being saved.
	 */
	public static function save_profile( int $user_id ): void {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE ] ) ), 'daguah_save_vendor_' . $user_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_user', $user_id ) || ! self::is_vendor( $user_id ) ) {
			return;
		}

		// Each value is cleaned by the sanitize callback registered for its key.
		$input = isset( $_POST['daguah_vendor'] ) && is_array( $_POST['daguah_vendor'] ) ? wp_unslash( $_POST['daguah_vendor'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$keys = array(
			'region'         => self::REGION,
			'ships_from'     => self::SHIPS_FROM,
			'selling_since'  => self::SINCE,
			'response_hours' => self::RESPONSE,
			'story'          => self::STORY,
			'voice_intro'    => self::VOICE,
		);

		if ( current_user_can( 'manage_options' ) ) {
			$keys['verified_level'] = self::VERIFIED;
		}

		foreach ( $keys as $field => $key ) {
			if ( array_key_exists( $field, $input ) ) {
				update_user_meta( $user_id, $key, $input[ $field ] );
			}
		}

		self::refresh_cloth( $user_id );
	}
}
