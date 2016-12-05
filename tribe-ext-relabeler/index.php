<?php
/**
 * Plugin Name:     The Events Calendar Extension: Relabeler
 * Description:     Adds option to WP Admin > Events > Display for altering labels. For example, you can change the word "Events" to a different word such as "Gigs".
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Relabeler
 * Author:          Modern Tribe, Inc.
 * Author URI:      http://m.tri.be/1971
 * License:         GPLv2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 */

// Do not load directly.
if ( ! defined( 'ABSPATH' ) ) { die( '-1' ); }
// Do not load unless Tribe Common is fully loaded.
if ( ! class_exists( 'Tribe__Extension' ) ) { return; }

/**
 * Extension main class, class begins loading on init() function.
 */
class Tribe__Extension__Relabeler extends Tribe__Extension {

	/**
	 * Caches labels that are retrieved from the database.
	 *
	 * @var array {
	 *      @type $option_name string Full text for the altered label
	 * }
	 */
	protected $label_cache = array();

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		// Settings area.
		require_once dirname( __FILE__ ) . '/src/Tribe/Settings_Helper.php';
		add_action( 'admin_init', array( $this, 'add_settings' ) );

		// Events.
		add_filter( 'tribe_event_label_singular', array( $this, 'get_event_single' ) );
		add_filter( 'tribe_event_label_singular_lowercase', array( $this, 'get_event_single_lowercase' ) );
		add_filter( 'tribe_event_label_plural', array( $this, 'get_event_plural' ) );
		add_filter( 'tribe_event_label_plural_lowercase', array( $this, 'get_event_plural_lowercase' ) );

		// Venues.
		add_filter( 'tribe_venue_label_singular', array( $this, 'get_venue_single' ) );
		add_filter( 'tribe_venue_label_singular_lowercase', array( $this, 'get_venue_single_lowercase' ) );
		add_filter( 'tribe_venue_label_plural', array( $this, 'get_venue_plural' ) );
		add_filter( 'tribe_venue_label_plural_lowercase', array( $this, 'get_venue_plural_lowercase' ) );

