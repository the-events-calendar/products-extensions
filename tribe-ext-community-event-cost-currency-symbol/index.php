<?php
/**
 * Plugin Name:     The Events Calendar: Community Events Extension: Add Event Cost Currency Symbol
 * Description:     Adds a currency symbol selector option to the Community Event's form. Settings are at wp-admin > Events > Settings > Community tab.
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
		$setting_helper = new Tribe__Settings_Helper();

		// List of allowed symbols
		$fields['tribe_ext_ce_cost_currency_symbols'] = array(
			'type'            => 'text',
			'label'           => esc_html__( 'Event Cost: Allowed Currency Symbols', 'the-events-calendar' ),
			'tooltip'         => esc_html__( 'Comma-separated list of allowed currency symbols. The drop-down will display these options in the order you enter them. Example 1: $,€,£,Fr,¥,CNY,C$,AU$. Example 2: USD,EUR,GBP,JPY,CNY,CAD,AUD', 'tribe-extension' ),
			'default'         => '$,€,£',
			'validation_type' => 'textarea',
		);

		// only allow setting Default if List is created
		if ( tribe_get_option( 'tribe_ext_ce_cost_currency_symbols' ) ) {
			$fields['tribe_ext_ce_cost_currency_symbol_default'] = array(
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Event Cost: Default Currency Symbol', 'the-events-calendar' ),
				'tooltip'         => esc_html__( 'The default currency symbol for new events and when editing existing events that do not yet have a set currency symbol. Note: You must first enter the Allowed Currency Symbols before setting this option.', 'tribe-extension' ),
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

	/**
	 * TODO
	 *
	 * @return array
	 */
	public function symbol_list_array( $prepend_empty = false ) {
		$setting = tribe_get_option( 'tribe_ext_ce_cost_currency_symbols' );

		if ( '' === (string) $setting ) {
			return array();
		}

		$settings = explode( ',', $setting );

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

	protected function symbol_list_select_options() {
		$symbol_list_array = $this->symbol_list_array();

		if ( empty( $symbol_list_array ) ) {
			return '';
		}

		$current_symbol = (string) $this->get_current_symbol();

		if ( '' === $current_symbol ) {
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

	protected function get_current_symbol() {
		$post_id = Tribe__Main::post_id_helper( get_the_ID() );

		// first check $_POST, else check post meta
		if ( isset( $_POST['EventCurrencySymbol'] ) ) {
			$currency_symbol = $_POST['EventCurrencySymbol'];
		} else {
			$currency_symbol = tribe_get_event_meta( $post_id, '_EventCurrencySymbol', true );
		}

		return esc_attr( $currency_symbol );
	}


	public function display() {
		$symbol_list = $this->symbol_list_select_options();

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
					<?php tribe_community_events_field_label( 'EventCurrencySymbol', __( 'Currency Symbol:', 'tribe-events-community' ) ); ?>
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

	}

}