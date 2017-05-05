<?php
/**
 * Plugin Name:     The Events Calendar Extension: Divi Theme Integration
 * Description:     Integration for The Events Calendar and the Divi theme by Elegant Themes.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Fix_Divi_Integration
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
class Tribe__Extension__Fix_Divi_Integration extends Tribe__Extension {

    /**
     * Setup the Extension's properties.
     *
     * This always executes even if the required plugins are not present.
     */
    public function construct() {
        $this->add_required_plugin( 'Tribe__Events__Main', '4.3' );
        $this->set_url( 'https://theeventscalendar.com/extensions/divi-theme-integration/' );
    }

    /**
     * Extension initialization and hooks.
     */
    public function init() {
        add_filter( 'parse_query', array( $this, 'remove_divi_pre_get_posts' ), 100 );
    }
    public function remove_divi_pre_get_posts( $query ) {
        if ( $query->tribe_is_event_query ) {
            remove_action( 'pre_get_posts', 'et_custom_posts_per_page' );
        }
    }    
}