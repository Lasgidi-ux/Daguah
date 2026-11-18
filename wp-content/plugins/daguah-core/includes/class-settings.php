<?php
/**
 * DAGUAH settings: one option, one admin page.
 *
 * Holds the demo currency rates, the duties estimate table, the commission
 * rate, how long DAGUAH Hold waits before confirming delivery on its own, and
 * feature flags. Every value is validated on save and again on read.
 *
 * @package Daguah\Core
 */

declare( strict_types=1 );

namespace Daguah\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Settings storage and screen.
 */
final class Settings {

	public const OPTION = 'daguah_settings';
	public const PAGE   = 'daguah';
	public const CAP    = 'manage_options';

	/**
	 * Cached settings for this request.
	 *
	 * @var array|null
	 */
	private static ?array $cache = null;

	/**
	 * Feature flags and what each one controls.
	 *
	 * @return array<string, string>
	 */
	public static function flag_labels(): array {
		return array(
			'circle'           => __( 'DAGUAH Circle: group orders across countries', 'daguah' ),
			'send_home'        => __( 'Send Home: gifts for family, with a voice note', 'daguah' ),
			'market_live'      => __( 'Market Live: live shopping from stalls', 'daguah' ),
			'market_sound'     => __( 'Market sound toggle in the header (always off until a visitor turns it on)', 'daguah' ),
			'cursor_thread'    => __( 'Thread line that follows the cursor on desktop', 'daguah' ),
			'whatsapp_listing' => __( 'List by WhatsApp (needs a WhatsApp Business account)', 'daguah' ),
		);
	}

	/**
	 * Values used until the client sets their own.
	 *
	 * Rates and duties are demo figures for staging. They are labelled as
	 * such everywhere a buyer can see them.
	 */
	public static function defaults(): array {
		return array(
			'currency_source'   => 'demo',
			'demo_rates'        => array(
				'USD' => 1.27,
				'CAD' => 1.74,
				'EUR' => 1.17,
				'NGN' => 2050.0,
				'GHS' => 15.6,
				'KES' => 164.0,
			),
			'duties'            => array(
				'GB' => array( 'rate' => 20.0, 'threshold' => 0.0 ),
				'US' => array( 'rate' => 15.0, 'threshold' => 0.0 ),
				'CA' => array( 'rate' => 18.0, 'threshold' => 20.0 ),
				'EU' => array( 'rate' => 21.0, 'threshold' => 0.0 ),
				'NG' => array( 'rate' => 20.0, 'threshold' => 0.0 ),
				'GH' => array( 'rate' => 20.0, 'threshold' => 0.0 ),
				'KE' => array( 'rate' => 25.0, 'threshold' => 0.0 ),
			),
			'commission'        => 12.0,
			'auto_confirm_days' => 7,
			'flags'             => array(
				'circle'           => true,
				'send_home'        => true,
				'market_live'      => true,
				'market_sound'     => true,
				'cursor_thread'    => true,
				'whatsapp_listing' => false,
			),
		);
	}

	/**
	 * All settings, validated and merged over the defaults.
	 */
	public static function all(): array {
		if ( null === self::$cache ) {
			$stored      = get_option( self::OPTION, array() );
			self::$cache = self::sanitize( is_array( $stored ) ? $stored : array(), false );
		}

		return self::$cache;
	}

	/**
	 * One setting.
	 *
	 * @param string $key Top level key.
	 * @return mixed
	 */
	public static function get( string $key ) {
		return self::all()[ $key ] ?? null;
	}

	/**
	 * Is a feature switched on?
	 *
	 * @param string $flag Flag name.
	 */
	public static function flag( string $flag ): bool {
		return ! empty( self::all()['flags'][ $flag ] );
	}

	/**
	 * Replace settings. Used by the seed command.
	 *
	 * @param array $values Partial settings to merge in.
	 */
	public static function update( array $values ): void {
		update_option( self::OPTION, self::sanitize( array_replace_recursive( self::all(), $values ), false ) );
		self::$cache = null;
	}

