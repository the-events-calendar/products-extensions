<?php
/**
 * Plugin Name:     The Events Calendar Extension: Custom Label for Event URLs
 * Description:     An extension that makes it easy to use a custom label for Event Website URLs, instead of the default which is just the literal URL.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Custom_Label_Event_URLs
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

class Tribe__Extension__Custom_Label_Event_URLs extends Tribe__Extension {

	/**
	 * Setup the Extension's properties.
	 *
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main', '4.3.3' );
		$this->set_url( 'https://theeventscalendar.com/extensions/use-a-custom-label-for-the-event-website-url-field/' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		add_filter( 'tribe_get_event_website_link_label', array( $this, 'tribe_get_event_website_link_label' ), 10, 2 );
	}

	/**
	 * Change the text here to change the label for Event Website URLs.
	 * 
	 * @param $label string The default label.
	 * @return string
	 */
	public function tribe_get_event_website_link_label( $label ) {
		return 'View Website →';
	}
}