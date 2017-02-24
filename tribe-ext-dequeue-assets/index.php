<?php
/**
 * Plugin Name: Tribe Extension: Dequeque Assets
 * Description: Dequeues any scripts or styles that are registered using the tribe_asset() function. Adds a list of dequeueable assets to WP Admin > Events > Settings.
 * Version: 1.0.2
 * Extension Class: Tribe__Extension__Dequeue_Assets
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
class Tribe__Extension__Dequeue_Assets extends Tribe__Extension {

	/**
	 * All assets that have been detected on this install
	 *
	 * @var array
	 */
	protected $logged_assets = array();

	/**
	 * Any asset meant to be dequeued
	 *
	 * @var array
	 */
	protected $dequeued_assets = array();

	/**
	 * Holds the instance of Tribe__Assets
	 *
	 * @var Tribe__Assets Instance
	 */
	protected $ta_instance;

	/**
	 * Option key containing all logged assets on install
	 *
	 * @var string
	 */
	protected $logged_assets_key = 'logged_assets';

	/**
	 * Option key for dequeued assets
	 *
	 * @var string
	 */
	protected $dequeued_assets_key = 'dequeued_assets';

	/**
	 * Prefix used for reset URLs
	 *
	 * @var string Prefix
	 */
	protected $reset_url_parameter_prefix = 'tribe_extension_reset_';

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		$this->set_url( 'https://theeventscalendar.com/extensions/dequeue-assets/' );
		$this->set_version( '1.0.2' );

		// Set this to init() straight away, so we can begin logging assets.
		$this->set( 'init_hook', 'plugins_loaded' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		$this->ta_instance = Tribe__Assets::instance();

		// Cast this to an array. Due to a bug in v1.0.1 this might have been saved as an empty string.
		$this->logged_assets = (array) tribe_get_option( $this->logged_assets_key, array() );

		$this->dequeued_assets = array_merge(
			tribe_get_option( $this->dequeued_assets_key . '_css', array() ),
			tribe_get_option( $this->dequeued_assets_key . '_js', array() )
		);

		add_action( 'admin_init', array( $this, 'add_settings' ) );
		add_filter( 'tribe_asset_enqueue', array( $this, 'tribe_asset_enqueue' ), 10, 2 );
		add_action( 'shutdown', array( $this, 'update_asset_list' ) );
	}

	/**
	 * Adds settings options
	 */
	public function add_settings() {
		// Only folks who can manage_options should be allowed to manually reset things.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		require_once dirname( __FILE__ ) . '/src/Tribe/Settings_Helper.php';
		$setting_helper = new Tribe__Extension__Settings_Helper();

		$reset_dequeue_key = $this->reset_url_parameter_prefix . $this->dequeued_assets_key;
		$reset_assets_key = $this->reset_url_parameter_prefix . $this->logged_assets_key;
		$settings_url = Tribe__Settings::instance()->get_url();

		// Allow manual reset of dequeued and logged assets, in case admin JavaScript is broken.
		if ( ! empty( $_GET[ $reset_dequeue_key ] ) && wp_verify_nonce( $_GET[ $reset_dequeue_key ], $reset_dequeue_key ) ) {
			$this->reset_dequeued_assets();
		}
		if ( ! empty( $_GET[ $reset_assets_key ] ) && wp_verify_nonce( $_GET[ $reset_assets_key ], $reset_assets_key ) ) {
			$this->reset_logged_assets();
		}

		$fields = array(
			'labels_heading' => array(
				'type' => 'html',
				'html' => '<h3>' . esc_html__( 'Dequeued Assets', 'tribe-extension' ) . '</h3>',
			),
			'labels_helper_text' => array(
				'type' => 'html',
				'html' => '<p>' . esc_html__( 'Select any scripts or styles that you do not want loaded. In order for a script or style to appear in these lists you must first visit the page where it is loaded.', 'tribe-extension' ) . '</p>',
			),
			$this->dequeued_assets_key . '_css' => array(
				'type' => 'checkbox_list',
				'label' => esc_html__( 'Dequeued Styles', 'the-events-calendar' ),
				'tooltip' => esc_html__( 'Select any CSS styles that you do not want loaded on your site.', 'tribe-extension' ),
				'default' => array(),
				'options' => Tribe__Utils__Array::get( $this->logged_assets, 'css', array() ),
				// No validation is less than ideal, but it's the only way to allow no options selected to be saved.
				'validation_type' => 'none',
			),
			$this->dequeued_assets_key . '_js' => array(
				'type' => 'checkbox_list',
				'label' => esc_html__( 'Dequeued Scripts', 'the-events-calendar' ),
				'tooltip' => esc_html__( 'Select any JavaScript files that you do not want loaded on your site. Please note, dequeuing any of these is likely to break something unless you replace its functionality with a different script.', 'tribe-extension' ),
				'default' => array(),
				'options' => Tribe__Utils__Array::get( $this->logged_assets, 'js', array() ),
				// No validation is less than ideal, but it's the only way to allow no options selected to be saved.
				'validation_type' => 'none',
			),
			'reset_dequeue_lists' => array(
				'type' => 'button_link',
				'label' => esc_html__( 'Reset Dequeue List', 'tribe-extension' ),
				'url' => wp_nonce_url( $settings_url, $reset_dequeue_key, $reset_dequeue_key ),
				'tooltip' => 'Use this if your site stops working properly after dequeueing scripts.',
			),
			'reset_dequeue_logged_assets' => array(
				'type' => 'button_link',
				'label' => esc_html__( 'Reset List of Assets', 'tribe-extension' ),
				'url' => wp_nonce_url( $settings_url, $reset_assets_key, $reset_assets_key ),
				'tooltip' => 'Resets the list of options available in the above dequeue lists. You can add items back to the list by visiting any page which loads the relevant script.',
			),
		);

		$setting_helper->add_fields(
			$fields,
			'general',
			'tribeEventsMiscellaneousTitle',
			true
		);
	}

	/**
	 * Attached to the tribe_asset_enqueue filter
	 *
	 * @see tribe_asset_enqueue
	 */
	public function tribe_asset_enqueue( $enqueue, $asset ) {
		$this->log_asset( $asset );

		if ( in_array( $asset->slug, $this->dequeued_assets ) ) {
			$enqueue = false;
		}

		return $enqueue;
	}

	/**
	 * Adds an asset to our ongoing list of tribe assets
	 *
	 * @param $asset string Name of the asset
	 */
	public function log_asset( $asset ) {
		$keys = array( $asset->type, $asset->slug );
		$isset = Tribe__Utils__Array::get( $this->logged_assets, $keys, false );

		if ( ! $isset ) {
			$full_text_description = sprintf(
				'%1$s (<a href="%2$s">%3$s</a>)',
				$asset->slug,
				$asset->url,
				$asset->file
			);

			$this->logged_assets = Tribe__Utils__Array::set(
				$this->logged_assets,
				$keys,
				$full_text_description
			);
		}
	}

	/**
	 * Logs the current asset list to the database
	 */
	public function update_asset_list() {
		tribe_update_option( $this->logged_assets_key, $this->logged_assets );
	}

	/**
	 * Resets the dequeued assets option
	 */
	public function reset_dequeued_assets() {
		tribe_update_option( $this->dequeued_assets_key . '_css', array() );
		tribe_update_option( $this->dequeued_assets_key . '_js', array() );
		$this->dequeued_assets = array();
		tribe_notice( $this->reset_url_parameter_prefix . $this->dequeued_assets_key, array( $this, 'notice_reset_dequeued' ) );
	}


	/**
	 * Resets the dequeued assets option
	 */
	public function reset_logged_assets() {
		$this->logged_assets = array();
		tribe_notice( $this->reset_url_parameter_prefix . $this->logged_assets_key, array( $this, 'notice_reset_logged' ) );
	}

	/**
	 * Echoes reset dequeued notice
	 */
	public function notice_reset_dequeued() {
		printf(
			'<div class="updated"><p>%s</p></div>',
			esc_html__( 'Setting saved. No Tribe assets will be dequeued.', 'tribe-extension' )
		);
	}

	/**
	 * Echoes reset dequeued notice
	 */
	public function notice_reset_logged() {
		printf(
			'<div class="updated"><p>%s</p></div>',
			esc_html__( 'Setting saved. List of Tribe Assets has been reset. Begin browsing around the site to rebuild the list.', 'tribe-extension' )
		);
	}
}