	/**
	 * Validate settings. Unknown keys are dropped; out of range numbers are clamped.
	 *
	 * @param mixed $input    Raw input.
	 * @param bool  $from_form True when the input comes from the settings form,
	 *                         where an unticked flag is absent rather than false.
	 */
	public static function sanitize( $input, bool $from_form = true ): array {
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();
		$clean    = $defaults;

		$clean['currency_source'] = in_array( $input['currency_source'] ?? '', array( 'demo', 'plugin' ), true )
			? $input['currency_source']
			: $defaults['currency_source'];

		foreach ( $defaults['demo_rates'] as $currency => $default ) {
			$rate                              = isset( $input['demo_rates'][ $currency ] ) ? (float) $input['demo_rates'][ $currency ] : $default;
			$clean['demo_rates'][ $currency ] = $rate > 0 && $rate < 1000000 ? round( $rate, 4 ) : $default;
		}

		foreach ( $defaults['duties'] as $zone => $default ) {
			$row = isset( $input['duties'][ $zone ] ) && is_array( $input['duties'][ $zone ] ) ? $input['duties'][ $zone ] : $default;

			$clean['duties'][ $zone ] = array(
				'rate'      => round( min( 100.0, max( 0.0, (float) ( $row['rate'] ?? $default['rate'] ) ) ), 2 ),
				'threshold' => round( min( 100000.0, max( 0.0, (float) ( $row['threshold'] ?? $default['threshold'] ) ) ), 2 ),
			);
		}

		$clean['commission']        = round( min( 50.0, max( 0.0, (float) ( $input['commission'] ?? $defaults['commission'] ) ) ), 2 );
		$clean['auto_confirm_days'] = (int) min( 60, max( 1, (int) ( $input['auto_confirm_days'] ?? $defaults['auto_confirm_days'] ) ) );

		foreach ( $defaults['flags'] as $flag => $default ) {
			$clean['flags'][ $flag ] = $from_form
				? ! empty( $input['flags'][ $flag ] )
				: (bool) ( $input['flags'][ $flag ] ?? $default );
		}

		return $clean;
	}