		// Organizers.
		add_filter( 'tribe_organizer_label_singular', array( $this, 'get_organizer_single' ) );
		add_filter( 'tribe_organizer_label_singular_lowercase', array( $this, 'get_organizer_single_lowercase' ) );
		add_filter( 'tribe_organizer_label_plural', array( $this, 'get_organizer_plural' ) );
		add_filter( 'tribe_organizer_label_plural_lowercase', array( $this, 'get_organizer_plural_lowercase' ) );
	}

	/**
	 * Adds settings options
	 */
	public function add_settings() {
		$setting_helper = new Tribe__Settings_Helper();

		$fields = array(
			'labels_heading' => array(
				'type' => 'html',
				'html' => '<h3>' . __( 'Labels', 'tribe-extension' ) . '</h3>',
			),
			'labels_helper_text' => array(
				'type' => 'html',
				'html' => '<p>' . __( 'The following fields allow you to change the default labels. Inputting something other than the default should change that word everywhere it appears.', 'tribe-extension' ) . '</p>',
			),
			'label_event_single' => array(
				'type'            => 'text',
				'label'           => esc_html__( 'Events', 'the-events-calendar' ),
				'default'         => esc_attr__( 'Events', 'the-events-calendar' ),
				'tooltip'         => __( 'Singular label for Events.', 'tribe-extension' ),
				'validation_type' => 'html',
			),
			'label_event_single_lowercase' => array(
				'type'            => 'text',
				'label'           => esc_html__( 'event', 'the-events-calendar' ),
				'default'         => esc_attr__( 'event', 'the-events-calendar' ),
				'tooltip'         => __( 'Lowercase singular label for Events.', 'tribe-extension' ),
				'validation_type' => 'html',
			),
			'label_event_plural' => array(
				'type'            => 'text',
				'label'           => esc_html__( 'Events', 'the-events-calendar' ),
				'default'         => esc_attr__( 'Events', 'the-events-calendar' ),
				'tooltip'         => __( 'Plural label for Events.', 'tribe-extension' ),
				'validation_type' => 'html',
			),
			'label_event_plural_lowercase' => array(
				'type'            => 'text',
				'label'           => esc_html__( 'events', 'the-events-calendar' ),
				'default'         => esc_attr__( 'events', 'the-events-calendar' ),
				'tooltip'         => __( 'Lowercase plural label for Events.', 'tribe-extension' ),
				'validation_type' => 'html',
			),
			'label_venue_single' => array(
				'type'            => 'text',
				'label'           => esc_html__( 'Venue', 'the-events-calendar' ),
				'default'         => esc_attr__( 'Venue', 'the-events-calendar' ),
				'tooltip'         => __( 'Singular label for Venues.', 'tribe-extension' ),
				'validation_type' => 'html',
			),
			'label_venue_single_lowercase' => array(
				'type'            => 'text',
				'label'           => esc_html__( 'venue', 'the-events-calendar' ),
				'default'         => esc_attr__( 'venue', 'the-events-calendar' ),
				'tooltip'         => __( 'Lowercase singular label for Venues.', 'tribe-extension' ),
				'validation_type' => 'html',
			),
			'label_venue_plural' => array(
				'type'            => 'text',
				'label'           => esc_html__( 'Venues', 'the-events-calendar' ),
				'default'         => esc_attr__( 'Venues', 'the-events-calendar' ),
				'tooltip'         => __( 'Plural label for Venues.', 'tribe-extension' ),
				'validation_type' => 'html',
			),
			'label_venue_plural_lowercase' => array(
				'type'            => 'text',
				'label'           => esc_html__( 'venues', 'the-events-calendar' ),
				'default'         => esc_attr__( 'venues', 'the-events-calendar' ),
				'tooltip'         => __( 'Lowercase plural label for Venues.', 'tribe-extension' ),
				'validation_type' => 'html',
			),
			'label_organizer_single' => array(
				'type'            => 'text',
				'label'           => esc_html__( 'Organizer', 'the-events-calendar' ),
				'default'         => esc_attr__( 'Organizer', 'the-events-calendar' ),
				'tooltip'         => __( 'Singular label for Organizers.', 'tribe-extension' ),
				'validation_type' => 'html',
			),
			'label_organizer_single_lowercase' => array(
				'type'            => 'text',
				'label'           => esc_html__( 'organizer', 'the-events-calendar' ),
				'default'         => esc_attr__( 'organizer', 'the-events-calendar' ),
				'tooltip'         => __( 'Lowercase singular label for Organizers.', 'tribe-extension' ),
				'validation_type' => 'html',
			),
			'label_organizer_plural' => array(
				'type'            => 'text',
				'label'           => esc_html__( 'Organizers', 'the-events-calendar' ),
				'default'         => esc_attr__( 'Organizers', 'the-events-calendar' ),
				'tooltip'         => __( 'Plural label for Organizers.', 'tribe-extension' ),
				'validation_type' => 'html',
			),
			'label_organizer_plural_lowercase' => array(
				'type'            => 'text',
				'label'           => esc_html__( 'organizers', 'the-events-calendar' ),
				'default'         => esc_attr__( 'organizers', 'the-events-calendar' ),
				'tooltip'         => __( 'Lowercase plural label for Organizers.', 'tribe-extension' ),
				'validation_type' => 'html',
			),
		);

		$setting_helper->add_fields(
			$fields,
			'display',
			'tribeEventsDateFormatSettingsTitle',
			true
		);
	}

	/**
	 * Gets the label from the database and caches it
	 *
	 * @param $key     string Option key for the label.
	 * @param $default string Value to return if none set.
	 *
	 * @return string|null
	 */
	public function get_label( $key, $default = null ) {
		if ( ! isset( $this->label_cache[ $key ] ) ) {
			$this->label_cache[ $key ] = tribe_get_option( $key, $default );
		}

		return $this->label_cache[ $key ];
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_event_single( $label ) {
		return $this->get_label( 'label_event_single', $label );
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_event_single_lowercase( $label ) {
		return $this->get_label( 'label_event_single_lowercase', $label );
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_event_plural( $label ) {
		return $this->get_label( 'label_event_plural', $label );
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_event_plural_lowercase( $label ) {
		return $this->get_label( 'label_event_plural_lowercase', $label );
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_venue_single( $label ) {
		return $this->get_label( 'label_venue_single', $label );
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_venue_single_lowercase( $label ) {
		return $this->get_label( 'label_venue_single_lowercase', $label );
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_venue_plural( $label ) {
		return $this->get_label( 'label_venue_plural', $label );
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_venue_plural_lowercase( $label ) {
		return $this->get_label( 'label_venue_plural_lowercase', $label );
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_organizer_single( $label ) {
		return $this->get_label( 'label_organizer_single', $label );
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_organizer_single_lowercase( $label ) {
		return $this->get_label( 'label_organizer_single_lowercase', $label );
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_organizer_plural( $label ) {
		return $this->get_label( 'label_organizer_plural', $label );
	}

	/**
	 * Gets the label
	 *
	 * @param $label string
	 *
	 * @return string
	 */
	public function get_organizer_plural_lowercase( $label ) {
		return $this->get_label( 'label_organizer_plural_lowercase',  $label );
	}
}