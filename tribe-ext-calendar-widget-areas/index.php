<?php
/**
* Plugin Name: The Events Calendar Extension: Calendar Widget Areas
* Description: Adds widget areas (a.k.a. sidebars) that only display on The Events Calendar pages/views. Areas may be enabled or disabled at wp-admin > Events > Settings > Display tab > Advanced Template Settings section. Note that the WP Customizer only allows you to manage widget areas that apply to the page you're currently previewing; therefore, you will need to navigate to your Events page, for example, to edit the content of those widget areas via the Customizer's live preview.
* Version: 1.0
* Extension Class: Tribe__Extension__Calendar_Widget_Areas
* Author: Modern Tribe, Inc.
* Author URI: http://m.tri.be/1971
* License: GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Created 2015-10-05 for https://theeventscalendar.com/support/forums/topic/control-visibility-of-sidebar-widgets/#dl_post-1011743
// Turned into an Extension by Cliff on 2017-01-11

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
class Tribe__Extension__Calendar_Widget_Areas extends Tribe__Extension {
	/**
	 * Option key for which widget areas are enabled
	 *
	 * @var string
	 */
	protected $option_key_enabled_areas = 'tribe_ext_enabled_widget_areas';

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 *
	 * @access protected
	 */
	protected function construct() {
		// Each plugin required by this extension
		$this->add_required_plugin( 'Tribe__Events__Main' );
				
		// Set the extension's TEC URL
		$this->set_url( 'https://theeventscalendar.com/extensions/calendar-widget-areas/' );
	}
	
	/**
	 * All available widget areas this extension supports and details about each in a multidimensional associative array.
	 *
	 * Follow this format to enable a new widget area for each action hook you want
	 * to use. You will also need to add an additional method toward the end of this
	 * file for each area added so we know what code to execute.
	 * 
	 * @return array
	 */
	public function get_all_areas() {
		// format is:
		// action_hook => array of details what to do if we use this hook
		// 'method' value may lead with context, such as "single_" and the actual function named accordingly and having the necessary logic in place
		// actual function names are "tec_ext_widget_areas__" + the 'method' name from here
		// optional 'hook_type' => 'action' or 'filter' to tell init() add_action or add_filter -- will default to add_action
		$areas = array(
			'tribe_events_before_template'	=>	array(
				'method'  	=> 'before_template',
				'name'		=> __( 'TEC Above Calendar', 'tribe-extension' ),
				'desc'		=> __( 'Widgets in this area will be shown ABOVE The Events Calendar.', 'tribe-extension' ),
			),				
			'tribe_events_after_template'	=>	array(
				'method'  	=> 'after_template',
				'name'		=> __( 'TEC Below Calendar', 'tribe-extension' ),
				'desc'		=> __( 'Widgets in this area will be shown BELOW The Events Calendar.', 'tribe-extension' ),
			),				
			'tribe_events_before_view'	=>	array(
				'method'  	=> 'single_before_view',
				'name'		=> __( 'TEC Above Single Events', 'tribe-extension' ),
				'desc'		=> __( 'Widgets in this area will be shown ABOVE Single Events.', 'tribe-extension' ),
			),				
			'tribe_events_after_view'	=>	array(
				'method'  	=> 'single_after_view',
				'name'		=> __( 'TEC Below Single Events', 'tribe-extension' ),
				'desc'		=> __( 'Widgets in this area will be shown BELOW Single Events.', 'tribe-extension' ),
			),
		);
		
		return apply_filters( 'tribe_ext_calendar_widget_areas', $areas );
	}
	
	/**
	 * Build options to present to user
	 * 
	 * @return array
	 */
	public function get_available_area_options() {
		$options = array();
		
		foreach ( $this->get_all_areas() as $key => $value ) {
			$options[$key] = $value['name'];
		}
		
		return $options;
	}
	
	/**
	 * The chosen widget areas to activate/run. Just the array keys from get_all_areas()
	 * 
	 * @return array
	 */
	public function get_enabled_areas_simple() {
		$all_available = $this->get_all_areas();
		
		$enabled_areas = (array) tribe_get_option( $this->option_key_enabled_areas, $all_available );
		
		foreach ( $enabled_areas as $key => $value ) {
			if ( ! array_key_exists( $value, $all_available ) ) {
				unset( $enabled_areas[$key] );
			}
		}
		
		return $enabled_areas;
	}
		
	/**
	 * The chosen widget areas to activate/run. Full array data from get_all_areas()
	 * 
	 * @return array
	 */
	public function get_enabled_areas_full_details() {
		$all_available = $this->get_all_areas();
		
		$enabled_areas = $this->get_enabled_areas_simple();
		
		$return = array();
		
		// if none selected, return all available
		if ( empty( $enabled_areas ) ) {
			$return = $all_available;
		} else {
			foreach ( $enabled_areas as $key => $value ) {
				if ( array_key_exists( $value, $all_available ) ) {
					$return[$value] = $all_available[$value];
				}
			}
		}
		
		return $return;
	}
	
	/**
	 * Add options to Tribe settings page
	 *
	 * @see Tribe__Extension__Settings_Helper
	 */
	public function add_settings() {
		require_once dirname( __FILE__ ) . '/src/Tribe/Settings_Helper.php';

		// Setup fields on the settings page
		$setting_helper = new Tribe__Extension__Settings_Helper();
		$options = $this->get_available_area_options();

		$setting_helper->add_field(
			$this->option_key_enabled_areas,
			array(
				'type'            => 'checkbox_list',
				'label'           => esc_html__( 'Widget Areas', 'tribe-extension' ),
				'tooltip'         => esc_html__( 'Select which widget areas you want available on your site. Note: Unchecking all the boxes will not save. If you want all areas unchecked, just deactivate this extension.', 'tribe-extension' ),
				'default'         => array_keys( $options ),
				'validation_type' => 'options_multi',
				'options'         => $options,
			),
			'display',
			'tribeEventsBeforeHTML',
			true
		);
	}
	
	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		// Register all widget areas
		add_action( 'widgets_init', array( $this, 'register_sidebars' ) );
		
		add_action( 'admin_init', array( $this, 'add_settings' ) );
		
		$active_areas = $this->get_enabled_areas_full_details();
		
		foreach ( $active_areas as $key => $value ) {
			$method = '';
			$method = $value['method'];
			if ( method_exists( $this, $method ) ) {
				// add_filter or add_action
				if ( ! empty( $value['hook_type'] ) && 'filter' === $value['hook_type'] ) {
					add_filter( $key, array( $this, $method ) );
				} else {
					add_action( $key, array( $this, $method ) );
				}
			}
		}
	}
	
	/**
	 * Register all widget areas.
	 */
	public function register_sidebars() {
		$active_areas = $this->get_enabled_areas_full_details();
		
		foreach ( $active_areas as $key => $value ) {
			register_sidebar (
				array (
					'name'			=> $value['name'],
					'id'			=> "tec_ext_widget_areas__{$value['method']}",
					'description'	=> $value['desc'],
				)
			);
		}	
	}
	
	
	/**
	 * "Before Calendar" Widget Area
	 */
	public function before_template() {
		dynamic_sidebar( 'tec_ext_widget_areas__before_template' );
	}
	
	
	/**
	 * "After Calendar" Widget Area
	 */
	public function after_template() {
		dynamic_sidebar( 'tec_ext_widget_areas__after_template' );
	}
	
	
	/**
	 * "Before Event Single" Widget Area
	 */
	public function single_before_view() {
		if ( ! tribe_is_event() ) {
			return false;
		}
		
		dynamic_sidebar( 'tec_ext_widget_areas__single_before_view' );
	}
	
	
	/**
	 * "After Event Single" Widget Area
	 */
	public function single_after_view() {
		if ( ! tribe_is_event() ) {
			return false;
		}
		
		dynamic_sidebar( 'tec_ext_widget_areas__single_after_view' );
	}
	
}