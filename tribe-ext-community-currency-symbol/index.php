<?php
/**
 * Plugin Name:     The Events Calendar Extension: Community Events Cost Currency Symbol
 * Description:     Adds a currency symbol selector option to the Community Events form (must first be setup at wp-admin > Events > Settings > Community tab > Form Defaults section). Additionally, the event creator can override the currency symbol's position instead of using your site's default from wp-admin > Events > Settings > General tab.
 * Version:         1.0.1
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

		$this->set_url( 'https://theeventscalendar.com/extensions/community-events-cost-currency-symbol/' );
		$this->set_version( '1.0.1' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'add_settings' ) );
		add_action( 'tribe_events_community_section_before_cost', array( $this, 'display' ) );
	}

	/**
	 * Add options to Tribe settings page.
	 *
	 * @see Tribe__Extension__Settings_Helper
	 */
	public function add_settings() {
		if ( ! class_exists( 'Tribe__Extension__Settings_Helper' ) ) {
			require_once dirname( __FILE__ ) . '/src/Tribe/Settings_Helper.php';
		}

		// Setup fields on the settings page
		$setting_helper = new Tribe__Extension__Settings_Helper();

		$fields = array();

		$fields['tribe_ext_ce_cost_currency_title'] = array(
			'type' => 'html',
			'html' => '<h3>' . esc_html__( 'Event Cost Currency Extension', 'tribe-extension' ) . '</h3>',
		);

		// List of allowed symbols
		$fields['tribe_ext_ce_cost_currency_symbols'] = array(
			'type'            => 'text',
			'label'           => esc_html__( 'Allowed Symbols', 'tribe-extension' ),
			'tooltip'         => esc_html__( 'Pipe-separated list of allowed currency symbols. (Once this option is set and saved, you will be able to pick the Default Currency Symbol for new events.) The drop-down will display these in the order you enter them. Example 1: $|€|£|Fr|¥|CNY|C$|AU$. Example 2: USD|EUR|GBP|JPY|CNY|CAD|AUD', 'tribe-extension' ),
			'validation_type' => 'textarea',
		);

		// only allow modifying after the List exists (to be able to pick from)
		if ( tribe_get_option( 'tribe_ext_ce_cost_currency_symbols' ) ) {
			$fields['tribe_ext_ce_cost_currency_symbol_default'] = array(
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Default Symbol', 'tribe-extension' ),
				'tooltip'         => esc_html__( 'The default currency symbol for new events (does not apply when editing existing events). Leave blank to not set a default.', 'tribe-extension' ),
				'options'         => $this->symbol_list_array( true ),
				'validation_type' => 'options',
			);
		}

		// List of allowed symbols
		$fields['tribe_ext_ce_cost_currency_symbol_position_disabled'] = array(
			'type'            => 'checkbox_bool',
			'label'           => esc_html__( 'Disallow Position Selector', 'tribe-extension' ),
			'tooltip'         => esc_html__( 'If checked, event creators will not be able to change the currency position (before or after the cost) and your site default from the General settings tab will be used.', 'tribe-extension' ),
			'default'         => false,
			'validation_type' => 'boolean',
		);

		$setting_helper->add_fields(
			$fields,
			'community',
			'single_geography_mode' // At the bottom of the "Form Defaults" section
		);
	}

	/**
	 * Get list of allowed symbols as an array.
	 *
	 * @param bool $prepend_empty
	 *
	 * @return array
	 */
	public function symbol_list_array( $prepend_empty = false ) {
		$setting = tribe_get_option( 'tribe_ext_ce_cost_currency_symbols' );

		if ( '' === (string) $setting ) {
			return array();
		}

		$settings = explode( '|', $setting );

		/**
		 * Filters the array of currency symbols passed to the drop-down.
		 *
		 * @param array $settings The original array.
		 */
		$settings = (array) apply_filters( 'tribe_ext_ce_cost_currency_symbol_list_array', $settings );

		$symbols = array();

		foreach ( $settings as $key => $value ) {
			$value = esc_attr( sanitize_text_field( $value ) );
			if ( '' !== $value ) {
				$symbols[ $value ] = $value;
			}
		}

		$prepend_empty = (bool) $prepend_empty;

		if ( ! empty( $prepend_empty ) ) {
			$symbols = array( '' => '' ) + $symbols;
		}

		return $symbols;
	}

	/**
	 * Get the set currency symbol.
	 *
	 * @return string|void
	 */
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

	/**
	 * Get the set currency symbol position.
	 *
	 * @return string|void
	 */
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

	/**
	 * Get the default currency position
	 *
	 * @return string
	 */
	protected function get_symbol_position_default() {
		$default_position = tribe_get_option( 'reverseCurrencyPosition' ); // boolean
		if ( ! empty( $default_position ) ) {
			$default_position = 'suffix';
		} else {
			$default_position = 'prefix';
		}

		/**
		 * Filters the default currency position setting.
		 *
		 * @param string $default_position The default from the settings.
		 */
		$default_position = apply_filters( 'tribe_ext_ce_cost_currency_symbol_position_default', $default_position );

		return sanitize_text_field( $default_position );
	}

	/**
	 * Get the symbol position phrase
	 *
	 * @param string $position
	 *
	 * @return string
	 */
	protected function get_symbol_position_phrase( $position = 'prefix' ) {
		if ( 'prefix' === $position ) {
			$output = esc_attr_x( 'Before Cost', 'Currency symbol position', 'tribe-extension' );
		} else {
			$output = esc_attr_x( 'After Cost', 'Currency symbol position', 'tribe-extension' );
		}

		return $output;
	}

	/**
	 * Build the list of select options for the allowed currency symbols.
	 *
	 * @return string
	 */
	protected function symbol_list_select_options() {
		$symbol_list_array = $this->symbol_list_array();

		if ( empty( $symbol_list_array ) ) {
			return '';
		}

		$current_symbol = (string) $this->get_current_symbol();

		$post_id = Tribe__Main::post_id_helper();

		/**
		 * If currency symbol is not set and we verify we are on the Add New
		 * Event version of the Community Events form, then set the currency
		 * symbol to the default setting. This avoids setting a purposefully-blank
		 * currency symbol to the default symbol when on the Edit Event version of
		 * the Community Events form.
		 */
		if (
			'' === $current_symbol
			&& $this->is_community_page( 'add-event' )
		) {
			$current_symbol = tribe_get_option( 'tribe_ext_ce_cost_currency_symbol_default' );
		}

		$output = PHP_EOL . '<option value=""></option>' . PHP_EOL;

		foreach ( $symbol_list_array as $key => $value ) {
			$selected = selected( $value, $current_symbol, false );

			$opt_text = esc_html_x( $value, 'Currency symbol', 'tribe-extension' );

			$output .= sprintf( '<option value="%s" %s>%s</option>%s', esc_attr( $value ), esc_html( $selected ), $opt_text, PHP_EOL );
		}

		return $output;
	}

	/**
	 * Build the list of select options for the symbol position.
	 *
	 * @return string
	 */
	protected function symbol_position_list_select_options() {
		$default_position = $this->get_symbol_position_default();

		if ( 'prefix' === $default_position ) {
			$opposite = 'suffix';
		} else {
			$opposite = 'prefix';
		}

		$symbol_position_list_array[ $opposite ] = $this->get_symbol_position_phrase( $opposite );

		$current_position = (string) $this->get_current_symbol_position();

		$post_id = Tribe__Main::post_id_helper();

		// Awaiting https://central.tri.be/issues/80685 instead of the empty() check here
		if ( '' === $current_position && empty( $post_id ) ) {
			$current_position = $default_position;
		}

		$output = sprintf( '%1$s<option value=""></option>%1$s', PHP_EOL );

		foreach ( $symbol_position_list_array as $key => $value ) {
			$selected = selected( $key, $current_position, false );

			$opt_text = esc_attr_x( $value, 'Currency symbol position', 'tribe-extension' );

			$output .= sprintf( '<option value="%s" %s>%s</option>%s', esc_attr( $key ), esc_html( $selected ), $opt_text, PHP_EOL );
		}

		return $output;
	}

	/**
	 * Detect which mode of the Community Events form we are currently in.
	 *
	 * @param null $page
	 *
	 * @return bool
	 */
	protected function is_community_page( $page = null ) {
		$main = tribe( 'community.main' );

		$event_post_type = Tribe__Events__Main::POSTTYPE;
		$action          = Tribe__Utils__Array::get( $main->context, 'action' );
		$ce_post_type    = Tribe__Utils__Array::get( $main->context, 'post_type', $event_post_type ); // assume event post type if not set

		// bail if we are not doing what is expected from the start
		if ( $event_post_type !== $ce_post_type ) {
			return false;
		}

		if (
			'edit-event' === $page
			&& 'edit' === $action
		) {
			$is_page = true;
		} elseif (
			'add-event' === $page
			&& (
				'add' === $action
				|| empty( $action )
			)
		) {
			$is_page = true;
		} else {
			$is_page = false;
		}

		return $is_page;
	}

	/**
	 * Do the output
	 *
	 * @return string
	 */
	public function display() {
		$symbol_list = $this->symbol_list_select_options();

		// if no symbols are entered in Settings, do not display Symbol or Position on CE form
		if ( empty( $symbol_list ) ) {
			return '';
		}

		?>

		<table class="tribe-section-content">
			<colgroup>
				<col class="tribe-colgroup tribe-colgroup-label">
				<col class="tribe-colgroup tribe-colgroup-field">
			</colgroup>

			<tr class="tribe-section-content-row">
				<td class="tribe-section-content-label">
					<?php tribe_community_events_field_label( 'EventCurrencySymbol', esc_html__( 'Currency Symbol:', 'tribe-extension' ) ); ?>
				</td>
				<td class="tribe-section-content-field">
					<select
						id="EventCurrencySymbol"
						aria-label="<?php esc_attr_e( 'Currency Symbol', 'tribe-extension' ); ?>"
						name="EventCurrencySymbol"
						class="event-cost-currency-symbol tribe-dropdown"
						placeholder="<?php esc_attr_e( 'Select a currency symbol', 'tribe-extension' ); ?>"
					>
						<?php echo $symbol_list; // Escaped upstream ?>
					</select>
				</td>
			</tr>

		</table>

		<?php
		// allow disabling the Before/After choice on the CE form
		$disabled = tribe_get_option( 'tribe_ext_ce_cost_currency_symbol_position_disabled' );

		if ( empty( $disabled ) ) :
			?>
			<table class="tribe-section-content">
				<colgroup>
					<col class="tribe-colgroup tribe-colgroup-label">
					<col class="tribe-colgroup tribe-colgroup-field">
				</colgroup>

				<tr class="tribe-section-content-row">
					<td class="tribe-section-content-label">
						<?php tribe_community_events_field_label( 'EventCurrencyPosition', esc_html__( 'Symbol Position:', 'tribe-extension' ) ); ?>
					</td>
					<td class="tribe-section-content-field">
						<select
							id="EventCurrencyPosition"
							aria-label="<?php esc_attr_e( 'Symbol Position', 'tribe-extension' ); ?>"
							name="EventCurrencyPosition"
							class="event-cost-currency-symbol-position tribe-dropdown"
							placeholder="<?php esc_attr_e( 'Override the symbol position', 'tribe-extension' ); ?>"
						>
							<?php echo $this->symbol_position_list_select_options(); ?>
						</select>
						<p>
							<?php esc_html_e( 'The position of the currency symbol in the cost string.', 'tribe-extension' ); ?>
							<br>
							<?php printf( esc_html__( 'Leave blank to use the default: %s', 'tribe-extension' ), $this->get_symbol_position_phrase( $this->get_symbol_position_default() ) ); ?>
						</p>

					</td>
				</tr>

			</table>
		<?php
		endif;
	}

}