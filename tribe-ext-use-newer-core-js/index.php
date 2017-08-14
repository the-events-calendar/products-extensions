<?php
/**
 * Plugin Name:     The Events Calendar Extension: Use Newer Core JS
 * Description:     Loads more current versions of core scripts like jQuery and Underscore when viewing various admin pages of The Events Calendar and Event Tickets.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Use_Newer_Core_JS
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
class Tribe__Extension__Use_Newer_Core_JS extends Tribe__Extension {


	/**
	 * Setup the Extension's properties. This always executes even if the required plugins are not present.
	 *
	 * @since 1.0.0
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main' );
		$this->set_url( 'https://theeventscalendar.com/extensions/use-newer-core-js/' );
		$this->set_version( '1.0.0' );
	}

	/**
	 * Extension initialization and hooks.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'usurp_core_scripts' ) );
	}

	/**
	 * Break a cardinal rule of WordPress and replace WP Core versions of scripts with our own.
	 *
	 * @since 1.0.0
	 */
	public function usurp_core_scripts() {
		if ( ! class_exists( 'Tribe__Admin__Helpers' ) ) {
			return;
		}

		$helpers = Tribe__Admin__Helpers::instance();

		if ( ! $helpers->is_screen() && ! $helpers->is_post_type_screen() ) {
			return;
		}

		$plugin_dir_url = plugin_dir_url( __FILE__);

		wp_deregister_script( 'jquery' );
		wp_deregister_script( 'underscore' );

		wp_enqueue_script( 'underscore', $plugin_dir_url . 'vendor/underscore/underscore-1.8.3.min.js', array(), '1.8.3', false );
		wp_enqueue_script( 'jquery', $plugin_dir_url . 'vendor/jquery/jquery-1.12.4.min.js', array( 'underscore' ), '1.12.4', false );
	}
}