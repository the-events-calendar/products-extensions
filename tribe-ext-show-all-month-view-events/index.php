<?php
/**
 * Plugin Name:     The Events Calendar Extension: Show All Month View Events
 * Description:     An extension that, when activated, will not limit the number of events shown in each day in the Month View.
 * Version:         1.0.1
 * Extension Class: Tribe__Extension__Show_All_Month_View_Events
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

class Tribe__Extension__Show_All_Month_View_Events extends Tribe__Extension {

	/**
	 * Setup the Extension's properties.
	 *
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main', '4.3.3' );
		$this->set_url( 'https://theeventscalendar.com/extensions/display-all-events-on-a-day-in-month-view/' );
		$this->set_version( '1.0.1' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		add_filter( 'tribe_events_month_day_limit', array( $this, 'remove_limit' ) );
	}

	/**
	 * Remove the limit.
	 */
	public function remove_limit() {
		return -1;
	}
}