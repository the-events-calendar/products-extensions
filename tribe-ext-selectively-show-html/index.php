<?php
/**
 * Plugin Name:     The Events Calendar Extension: Selectively Show HTML Boxes
 * Description:     Adds fields to WP Admin > Events > Settings > Display for selecting which views to see the HTML Before/After content on. This allows you to show these boxes on views of your choosing.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Selectively_Show_HTML
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
class Tribe__Extension__Selectively_Show_HTML extends Tribe__Extension {

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main', '4.3' );

		$this->set_url( 'https://theeventscalendar.com/extensions/selectively-show-html-before-after-content/' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		require_once dirname( __FILE__ ) . '/src/functions/general.php';
		require_once dirname( __FILE__ ) . '/src/Tribe/View_Helper.php';

		add_action( 'admin_init', array( $this, 'add_settings' ) );

		add_filter( 'tribe_events_before_html', array( $this, 'tribe_events_before_html' ) );
		add_filter( 'tribe_events_after_html', array( $this, 'tribe_events_after_html' ) );
	}

	public function add_settings() {
		require_once dirname( __FILE__ ) . '/src/Tribe/Settings_Helper.php';

		$avail_views = Tribe__View_Helper::get_available_views();
		$field_view_options = array();

		foreach ( $avail_views as $slug => $view ) {
			$field_view_options[ $slug ] = $view['name'];
		}

		// Setup fields on the settings page
		$setting_helper = new Tribe__Settings_Helper();

		$setting_helper->add_field(
			'show_html_before_views',
			array(
				'type'            => 'checkbox_list',
				'label'           => __( 'Show HTML before on', 'tribe-extension' ),
				'tooltip'         => __( 'Show the HTML before content on these views', 'tribe-extension' ),
				'default'         => array_keys( $field_view_options ),
				'validation_type' => 'options_multi',
				'options'         => $field_view_options,
			),
			'display',
			'tribeEventsBeforeHTML',
			false
		);

		$setting_helper->add_field(
			'show_html_after_views',
			array(
				'type'            => 'checkbox_list',
				'label'           => __( 'Show HTML after on', 'tribe-extension' ),
				'tooltip'         => __( 'Show the HTML after content on these views', 'tribe-extension' ),
				'default'         => array_keys( $field_view_options ),
				'validation_type' => 'options_multi',
				'options'         => $field_view_options,
			),
			'display',
			'tribeEventsAfterHTML',
			false
		);

	}

	/**
	 * Filters tribe_events_before_html, returning an empty string on select views
	 *
	 * @see tribe_events_before_html
	 */
	public function tribe_events_before_html( $html ) {
		return $this->hide_views_htmls( $html, 'show_html_before_views' );
	}

	/**
	 * Filters tribe_events_after_html, returning an empty string on select views
	 *
	 * @see tribe_events_after_html
	 */
	public function tribe_events_after_html( $html ) {
		return $this->hide_views_htmls( $html, 'show_html_after_views' );
	}

	/**
	 * Returns empty string for any hidden views specified in the option key
	 *
	 * @param $html       string The HTML content
	 * @param $option_key string The option key to search allowed views for
	 *
	 * @return string The HTML
	 */
	public function hide_views_htmls( $html, $option_key ) {
		$visible_views = tribe_get_option( $option_key, array() );

		if ( ! Tribe__View_Helper::is_view( $visible_views ) ) {
			$html = '';
		}

		return $html;
	}
}