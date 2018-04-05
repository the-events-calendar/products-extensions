<?php
/**
 * Plugin Name:     The Events Calendar Extension: Remove Export Links
 * Description:     Removes the Export Links from Event Views
 * Version:         1.0.0
 * Extension Class: Tribe__Events__Remove_Export_Links
 * Author:          Modern Tribe, Inc.
 * Author URI:      http://m.tri.be/1971
 * License:         GPLv2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Do not load directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
/**
 * Do not load unless Tribe Common is fully loaded.
 */
if ( ! class_exists( 'Tribe__Extension' ) ) {
	return;
}
/**
 * Extension main class, class begins loading on init() function.
 */
class Tribe__Events__Remove_Export_Links extends Tribe__Extension {
	/**
	 * Setup the Extension's properties.
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main' );
		$this->set_url( 'https://theeventscalendar.com/knowledgebase/removing-export-links-event-views/' );
	}
	/**
	 * Initialization and hooks.
	 */
	public function init() {
		add_action( 'init', array( $this, 'single_event_links' ) );
		add_action( 'init', array( $this, 'view_links' ) );
	}
	/**
	 * Removes the markup for iCal and gCal single event links.
	 */
	public function single_event_links() {
		remove_action( 'tribe_events_single_event_after_the_content', array(
				$this->ical_provider(),
				'single_event_links',
			) );
	}
	/**
	 * Removes the markup for the "iCal Import" link for the views.
	 */
	public function view_links() {
		remove_filter( 'tribe_events_after_footer', array( $this->ical_provider(), 'maybe_add_link' ) );
	}
	/**
	 * Makes the extension compatible with older versions of The Events Calendar.
	 */
	protected function ical_provider() {
		return function_exists( 'tribe' ) ? tribe( 'tec.iCal' ) // Current
			: 'Tribe__Events__iCal'; // Legacy
	}
}