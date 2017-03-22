<?php
/**
 * Plugin Name:     Event Tickets Plus Extension: Attendee Meta Noscript
 * Description:     Adds a notice to your ticket purchase forms asking users without JavaScript enabled to enable it.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Attendee_Meta_Noscript
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
class Tribe__Extension__Attendee_Meta_Noscript extends Tribe__Extension {

	/**
	 * @var string The name of the action that the tickets hooks into
	 */
	protected $tickets_hook;

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Tickets__Main' );
		$this->add_required_plugin( 'Tribe__Tickets_Plus__Main' );

		$this->set_url( 'https://theeventscalendar.com/extensions/noscript-attendee-meta/' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		add_filter( 'tribe_tickets_commerce_tickets_form_hook', array( $this, 'set_hook' ), PHP_INT_MAX );
	}

	/**
	 * Dirty hack to get the return of a private method
	 *
	 * @see tribe_tickets_commerce_tickets_form_hook
	 */
	public function set_hook( $hook ) {
		if ( $this->tickets_hook !== $hook ) {
			$this->tickets_hook = $hook;
			add_action( $hook, array( $this, 'output_noscript' ), 0 );
		}
		return $hook;
	}

	/**
	 * Outputs the noscript tab
	 */
	function output_noscript() {
		$message = __( 'Please enable JavaScript to obtain tickets.', 'tribe-extension' );
		printf(
			'<noscript><p class="tribe-link-tickets-message">%s</p></noscript>',
			$message
		);
	}
}
