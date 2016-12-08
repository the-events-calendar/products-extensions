<?php
/**
 * Plugin Name:     The Events Calendar Extension: Month View Thumbnails
 * Description:     An extension that adds event featured images to the Month View. 
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Month_View_Thumbnails
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

class Tribe__Extension__Month_View_Thumbnails extends Tribe__Extension {

	/**
	 * Setup the Extension's properties.
	 *
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main', '4.3.3' );
		$this->set_url( 'https://theeventscalendar.com/knowledgebase/adding-featured-images-to-month-view/' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		add_action( 'tribe_post_get_template_part_month/single', array( $this, 'add_thumbnail' ), 10, 2 );
		add_action( 'tribe_events_after_header', array( $this, 'add_thumbnail_css' ) );
	}

	/**
	 * Echo the featured image in the "day" grid items in month view.
	 *
	 * @return void
	 */
	public function add_thumbnail( $slug, $name ) {
		echo tribe_event_featured_image( null, 'medium' );
	}

	/**
	 * Add CSS at the top of the month view to ensure only one featured image shows.
	 *
	 * @return void
	 */
	public function add_thumbnail_css() {
	
		if ( ! tribe_is_month() ) {
			return;
		}

		?>
			<style>
			.tribe-events-month .tribe-events-event-image {
				display: none;
			}
			.tribe-events-month .tribe-events-has-events .type-tribe_events + .tribe-events-event-image {
				display: inline-block;
			}
			</style>
		<?php
	}
}