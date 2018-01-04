<?php
/**
 * Plugin Name:     Event Tickets Extension: Select Ticket Providers
 * Description:     Adds some checkboxes to WP Admin > Events > Settings > Tickets for choosing which Ticket Providers you want to use.
 * Version:         1.0.1
 * Extension Class: Tribe__Extension__Select_Ticket_Providers
 * Author:          Modern Tribe, Inc.
 * Author URI:      http://m.tri.be/1971
 * License:         GPLv2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 */

// Do not load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Do not load unless Tribe Common is fully loaded.
if ( ! class_exists( 'Tribe__Extension' ) ) {
	return;
}

/**
 * Extension main class, class begins loading on init() function.
 */
class Tribe__Extension__Select_Ticket_Providers extends Tribe__Extension {

	/**
	 * Contains the full list of providers from before we removed any
	 *
	 * @var array
	 */
	protected $default_providers;

	/**
	 * Option key for which providers are enabled
	 *
	 * @var string
	 */
	protected $option_key_enabled_providers = 'tickets_enabled_providers';

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Tickets__Main' );
		$this->add_required_plugin( 'Tribe__Tickets_Plus__Main' );

		$this->set_url( 'https://theeventscalendar.com/extensions/select-which-ticket-types-display-ticket-editor/' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		add_action( 'init', array( $this, 'remove_providers' ), 100 );
		add_action( 'admin_init', array( $this, 'add_settings' ) );
	}

	/**
	 * Add settings to tribe settings page
	 */
	public function add_settings() {
		if ( ! class_exists( 'Tribe__Extension__Settings_Helper' ) ) {
			require_once dirname( __FILE__ ) . '/src/Tribe/Settings_Helper.php';
		}

		// Setup fields on the settings page
		$setting_helper = new Tribe__Extension__Settings_Helper();
		$providers = $this->get_default_providers();

		$setting_helper->add_field(
			$this->option_key_enabled_providers,
			array(
				'type'            => 'checkbox_list',
				'label'           => esc_html__( 'Ticket Providers', 'tribe-extension' ),
				'tooltip'         => esc_html__( 'Select which ticket providers you want available on your site.', 'tribe-extension' ),
				'default'         => array_keys( $providers ),
				'validation_type' => 'options_multi',
				'options'         => $providers,
			),
			'event-tickets',
			'ticket-enabled-post-types',
			false
		);
	}

	/**
	 * Gets defaults providers
	 *
	 * This should be called prior to altering the list any.
	 */
	protected function get_default_providers() {
		if ( ! isset( $this->default_providers ) ) {
			$this->default_providers = Tribe__Tickets__Tickets::modules();
		}

		return $this->default_providers;
	}

	/**
	 * Removes ticket providers specified in the options
	 */
	public function remove_providers() {
		// The meat of this extension is a hack, that requires PHP 5.3+.
		// @todo Once we get a proper filter for the ticket providers we can remove the hack.
		if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
			_doing_it_wrong( $this->get_name(), 'Requires PHP 5.3 or newer.', 'N/A' );
			return;
		}

		$current_providers = $this->get_default_providers();
		$enabled_providers = tribe_get_option( $this->option_key_enabled_providers, $current_providers );

		$reflection = new ReflectionClass( 'Tribe__Tickets__Tickets' );
		$reflect_prop = $reflection->getProperty( 'active_modules' );
		$reflect_prop->setAccessible( true );

		// Remove disabled providers from $current_providers.
		foreach ( $current_providers as $class => $description ) {
			if ( ! in_array( $class, $enabled_providers ) ) {
				unset( $current_providers[ $class ] );
			}
		}

		$reflect_prop->setValue( $current_providers );
	}
}