	/**
	 * Hooks.
	 */
	public static function init(): void {
		add_action( 'admin_init', array( self::class, 'register' ) );
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'update_option_' . self::OPTION, static fn() => self::$cache = null );
	}

	/**
	 * Register the option with the Settings API.
	 */
	public static function register(): void {
		register_setting(
			self::PAGE,
			self::OPTION,
			array(
				'type'              => 'object',
				'sanitize_callback' => array( self::class, 'sanitize' ),
				'default'           => self::defaults(),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Top level DAGUAH menu. Post types for calendar moments, circles and
	 * live sessions sit under it.
	 */
	public static function menu(): void {
		add_menu_page(
			__( 'DAGUAH settings', 'daguah' ),
			__( 'DAGUAH', 'daguah' ),
			self::CAP,
			self::PAGE,
			array( self::class, 'render' ),
			'dashicons-store',
			56
		);

		add_submenu_page(
			self::PAGE,
			__( 'DAGUAH settings', 'daguah' ),
			__( 'Settings', 'daguah' ),
			self::CAP,
			self::PAGE,
			array( self::class, 'render' )
		);
	}

	/**
	 * The settings screen.
	 */
	public static function render(): void {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}

		$s    = self::all();
		$name = self::OPTION;
		?>
		<div class="wrap daguah-admin">
			<h1><?php esc_html_e( 'DAGUAH settings', 'daguah' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( self::PAGE ); ?>

				<h2><?php esc_html_e( 'Currency', 'daguah' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="daguah-currency-source"><?php esc_html_e( 'Rates come from', 'daguah' ); ?></label></th>
						<td>
							<select id="daguah-currency-source" name="<?php echo esc_attr( $name ); ?>[currency_source]">
								<option value="demo" <?php selected( $s['currency_source'], 'demo' ); ?>><?php esc_html_e( 'Demo rates below (staging only)', 'daguah' ); ?></option>
								<option value="plugin" <?php selected( $s['currency_source'], 'plugin' ); ?>><?php esc_html_e( 'The multi currency plugin', 'daguah' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'While demo rates are in use, every converted price on the site is labelled "Demo rates".', 'daguah' ); ?></p>
						</td>
					</tr>
				</table>

				<table class="widefat daguah-admin__table">
					<caption><?php esc_html_e( 'Demo rates: how much of each currency one pound buys', 'daguah' ); ?></caption>
					<thead><tr><th scope="col"><?php esc_html_e( 'Currency', 'daguah' ); ?></th><th scope="col"><?php esc_html_e( 'Per £1', 'daguah' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $s['demo_rates'] as $currency => $rate ) : ?>
						<tr>
							<th scope="row"><label for="daguah-rate-<?php echo esc_attr( $currency ); ?>"><?php echo esc_html( $currency ); ?></label></th>
							<td><input id="daguah-rate-<?php echo esc_attr( $currency ); ?>" type="number" step="0.0001" min="0.0001" required name="<?php echo esc_attr( $name ); ?>[demo_rates][<?php echo esc_attr( $currency ); ?>]" value="<?php echo esc_attr( (string) $rate ); ?>"></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<h2><?php esc_html_e( 'Duties estimate', 'daguah' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Shown in the landed price before a buyer pays, as an estimate. These figures are placeholders: replace them with advice from a customs broker, or connect a duties service such as Zonos.', 'daguah' ); ?></p>
				<table class="widefat daguah-admin__table">
					<thead><tr>
						<th scope="col"><?php esc_html_e( 'Delivery zone', 'daguah' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Estimated charges, % of item and delivery', 'daguah' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Charged only above, £', 'daguah' ); ?></th>
					</tr></thead>
					<tbody>
					<?php foreach ( $s['duties'] as $zone => $row ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( Countries::zones()[ $zone ] ?? $zone ); ?></th>
							<td><input aria-label="<?php echo esc_attr( sprintf( /* translators: %s: zone name */ __( 'Duties rate for %s', 'daguah' ), Countries::zones()[ $zone ] ?? $zone ) ); ?>" type="number" step="0.01" min="0" max="100" name="<?php echo esc_attr( $name ); ?>[duties][<?php echo esc_attr( $zone ); ?>][rate]" value="<?php echo esc_attr( (string) $row['rate'] ); ?>"></td>
							<td><input aria-label="<?php echo esc_attr( sprintf( /* translators: %s: zone name */ __( 'Duties threshold for %s', 'daguah' ), Countries::zones()[ $zone ] ?? $zone ) ); ?>" type="number" step="0.01" min="0" name="<?php echo esc_attr( $name ); ?>[duties][<?php echo esc_attr( $zone ); ?>][threshold]" value="<?php echo esc_attr( (string) $row['threshold'] ); ?>"></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<h2><?php esc_html_e( 'Marketplace', 'daguah' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="daguah-commission"><?php esc_html_e( 'Commission, %', 'daguah' ); ?></label></th>
						<td>
							<input id="daguah-commission" type="number" step="0.01" min="0" max="50" name="<?php echo esc_attr( $name ); ?>[commission]" value="<?php echo esc_attr( (string) $s['commission'] ); ?>">
							<p class="description"><?php esc_html_e( 'Shown to sellers before they list. Applied to payouts from Stage 6.', 'daguah' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="daguah-auto-confirm"><?php esc_html_e( 'DAGUAH Hold confirms delivery after, days', 'daguah' ); ?></label></th>
						<td>
							<input id="daguah-auto-confirm" type="number" step="1" min="1" max="60" name="<?php echo esc_attr( $name ); ?>[auto_confirm_days]" value="<?php echo esc_attr( (string) $s['auto_confirm_days'] ); ?>">
							<p class="description"><?php esc_html_e( 'Counted from the day tracking shows delivered, if the buyer neither confirms nor reports a problem.', 'daguah' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Features', 'daguah' ); ?></h2>
				<fieldset>
					<legend class="screen-reader-text"><?php esc_html_e( 'Features', 'daguah' ); ?></legend>
					<?php foreach ( self::flag_labels() as $flag => $label ) : ?>
						<p>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[flags][<?php echo esc_attr( $flag ); ?>]" value="1" <?php checked( ! empty( $s['flags'][ $flag ] ) ); ?>>
								<?php echo esc_html( $label ); ?>
							</label>
						</p>
					<?php endforeach; ?>
				</fieldset>

				<?php submit_button( __( 'Save settings', 'daguah' ) ); ?>
			</form>
		</div>
		<?php
	}
}
