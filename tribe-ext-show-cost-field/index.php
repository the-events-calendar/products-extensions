<?php
/**
 * Plugin Name:     Event Tickets Plus Extension: Show Cost Fields
 * Description:     Event Tickets Plus hides the basic cost fields when activated, and automatically overrides the info with the costs of your tickets. When this extension is activated alongside Event Tickets Plus it will restore the hidden fields, allowing you to manually set the data.
 * Version:         1.0.1
 * Extension Class: Tribe__Extension__Show_Cost_Field
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
class Tribe__Extension__Show_Cost_Field extends Tribe__Extension {

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		if ( ! class_exists( 'Tribe__Events__Tickets__Eventbrite__Main' ) ) {
			$this->add_required_plugin( 'Tribe__Events__Main' );
			$this->add_required_plugin( 'Tribe__Tickets__Main' );
			$this->add_required_plugin( 'Tribe__Tickets_Plus__Main' );
		} else {
			$this->add_required_plugin( 'Tribe__Events__Main' );
			$this->add_required_plugin( 'Tribe__Events__Tickets__Eventbrite__Main' );
		}
		$this->set_url( 'https://theeventscalendar.com/extensions/show-cost-fields/' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		add_filter( 'tribe_events_admin_show_cost_field', '__return_true', 100 );
	}
}
