<?php
/**
 * Plugin Name:     The Events Calendar: Community Events Extension: Add Event Cost Currency Symbol
 * Description:     Adds a currency symbol selector option to the Community Event's form. This extension's settings are at wp-admin > Events > Settings > Community tab. Additionally, the event creator can override the currency symbol's position instead of using your site's default from wp-admin > Events > Settings > General tab.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__CE_Event_Cost_Currency_Symbol
 * Author:          Modern Tribe, Inc.
 * Author URI:      http://m.tri.be/1971
 * License:         GPLv2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 */

// Do not load unless Tribe Common is fully loaded.
if ( ! class_exists( 'Tribe__Extension' ) ) {
	return;
}

/**
 * Extension main class, class begins loading on init() function.
 */
class Tribe__Extension__CE_Event_Cost_Currency_Symbol extends Tribe__Extension {

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		// Only works with/after version 4.5 as previous versions included the field
		$this->add_required_plugin( 'Tribe__Events__Community__Main', '4.5' );

		// TODO: $this->set_url( 'https://theeventscalendar.com/extensions//' );

		// possibly NOT run this extension (even if activated) if tribe_events_admin_show_cost_field() is false. Wouldn't affect front-end CE form, just that the Community Events option wouldn't then appear.
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'add_settings' ) );
		add_action( 'tribe_events_community_section_before_cost', array( $this, 'display' ) );
	}

	/**
	 * Add options to Tribe settings page
	 *
	 * @see Tribe__Extension__Settings_Helper
	 */
	public function add_settings() {
		require_once dirname( __FILE__ ) . '/src/Tribe/Settings_Helper.php';

		// Setup fields on the settings page
		$setting_helper = new Tribe__Extension__Settings_Helper();

		// List of allowed symbols
		$fields['tribe_ext_ce_cost_currency_symbols'] = array(
			'type'            => 'text',
			'label'           => esc_html__( 'Event Cost: Allowed Currency Symbols', 'tribe-extension' ),
			'tooltip'         => esc_html__( 'Pipe-separated list of allowed currency symbols. (Once this option is set and saved, you will be able to pick the Default Currency Symbol for new events.) The drop-down will display these in the order you enter them. Example 1: $|€|£|Fr|¥|CNY|C$|AU$. Example 2: USD|EUR|GBP|JPY|CNY|CAD|AUD', 'tribe-extension' ),
			'validation_type' => 'textarea',
		);

		// only allow modifying after the List exists (to be able to pick from)
		if ( tribe_get_option( 'tribe_ext_ce_cost_currency_symbols' ) ) {
			$fields['tribe_ext_ce_cost_currency_symbol_default'] = array(
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Event Cost: Default Currency Symbol', 'tribe-extension' ),
				'tooltip'         => esc_html__( 'The default currency symbol for new events (does not apply to editing existing events).', 'tribe-extension' ),
				'options'         => $this->symbol_list_array( true ),
				'validation_type' => 'options',
			);
		}

		$setting_helper->add_fields(
			$fields,
			'community',
			'current-community-events-slug'
		);
	}

	public function symbol_list_array( $prepend_empty = false ) {
		$setting = tribe_get_option( 'tribe_ext_ce_cost_currency_symbols' );

		if ( '' === (string) $setting ) {
			return array();
		}

		$settings = explode( '|', $setting );

		$settings = (array) apply_filters( 'tribe_ext_ce_cost_currency_symbol_list_array', $settings );

		$symbols = array();

		foreach ( $settings as $key => $value ) {
			$value = esc_attr( sanitize_text_field( $value ) );
			if ( '' !== $value ) {
				$symbols[$value] = $value;
			}
		}

		if ( ! empty( (bool) $prepend_empty ) ) {
			$symbols = array( '' => '' ) + $symbols;
		}

		return $symbols;
	}

	protected function get_current_symbol() {
		$post_id = Tribe__Main::post_id_helper();

		// first check $_POST, else check post meta
		if ( isset( $_POST['EventCurrencySymbol'] ) ) {
			$currency_symbol = $_POST['EventCurrencySymbol'];
		} else {
			$currency_symbol = tribe_get_event_meta( $post_id, '_EventCurrencySymbol', true );
		}

		return esc_attr( $currency_symbol );
	}

	protected function get_current_symbol_position() {
		$post_id = Tribe__Main::post_id_helper();

		// first check $_POST, else check post meta
		if ( isset( $_POST['EventCurrencyPosition'] ) ) {
			$prefix_suffix = $_POST['EventCurrencyPosition'];
		} else {
			$prefix_suffix = tribe_get_event_meta( $post_id, '_EventCurrencyPosition', true );
		}

		return esc_attr( $prefix_suffix );
	}

	protected function get_symbol_position_default() {
		$default_position = tribe_get_option( 'reverseCurrencyPosition' ); // boolean
		if ( ! empty( $default_position ) ) {
			$default_position = 'suffix';
		} else {
			$default_position = 'prefix';
		}

		$default_position = apply_filters( 'tribe_ext_ce_cost_currency_symbol_position_default', $default_position );

		return sanitize_text_field( $default_position );
	}

	protected function get_symbol_position_phrase( $position = 'prefix' ) {
		if ( 'prefix' === $position ) {
			$output = esc_attr_x( 'Before Cost', 'Currency symbol position', 'tribe-extension' );
		} else {
			$output = esc_attr_x( 'After Cost', 'Currency symbol position', 'tribe-extension' );
		}

		return $output;
	}

	protected function symbol_list_select_options() {
		$symbol_list_array = $this->symbol_list_array();

		if ( empty( $symbol_list_array ) ) {
			return '';
		}

		$current_symbol = (string) $this->get_current_symbol();

		$post_id = Tribe__Main::post_id_helper();

		// Awaiting https://central.tri.be/issues/80685 instead of the empty() check here
		if ( '' === $current_symbol && empty( $post_id ) ) {
			$default_symbol = tribe_get_option( 'tribe_ext_ce_cost_currency_symbol_default' );
			$current_symbol = apply_filters( 'tribe_ext_ce_cost_currency_symbol_default', $default_symbol );
		}

		$output = sprintf( '%1$s<option value=""></option>%1$s', PHP_EOL );

		foreach ( $symbol_list_array as $key => $value ) {
			$value = esc_attr( $value );

			$selected = selected( $value, $current_symbol, false );

			$opt_text = esc_attr_x( $value, 'Currency symbol', 'tribe-extension' );

			$output .= sprintf( '<option value="%s" %s>%s</option>%s', $value, $selected, $opt_text, PHP_EOL );
		}

		return $output;
	}

	protected function symbol_position_list_select_options() {
		$default_position = $this->get_symbol_position_default();

		if ( 'prefix' === $default_position ) {
			$opposite = 'suffix';
		} else {
			$opposite = 'prefix';
		}

		$symbol_position_list_array[$opposite] = $this->get_symbol_position_phrase( $opposite );

		$current_position = (string) $this->get_current_symbol_position();

		$post_id = Tribe__Main::post_id_helper();

		// Awaiting https://central.tri.be/issues/80685 instead of the empty() check here
		if ( '' === $current_position && empty( $post_id ) ) {
			$current_position = $default_position;
		}

		$output = sprintf( '%1$s<option value=""></option>%1$s', PHP_EOL );

		foreach ( $symbol_position_list_array as $key => $value ) {
			$key = esc_attr( $key );

			$value = esc_attr( $value );

			$selected = selected( $key, $current_position, false );

			$opt_text = esc_attr_x( $value, 'Currency symbol position', 'tribe-extension' );

			$output .= sprintf( '<option value="%s" %s>%s</option>%s', $key, $selected, $opt_text, PHP_EOL );
		}

		return $output;
	}

	public function display() {
		$symbol_list = $this->symbol_list_select_options();

		$symbol_position_list = $this->symbol_position_list_select_options();

		// if no symbols are entered in Settings, do not display Symbol or Position on CE form
		if ( empty( $symbol_list ) ) {
			return false;
		}

		?>

		<table class="tribe-section-content">
			<colgroup>
				<col class="tribe-colgroup tribe-colgroup-label">
				<col class="tribe-colgroup tribe-colgroup-field">
			</colgroup>

			<tr class="tribe-section-content-row">
				<td class="tribe-section-content-label">
					<?php tribe_community_events_field_label( 'EventCurrencySymbol', __( 'Currency Symbol:', 'tribe-extension' ) ); ?>
				</td>
				<td class="tribe-section-content-field">
					<select
						id="EventCurrencySymbol"
						aria-label="<?php esc_html_e( 'Currency Symbol', 'tribe-extension' ); ?>"
						name="EventCurrencySymbol"
						class="event-cost-currency-symbol tribe-dropdown"
						placeholder="<?php esc_attr_e( 'Select a currency symbol', 'tribe-extension' ); ?>"
					>
						<?php echo $symbol_list; ?>
					</select>
				</td>
			</tr>

		</table>

		<?php
		// allow disabling the Before/After choice on the CE form
		$enabled = (bool) apply_filters( 'tribe_ext_ce_cost_currency_symbol_position_enabled', true );
		if ( ! empty( $enabled ) ) {
			?>
			<table class="tribe-section-content">
				<colgroup>
					<col class="tribe-colgroup tribe-colgroup-label">
					<col class="tribe-colgroup tribe-colgroup-field">
				</colgroup>

				<tr class="tribe-section-content-row">
					<td class="tribe-section-content-label">
						<?php tribe_community_events_field_label( 'EventCurrencyPosition', __( 'Symbol Position:', 'tribe-extension' ) ); ?>
					</td>
					<td class="tribe-section-content-field">
						<select
							id="EventCurrencyPosition"
							aria-label="<?php esc_html_e( 'Symbol Position', 'tribe-extension' ); ?>"
							name="EventCurrencyPosition"
							class="event-cost-currency-symbol-position tribe-dropdown"
							placeholder="<?php esc_attr_e( 'Override the symbol position', 'tribe-extension' ); ?>"
						>
							<?php echo $symbol_position_list; ?>
						</select>
						<p>
							<?php _e( 'The position of the currency symbol in the cost string.', 'tribe-extension' ); ?>
							<br>
							<?php printf( __( 'Leave blank to use the default: %s', 'tribe-extension' ), $this->get_symbol_position_phrase( $this->get_symbol_position_default() ) ); ?>
						</p>

					</td>
				</tr>

			</table>
			<?php
		}

		return true;
	}

}