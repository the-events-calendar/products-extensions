<?php
/**
 * Plugin Name:     The Events Calendar Extension: Link Events Back to Facebook
 * Description:     An extension that makes event titles link to the events' Website URLs when present.
 * Version:         1.0.1
 * Extension Class: Tribe__Extension_Link_Events_Back_to_FB
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

class Tribe__Extension_Link_Events_Back_to_FB extends Tribe__Extension {

	/**
	 * Setup the Extension's properties.
	 *
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main' );
		$this->set_url( 'https://theeventscalendar.com/extensions/add-the-source-link-of-an-event-imported-from-facebook/' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		add_action( 'tribe_events_single_event_before_the_content', array( $this, 'add_link_to_fb_event' ) );
	}

	/**
	 * Add a link to the Facebook event at the start of event content.
	 */
	public function add_link_to_fb_event() {
		$fbid = tribe_get_event_meta( get_the_ID(), '_FacebookID' );

		// Only proceed if this event is indeed imported from Facebook.
		if ( empty( $fbid ) ) {
			return;
		}

		printf(
			'<p><a href="http://facebook.com/events/%2%s" target="_blank" rel="nofollow">%1$s</a></p>',
			esc_html__( 'See this event on Facebook', 'tribe-extension' ),
			$fbid
		);
	}
}