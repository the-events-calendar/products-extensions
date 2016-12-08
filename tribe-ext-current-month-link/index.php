<?php
/**
 * Plugin Name:     The Events Calendar Extension: Current Month Link
 * Description:     An extension that adds a "Back to Current Month" link to the bottom of the Month View—a handy addition to the default "Previous Month" and "Next Month" links.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Current_Month_Link
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

class Tribe__Extension__Current_Month_Link extends Tribe__Extension {

	/**
	 * Setup the Extension's properties.
	 *
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main', '4.3.3' );
		$this->set_url( 'https://theeventscalendar.com/extensions/add-return-to-current-month-link-in-month-view/' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		add_filter( 'tribe_events_the_previous_month_link', array( $this, 'add_link' ) );
		add_action( 'tribe_events_before_nav', array( $this, 'add_link_css' ) );
	}

	/**
	 * Adds a "Back to Current Month" link between previous and next month links.
	 *
	 * @param string $html
	 * @return string
	 */
	public function add_link( $html ) {

		if ( date_i18n( 'Y-m-01' ) !== tribe_get_month_view_date() ) {

			$html .= sprintf(
				'<li class="tribe-events-nav-current"><a href="%1$s">%2$s</a></li>',
				Tribe__Events__Main::instance()->getLink( 'month' ),
				esc_html__( 'Back to Current Month', 'tribe-extension' )
			);
		}

		return $html;
	}

	/**
	 * Adds some CSS for the "Back to Current Month" link.
	 *
	 * @return string
	 */
	public function add_link_css() {

			if ( ! tribe_is_month() ) {
				return;
			}
		?>
			<style>
				ul.tribe-events-sub-nav > li {
					width: 32%;
					margin-right: 0;
					margin-left: 0;
					padding-left: 0;
					padding-right: 0;
				}
				ul.tribe-events-sub-nav > li.tribe-events-nav-current a {
					float: left;
					width: 100%;
					text-align: center;
					
			</style>
		<?php
	}
}