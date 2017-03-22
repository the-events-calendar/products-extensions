<?php
/**
 * Plugin Name:     Event Tickets Extension: [event_tickets_form] shortcode
 * Description:     Insert the ticket and RSVP forms with the [event_tickets_form] shortcode. This shortcode is an available display area for the forms in WP Admin > Events > Settings > Tickets. Select the shortcode there, then insert it into any event or page that you wish to display the ticket forms in.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Tickets__Shortcode
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
class Tribe__Extension__Tickets__Shortcode extends Tribe__Extension {

	/**
	 * The action fired by our shortcode
	 *
	 * @var string
	 */
	protected $shortcode_action = 'event_tickets_form_shortcode';

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Tickets__Main', '4.4' );
		$this->add_required_plugin( 'Tribe__Tickets_Plus__Main', '4.4' );

		$this->set_url( 'https://theeventscalendar.com/extensions/tickets-shortcode/' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		add_shortcode( 'event_tickets_form', array( $this, 'output_shortcode' ) );

		add_action( 'init', array( $this, 'remove_post_filters' ) );

		if ( is_admin() ) {
			add_filter( 'tribe_tickets_settings_tab_fields', array( $this, 'admin_options' ) );
		}
	}

	/**
	 * Adds shortcode to the form display options dropdowns
	 *
	 * @see tribe_tickets_settings_tab_fields
	 */
	public function admin_options( $fields ) {
		$shortcode_description = sprintf(
			__( '%s shortcode', 'tribe-extension' ),
			'[event_tickets_form]'
		);

		if ( isset( $fields['ticket-rsvp-form-location'] ) ) {
			$fields['ticket-rsvp-form-location']['options'][ $this->shortcode_action ] = $shortcode_description;
		}

		if ( isset( $fields['ticket-commerce-form-location'] ) ) {
			$fields['ticket-commerce-form-location']['options'][ $this->shortcode_action ] = $shortcode_description;
		}

		return $fields;
	}

	/**
	 * The shortcode itself
	 *
	 * @see add_shortcode
	 */
	public function output_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'id' => null,
		), $atts );

		$original_post = $GLOBALS['post'];

		if ( is_numeric( $atts['id'] ) ) {
			$GLOBALS['post'] = get_post( $atts['id'] );
		}

		if ( tribe_is_event() ) {
			// For events, this action will be used when specified in Event > Settings.
			ob_start();
			do_action( $this->shortcode_action, '' );
			$output = ob_get_clean();
		} else {
			// For non events we must manually echo the forms, and remove the original hooks.
			$output = $this->get_forms();
		}

		// Always reset the global post.
		$GLOBALS['post'] = $original_post;

		return $output;
	}

	/**
	 * Returns the ticket forms associated with the global $post
	 */
	public function get_forms() {
		$providers = Tribe__Tickets__Tickets::modules();
		$output = '';

		foreach ( $providers as $provider_class => $name ) {
			$instance = $provider_class::get_instance();

			// These are meant to filter the_content, so pass them $output.
			$output = $instance->front_end_tickets_form_in_content( $output );
			$output = $instance->show_tickets_unavailable_message_in_content( $output );
		}

		return $output;
	}

	/**
	 * For non event posts, we have to manually remove the filters that would insert the tickets area.
	 */
	public function remove_post_filters() {

		if ( tribe_get_option( 'ticket-commerce-rsvp-location' ) === $this->shortcode_action ) {
			$rsvp = Tribe__Tickets__RSVP::get_instance();

			remove_filter( 'the_content', array( $rsvp, 'front_end_tickets_form_in_content' ), 11 );
			remove_filter( 'the_content', array( $rsvp, 'show_tickets_unavailable_message_in_content' ), 12 );
		}

		if ( tribe_get_option( 'ticket-commerce-form-location' ) === $this->shortcode_action ) {
			$providers = Tribe__Tickets__Tickets::modules();

			foreach ( $providers as $provider_class => $name ) {
				// Skip RSVP since it's handled by a separate setting.
				if ( 'Tribe__Tickets__RSVP' === $provider_class ) {
					continue;
				}

				$instance = $provider_class::get_instance();

				remove_filter( 'the_content', array( $instance, 'front_end_tickets_form_in_content' ), 11 );
				remove_filter( 'the_content', array( $instance, 'show_tickets_unavailable_message_in_content' ), 12 );
			}
		}

	}
}
