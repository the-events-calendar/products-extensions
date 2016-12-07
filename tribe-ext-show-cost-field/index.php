<?php
/**
 * Plugin Name:     Event Tickets Extension: Additional Settings
 * Description:     Adds a few extra settings to the general settings tab that are missing when The Events Calendar is not active.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Ticket_Currency_Settings
 * Author:          Modern Tribe, Inc.
 * Author URI:      http://m.tri.be/1971
 * License:         GPLv2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 */

// Do not load directly.
if ( ! defined( 'ABSPATH' ) ) { die( '-1' ); }
// Do not load unless Tribe Common is fully loaded.
if ( ! class_exists( 'Tribe__Extension' ) ) { return; }

/**
 * Extension main class, class begins loading on init() function.
 */
class Tribe__Extension__Ticket_Currency_Settings extends Tribe__Extension {

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Tickets__Main' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		// Settings area.
		require_once dirname( __FILE__ ) . '/src/Tribe/Settings_Helper.php';
		add_action( 'admin_init', array( $this, 'add_settings' ) );
	}

	/**
	 * Adds settings options
	 */
	public function add_settings() {
		$setting_helper = new Tribe__Settings_Helper();

		$fields = array(
			'defaultCurrencySymbol'         => array(
				'type'            => 'text',
				'label'           => esc_html__( 'Default currency symbol', 'the-events-calendar' ),
				'tooltip'         => esc_html__( 'Set the default currency symbol for event costs. Note that this only impacts future events, and changes made will not apply retroactively.', 'the-events-calendar' ),
				'validation_type' => 'textarea',
				'size'            => 'small',
				'default'         => '$',
			),
			'reverseCurrencyPosition'       => array(
				'type'            => 'checkbox_bool',
				'label'           => esc_html__( 'Currency symbol follows value', 'the-events-calendar' ),
				'tooltip'         => esc_html__( 'The currency symbol normally precedes the value. Enabling this option positions the symbol after the value.', 'the-events-calendar' ),
				'default'         => false,
				'validation_type' => 'boolean',
			),
		);

		$setting_helper->add_fields(
			$fields,
			'general'
		);
	}
}