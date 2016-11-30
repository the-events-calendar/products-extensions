<?php
/**
 * Plugin Name:     The Events Calendar Extension: Main Blog Loop
 * Description:     Additional options for including events in the main blog loop.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Main_Blog_Loop
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
class Tribe__Extension__Main_Blog_Loop extends Tribe__Extension {

	/**
	 * The sorting order option for the blog
	 *
	 * @var string Option value.
	 */
	protected $blog_sort_order;

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main', '3.12' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'add_settings' ) );
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 51 );
	}

	/**
	 * Adds settings options
	 */
	public function add_settings() {
		require_once dirname( __FILE__ ) . '/src/Tribe/Settings_Helper.php';

		$setting_helper = new Tribe__Settings_Helper();
		$setting_helper->add_field(
			'blog_sort_order',
			array(
				'type'            => 'dropdown',
				'label'           => __( 'Sort order in blog loop', 'tribe-extension' ),
				'tooltip'         => __( 'When events are included in the main blog loop they will be sorted by this field.', 'tribe-extension' ),
				'validation_type' => 'options',
				'size'            => 'small',
				'default'         => 'event_date',
				'options'         => array(
					'event_date' => __( 'Event Date', 'tribe-extension' ),
					'published' => __( 'Published', 'tribe-extension' ),
				),
			),
			'general',
			'showEventsInMainLoop',
			false
		);
	}

	/**
	 * Attached to pre_get_posts
	 *
	 * @see (wp filter) pre_get_posts
	 *
	 * @param $query WP_Query
	 */
	public function pre_get_posts( $query ) {

		if ( empty( $this->blog_sort_order ) ) {
			$this->blog_sort_order = tribe_get_option( 'blog_sort_order', 'event_date' );
		}

		if ( 'published' === $this->blog_sort_order && ! empty( $query->tribe_is_multi_posttype ) ) {
			remove_filter( 'posts_fields', array( 'Tribe__Events__Query', 'multi_type_posts_fields' ) );
		}
	}
}



