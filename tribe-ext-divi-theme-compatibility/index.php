<?php
/**
 * Plugin Name:     The Events Calendar Extension: Divi Theme Compatibility
 * Description:     Makes The Events Calendar compatible with Elegant Themes' Divi theme and Divi-based themes (e.g. Extra theme). The posts_per_page / pagination fix should also work for all their themes, even if not Divi-based.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Divi_Theme_Compatibility
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
class Tribe__Extension__Divi_Theme_Compatibility extends Tribe__Extension {

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main' );
		$this->set_url( 'https://theeventscalendar.com/extensions/elegant-themes-divi-theme-compatibility/' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		if ( ! is_admin() ) {
			add_filter( 'parse_query', array( $this, 'remove_et_custom_posts_per_page' ), 100 );
		}
	}

	/**
	 * Remove Elegant Themes' custom posts per page.
	 *
	 * Applies to ALL themes by Elegant Themes, not just Divi and Divi-based themes
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