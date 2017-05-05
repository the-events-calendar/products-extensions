<?php
/**
 * Plugin Name:     The Events Calendar Extension: Elegant Themes Posts Per Page Fix
 * Description:     Fixes the pagination override from Divi or other themes by Elegant Themes. The Event Calendar's "Number of events to show per page" setting will be used where applicable, as expected.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Fix_Elegant_Themes_Posts_Per_Page
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
class Tribe__Extension__Fix_Elegant_Themes_Posts_Per_Page extends Tribe__Extension {

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main' );
		$this->set_url( 'https://theeventscalendar.com/extensions/elegant-themes-posts-per-page-fix/' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		add_filter( 'parse_query', array( $this, 'remove_et_custom_posts_per_page' ), 100 );
	}

	/**
	 * Remove Elegant Themes' custom posts per page.
	 *
	 * @see et_custom_posts_per_page()
	 *
	 * @param WP_Query $query
	 */
	public function remove_et_custom_posts_per_page( $query ) {
		if ( $query->tribe_is_event_query ) {
			remove_action( 'pre_get_posts', 'et_custom_posts_per_page' );
		}
	}

}