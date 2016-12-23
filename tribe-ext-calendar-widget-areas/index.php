<?php
/**
* Plugin Name: The Events Calendar Extension: Calendar Widget Areas
* Description: Adds widget areas (a.k.a. sidebars) that only display on The Events Calendar pages/views
* Version: 1.0
* Extension Class: Tribe__Extension__Calendar_Widget_Areas
* Author: Modern Tribe, Inc.
* Author URI: http://m.tri.be/1971
* License: GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Created 2015-10-05 for https://theeventscalendar.com/support/forums/topic/control-visibility-of-sidebar-widgets/#dl_post-1011743
// Turned into an Extension by Cliff on 2016-12-14

// Do not load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Do not load unless Tribe Common is fully loaded.
if ( ! class_exists( 'Tribe__Extension' ) ) {
	return;
}

// Do not load unless dynamic_sidebar exists -- since WP 2.2.0, per https://developer.wordpress.org/reference/functions/dynamic_sidebar/
if ( ! function_exists( 'dynamic_sidebar' ) ) {
	return;
}

/**
* Extension main class, class begins loading on init() function.
*/
class Tribe__Extension__Calendar_Widget_Areas extends Tribe__Extension {
	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 * 
	 * @access: public
	 */
	public function construct() {
		// Each plugin required by this extension
		$this->add_required_plugin( 'Tribe__Events__Main' );
				
		// Set the extension's TEC URL
		$this->set_url( 'https://theeventscalendar.com/extensions/calendar-widget-areas/' );
	}
	
	/**
	 * Extension initialization and hooks.
	 * 
	 * @access: public
	 */
	public function init() {
		// Register all widget areas
		add_action( 'widgets_init', array( $this, 'register_sidebars' ) );
		
		// Print "Before Calendar" Widget Area
		add_action( 'tribe_events_before_template', array( $this, 'before_template' ) );
		
		// Print "After Calendar" Widget Area
		add_action( 'tribe_events_after_template', array( $this, 'after_template' ) );
		
		// Print "Before Calendar and Event Single" Widget Area
		add_action( 'tribe_events_before_view', array( $this, 'before_view' ) );
		
		// Print "After Calendar and Event Single" Widget Area
		add_action( 'tribe_events_after_view', array( $this, 'after_view' ) );
	}
	
	/**
	 * Register all widget areas.
	 * 
	 * @access: public
	 */
	public function register_sidebars() {
		register_sidebar (
			array (
				'name'			=> __( 'TEC Above Calendar', 'tribe-extension' ),
				'id'			=> 'tec_ext_cal_widget_areas__before_template',
				'description'	=> __( 'Widgets in this area will be shown ABOVE The Events Calendar.', 'tribe-extension' ),
			)
		);
		
		register_sidebar (
			array (
				'name'			=> __( 'TEC Below Calendar', 'tribe-extension' ),
				'id'			=> 'tec_ext_cal_widget_areas__after_template',
				'description'	=> __( 'Widgets in this area will be shown BELOW The Events Calendar.', 'tribe-extension' ),
			)
		);
		
		register_sidebar (
			array (
				'name'			=> __( 'TEC Above Single Events', 'tribe-extension' ),
				'id'			=> 'tec_ext_cal_widget_areas_single__before_view',
				'description'	=> __( 'Widgets in this area will be shown ABOVE Event Single pages.', 'tribe-extension' ),
			)
		);
		
		register_sidebar (
			array (
				'name'			=> __( 'TEC Below Single Events', 'tribe-extension' ),
				'id'			=> 'tec_ext_cal_widget_areas_single__after_view',
				'description'	=> __( 'Widgets in this area will be shown BELOW Event Single pages.', 'tribe-extension' ),
			)
		);
	
	}
	
	
	/**
	 * "Before Calendar" Widget Area
	 * 
	 * @access: public
	 */
	public function before_template() {
		dynamic_sidebar( 'tec_ext_cal_widget_areas__before_template' );
	}
	
	
	/**
	 * "After Calendar" Widget Area
	 * 
	 * @access: public
	 */
	public function after_template() {
		dynamic_sidebar( 'tec_ext_cal_widget_areas__after_template' );
	}
	
	
	/**
	 * "Before Calendar and Event Single" Widget Area
	 * 
	 * @access: public
	 */
	public function before_view() {
		if ( ! tribe_is_event() ) {
			return false;
		}
		
		dynamic_sidebar( 'tec_ext_cal_widget_areas_single__before_view' );
	}
	
	
	/**
	 * "After Calendar and Event Single" Widget Area
	 * 
	 * @access: public
	 */
	public function after_view() {
		if ( ! tribe_is_event() ) {
			return false;
		}
		
		dynamic_sidebar( 'tec_ext_cal_widget_areas_single__after_view' );
	}
	
}