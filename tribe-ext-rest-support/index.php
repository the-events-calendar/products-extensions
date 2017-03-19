<?php
/**
 * Plugin Name:     The Events Calendar Extension: REST Support
 * Description:     Adds support for the REST API.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Rest_Support
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
class Tribe__Extension__Rest_Support extends Tribe__Extension {

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main' );

		// Have this extension activate immediately
		$this->set( 'init_hook', 'tribe_plugins_loaded' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		add_filter( 'tribe_events_register_event_type_args', array( $this, 'add_rest_arg' ) );
	}

	/**
	 * Adds rest support to the args array and returns it
	 *
	 * @see tribe_events_register_event_type_args
	 */
	public function add_rest_arg( $args ) {
		$args['show_in_rest'] = true;

		return $args;
	}
}
