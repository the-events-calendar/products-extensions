<?php
/**
 * Plugin Name: The Events Calendar Extension: Calendar Widget Areas
 * Description: Adds widget areas (a.k.a. sidebars) that only display on The Events Calendar pages/views. Areas may be enabled or disabled at wp-admin > Events > Settings > Display tab > Advanced Template Settings section. Note that the WP Customizer only allows you to manage widget areas that apply to the page you're currently previewing; therefore, you will need to navigate to your Events page, for example, to edit the content of those widget areas via the Customizer's live preview.
 * Version: 1.0.1
 * Extension Class: Tribe__Extension__Calendar_Widget_Areas
 * Author: Modern Tribe, Inc.
 * Author URI: http://m.tri.be/1971
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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
		$this->set_version( '1.0.1' );
	}

	/**
	 * All available widget areas this extension supports and details about each in a multidimensional associative array.
	 *
	 * Follow this format to enable a new widget area for each action or filter hook
	 * you want to use. You will also need to add an additional method toward the
	 * end of this file for each area added so we know what code to execute.
	 *
	 * @return array {
	 *     Every widget area supported by this extension
	 *
	 *                         {
	 *                         The details for each widget area
	 *
	 * @param array  $hook     The action or filter hook to be used.
	 * @param string $method   The method of this class that outputs the widget. This value may prefix with context, such as "single_", and the actual function named accordingly and having the necessary logic in place. The actual function names are "tec_ext_widget_areas__" + the 'method' name from here.
	 * @param string $name     Name of the widget in the admin screens
	 * @param string $desc     Widget description
	 * @param bool   $filter   Optional: If $hook is a 'filter' hook instead of an action hook, set this to (bool) true.
	 * @param int    $priority Optional: Set this if desired. Will default to 10 if not set.
	 *                         }
	 *                         }
	 */
	protected function get_all_areas() {
		$areas = array(
			// template
			array(
				'hook'   => 'tribe_events_before_template',
				'method' => 'before_template',
				'name'   => __( 'TEC Above Calendar', 'tribe-extension' ),
				'desc'   => __( 'Widgets in this area will be shown ABOVE The Events Calendar.', 'tribe-extension' ),
			),
			array(
				'hook'   => 'tribe_events_after_template',
				'method' => 'after_template',
				'name'   => __( 'TEC Below Calendar', 'tribe-extension' ),
				'desc'   => __( 'Widgets in this area will be shown BELOW The Events Calendar.', 'tribe-extension' ),
			),
			// single
			// template
			array(
				'hook'   => 'tribe_events_before_view',
				'method' => 'single_before_view',
				'name'   => __( 'TEC Single: Top', 'tribe-extension' ),
				'desc'   => __( 'Widgets in this area will be shown ABOVE Single Events.', 'tribe-extension' ),
			),
			// description
			array(
				'hook'   => 'tribe_events_single_event_before_the_content',
				'method' => 'single_event_before_the_content',
				'name'   => __( 'TEC Single: Above Description', 'tribe-extension' ),
				'desc'   => __( 'Widgets in this area will be shown ABOVE the Single Event Description.', 'tribe-extension' ),
			),
			// description
			array(
				'hook'   => 'tribe_events_single_event_after_the_content',
				'method' => 'single_event_after_the_content',
				'name'   => __( 'TEC Single: Below Description', 'tribe-extension' ),
				'desc'   => __( 'Widgets in this area will be shown BELOW the Single Event Description.', 'tribe-extension' ),
			),
			// details
			array(
				'hook'   => 'tribe_events_single_event_before_the_meta',
				'method' => 'single_event_before_the_meta',
				'name'   => __( 'TEC Single: Above Details', 'tribe-extension' ),
				'desc'   => __( 'Widgets in this area will be shown ABOVE the Single Event Details.', 'tribe-extension' ),
			),
			// details
			array(
				'hook'     => 'tribe_events_single_event_after_the_meta',
				'method'   => 'single_event_after_the_meta_early',
				'name'     => __( 'TEC Single: Below Details (Before)', 'tribe-extension' ),
				'desc'     => __( 'Widgets in this area will be shown DIRECTLY BELOW the Single Event Details (before Related Events and Tickets, if displayed).', 'tribe-extension' ),
				'priority' => 1,
			),
			// details
			array(
				'hook'     => 'tribe_events_single_event_after_the_meta',
				'method'   => 'single_event_after_the_meta_late',
				'name'     => __( 'TEC Single: Below Details (After)', 'tribe-extension' ),
				'desc'     => __( 'Widgets in this area will be shown BELOW the Single Event Details (after Related Events and Tickets, if displayed).', 'tribe-extension' ),
				'priority' => 100,
			),
			// template
			array(
				'hook'   => 'tribe_events_after_view',
				'method' => 'single_after_view',
				'name'   => __( 'TEC Single: Bottom', 'tribe-extension' ),
				'desc'   => __( 'Widgets in this area will be shown BELOW Single Events.', 'tribe-extension' ),
			),
		);

		return apply_filters( 'tribe_ext_calendar_widget_areas', $areas );
	}

	/**
	 * Get all method names
	 *
	 * @return array
	 */
	protected function get_all_areas_simple() {
		return wp_list_pluck( $this->get_all_areas(), 'method' );
	}

	/**
	 * Convert All Areas array from indexed array to associative array, using 'method' as the key.
	 *
	 * @return array
	 */
	protected function get_all_areas_assoc() {
		$all_available_assoc = array();

		foreach ( $this->get_all_areas() as $value ) {
			$method_name                       = $value['method'];
			$all_available_assoc[$method_name] = $value;
		}

		return $all_available_assoc;
	}


	/**
	 * Build options to present to user
	 *
	 * @return array
	 */
	protected function get_available_area_options() {
		$options = array();

		foreach ( $this->get_all_areas() as $value ) {
			$method_name           = $value['method'];
			$options[$method_name] = $value['name'];
		}

		return $options;
	}

	/**
	 * The chosen widget areas to activate/run. If none are selected in Display settings, act as if all are checked/enabled.
	 *
	 * @return array
	 */
	protected function get_enabled_areas_simple() {
		$all_available = $this->get_all_areas_simple();

		$enabled_areas = (array) tribe_get_option( $this->option_key_enabled_areas, $all_available );

		return $enabled_areas;
	}

	/**
	 * The chosen widget areas to activate/run. If none are selected in Display settings, act as if all are checked/enabled.
	 *
	 * @return array
	 */
	protected function get_enabled_areas_full_details() {
		$all_available_assoc = $this->get_all_areas_assoc();

		$enabled_areas = (array) $this->get_enabled_areas_simple();

		$return = array();

		foreach ( $enabled_areas as $value ) {
			if ( array_key_exists( $value, $all_available_assoc ) ) {
				$return[$value] = $all_available_assoc[$value];
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
		if ( ! class_exists( 'Tribe__Extension__Settings_Helper' ) ) {
			require_once dirname( __FILE__ ) . '/src/Tribe/Settings_Helper.php';
		}

		// Setup fields on the settings page
		$setting_helper = new Tribe__Extension__Settings_Helper();
		$options        = $this->get_available_area_options();

		$setting_helper->add_field(
			$this->option_key_enabled_areas,
			array(
				'type'            => 'checkbox_list',
				'label'           => esc_html__( 'Widget Areas', 'tribe-extension' ),
				'tooltip'         => esc_html__( 'Select which widget areas you want available on your site. Note: Unchecking all the boxes will not save. If you want all areas unchecked, just deactivate this extension.', 'tribe-extension' ),
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

		add_action( 'init', array( $this, 'enqueue_styles' ) );

		foreach ( $this->get_enabled_areas_full_details() as $value ) {
			if ( method_exists( $this, $value['method'] ) ) {
				if ( empty( $value['priority'] ) ) {
					$priority = 10;
				} else {
					$priority = $value['priority'];
				}

				$priority = apply_filters( 'tribe_ext_calendar_widget_area_priority', $priority, $value );

				$priority = (int) $priority;

				if ( ! empty( $value['filter'] ) ) {
					add_filter( $value['hook'], array( $this, $value['method'] ), $priority );
				} else {
					add_action( $value['hook'], array( $this, $value['method'] ), $priority );
				}
			}
		}
	}

	/**
	 * Register all widget areas.
	 */
	public function register_sidebars() {
		foreach ( $this->get_enabled_areas_full_details() as $value ) {
			register_sidebar(
				array(
					'name'        => $value['name'],
					'id'          => "tec_ext_widget_areas__{$value['method']}",
					'description' => $value['desc'],
				)
			);
		}
	}

	/**
	 * Register and enqueue stylesheet
	 */
	public function enqueue_styles() {
		wp_register_style( 'tribe-ext-calendar-widget-areas', plugin_dir_url( __FILE__ ) . 'src/resources/css/tribe-ext-calendar-widget-areas.css', array(), $this->get_version() );

		wp_enqueue_style( 'tribe-ext-calendar-widget-areas' );
	}

	/**
	 * Before Calendar widget area
	 */
	public function before_template() {
		dynamic_sidebar( 'tec_ext_widget_areas__before_template' );
	}

	/**
	 * After Calendar widget area
	 */
	public function after_template() {
		dynamic_sidebar( 'tec_ext_widget_areas__after_template' );
	}

	/**
	 * Before Event Single widget area
	 */
	public function single_before_view() {
		if ( is_singular( Tribe__Events__Main::POSTTYPE ) ) {
			dynamic_sidebar( 'tec_ext_widget_areas__single_before_view' );
		}
	}

	/**
	 * Above Event Single Description widget area
	 */
	public function single_event_before_the_content() {
		dynamic_sidebar( 'tec_ext_widget_areas__single_event_before_the_content' );
	}

	/**
	 * Below Event Single Description widget area
	 */
	public function single_event_after_the_content() {
		dynamic_sidebar( 'tec_ext_widget_areas__single_event_after_the_content' );
	}

	/**
	 * Above Event Single Details widget area
	 */
	public function single_event_before_the_meta() {
		dynamic_sidebar( 'tec_ext_widget_areas__single_event_before_the_meta' );
	}

	/**
	 * Below Event Single Details BEFORE OTHERS widget area
	 */
	public function single_event_after_the_meta_early() {
		dynamic_sidebar( 'tec_ext_widget_areas__single_event_after_the_meta_early' );
	}

	/**
	 * Below Event Single Details AFTER OTHERS widget area
	 */
	public function single_event_after_the_meta_late() {
		dynamic_sidebar( 'tec_ext_widget_areas__single_event_after_the_meta_late' );
	}

	/**
	 * After Event Single widget area
	 */
	public function single_after_view() {
		if ( is_singular( Tribe__Events__Main::POSTTYPE ) ) {
			dynamic_sidebar( 'tec_ext_widget_areas__single_after_view' );
		}
	}

}