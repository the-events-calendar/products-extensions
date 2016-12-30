<?php
/**
 * Plugin Name:     The Events Calendar Extension: Hide Others Linked Posts
 * Description:     In Community Events this will hide the linked posts such as "Organizers" and "Venues" that were not created by the current user.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Hide_Linked_Posts
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
class Tribe__Extension__Hide_Linked_Posts extends Tribe__Extension {

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main' );
		$this->add_required_plugin( 'Tribe__Events__Community__Main', '4.2' );

		$this->set_url( 'https://theeventscalendar.com/extensions/hide-others-linked-posts/' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		add_filter( 'tribe_events_linked_posts_query', array( $this, 'hide_linked_posts' ), 10, 2 );
	}

	public function hide_linked_posts( $output, $args ) {

		if ( ! is_admin() && ! isset( $args['author'] ) ) {
			$output = array();
		}

		return $output;
	}
}