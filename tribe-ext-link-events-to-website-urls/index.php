<?php
/**
 * Plugin Name:     The Events Calendar Extension: Link Events to Website URLs
 * Description:     An extension that makes event titles link to the events' Website URLs when present.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Link_Events_to_Website_URLs
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

class Tribe__Extension__Link_Events_to_Website_URLs extends Tribe__Extension {

	/**
	 * Setup the Extension's properties.
	 *
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main', '4.3.3' );
		$this->set_url( 'https://theeventscalendar.com/extensions/make-event-titles-link-to-the-event-website-url/' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		add_filter( 'tribe_get_event_link', array( $this, 'tribe_get_event_link' ), 100, 2 );
	}

	/**
	 * Make event titles link to URLs from their respective "Event Website" fields.
	 *
	 * @param string $link
	 * @param int $post_id
	 * @return string
	 */
	public function tribe_get_event_link( $link, $post_id ) {

		$website_url = tribe_get_event_website_url( $post_id );
		
		if ( ! empty( $website_url ) ) {
			$link = $website_url;
		}
		
		return $link;
	}
}
