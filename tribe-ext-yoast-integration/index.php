<?php
/**
 * Plugin Name:     The Events Calendar Extension: Yoast Integration
 * Description:     Activate alongside Yoast SEO plugin to help your Calendar support Yoast's features.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Yoast_Integration
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
class Tribe__Extension__Yoast_Integration extends Tribe__Extension {

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main' );

		$this->set_url( 'https://theeventscalendar.com/extensions/yoast-compatibility-integration/' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		if ( ! class_exists( 'WPSEO_Frontend' ) ) {
			tribe_notice( 'tec-activate-yoast-seo', array( $this, 'activate_yoast_notice' ) );
			return;
		}

		add_filter( 'tribe_events_title_tag', array( $this, 'yoastify_title' ), 10, 3 );
	}

	/**
	 * Overrides Tribe titles with Yoast one, even for pages loaded via ajax
	 *
	 * @see tribe_events_title_tag
	 */
	function yoastify_title( $new_title, $the_title, $sep ) {

		if ( is_archive() && tribe_is_event() ) {
			$new_title = WPSEO_Frontend::get_instance()->title( $the_title, $sep );
		}

		return $new_title;
	}


	/**
	 * Generates a notice telling the user to activate Yoast SEO
	 */
	function activate_yoast_notice() {

		$yoast_url = add_query_arg(
			array(
				'tab' => 'plugin-information',
				'plugin' => 'wordpress-seo',
				'TB_iframe' => true,
				'width' => 600,
				'height' => 550,
			),
			admin_url( '/plugin-install.php' )
		);

		$yoast_link = sprintf(
			'<a href="%1$s" class="thickbox">Yoast SEO</a>',
			$yoast_url
		);

		$extension_strong = sprintf(
			'<strong>%1$s</strong>',
			$this->get_name()
		);


		$message = sprintf(
			__( 'To begin using %1$s, please install and activate the latest version of %2$s.', 'tribe-extension' ),
			$extension_strong,
			$yoast_link
		);

		printf(
			'<div class="error"><p>%s</p></div>',
			$message
		);
	}
}
