<?php
/**
 * Plugin Name:     The Events Calendar PRO Extension: Displays multiple events with the same venue in Google Maps infoWindow.
 * Description:     Show multiple events with the same venue in Google Maps info window.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Multiple_Events_Same_Venue
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
class Tribe__Extension__Multiple_Events_Same_Venue extends Tribe__Extension {

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main' );
		$this->add_required_plugin( 'Tribe__Events__Pro__Main' );
		$this->set_url( 'https://theeventscalendar.com/extensions/google-maps-multiple-events-same-venue/' );
	}

	public function init() {
		add_action( 'wp_print_scripts', array( $this, 'dequeue_tribe_events_pro_geoloc' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_tribe_new_pro_geoloc' ) );
	}

	public function dequeue_tribe_events_pro_geoloc() {
		wp_dequeue_script( 'tribe-events-pro-geoloc' );
	}

	public function enqueue_tribe_new_pro_geoloc() {
		$url  = apply_filters( 'tribe_events_pro_google_maps_api', 'https://maps.google.com/maps/api/js' );
		$path = plugin_dir_url( __FILE__ ) . '/src/tribe-events-ajax-maps.min.js';
		wp_register_script( 'tribe-events-new-geoloc', $path, array(
				'tribe-gmaps',
				Tribe__Events__Template_Factory::get_placeholder_handle(),
		), apply_filters( 'tribe_events_pro_js_version', Tribe__Events__Pro__Main::VERSION ) );
		wp_enqueue_script( 'tribe-events-new-geoloc' );

		$http = is_ssl() ? 'https' : 'http';
		$geoloc = Tribe__Events__Pro__Geo_Loc::instance();
		$data   = array(
			'ajaxurl'  => admin_url( 'admin-ajax.php', $http ),
			'nonce'    => wp_create_nonce( 'tribe_geosearch' ),
			'map_view' => 'map' == Tribe__Events__Main::instance()->displaying ? true : false,
			'pin_url'  => Tribe__Customizer::instance()->get_option( array( 'global_elements', 'map_pin' ), false ),
		);

		wp_localize_script( 'tribe-events-new-geoloc', 'GeoLoc', $data );
	}
}
