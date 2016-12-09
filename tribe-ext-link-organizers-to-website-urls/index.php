<?php
/**
 * Plugin Name:     The Events Calendar Extension: Link Organizers to Website URLs
 * Description:     An extension that makes orgnizer names link to the orgnizers' Website URLs when present.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Link_Orgnizers_to_Website_URLs
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

class Tribe__Extension__Link_Orgnizers_to_Website_URLs extends Tribe__Extension {

	/**
	 * Setup the Extension's properties.
	 *
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main', '4.3.3' );
		$this->set_url( 'https://theeventscalendar.com/extensions/make-organizer-names-link-to-organizer-website-urls/' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		add_filter( 'tribe_get_organizer_link', array( $this, 'tribe_get_organizer_link' ), 10, 4 );
	}

	/**
	 * Make organizer names link straight to the organizer website URLs instead of to single organizer pages.
	 *
	 * @param string $link
	 * @param int $post_id
	 * @param string $full_link
	 * @param string $url
	 * @return string
	 */
	public function tribe_get_organizer_link( $link, $post_id, $full_link, $url ) {
	    
		$website_url = tribe_get_organizer_website_url( $post_id );
		
		if ( ! is_string( $website_url ) || empty( $website_url ) ) {
		    return $link;
		}

		return sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $website_url ), tribe_get_organizer( $post_id ) );
	}
}