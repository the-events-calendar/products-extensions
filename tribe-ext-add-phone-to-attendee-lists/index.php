<?php
/**
 * Plugin Name:     The Events Calendar Extension: Add Phone to Attendee Lists
 * Description:     An extension that adds attendee phone numbers to their listings in Attendee Lists.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension_Add_Phone_to_Attendee_Lists
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

class Tribe__Extension_Add_Phone_to_Attendee_Lists extends Tribe__Extension {

	/**
	 * Setup the Extension's properties.
	 *
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Tickets__Main', '4.3.3' );
		$this->set_url( 'https://theeventscalendar.com/extensions/include-attendee-phone-number-in-the-event-attendee-report/' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		add_action( 'event_tickets_attendees_table_ticket_column', array( $this, 'add_phone_to_attendee_details' ) );
    }

    /**
     * Add the phone number to attendee ticket details.
     *
     * @param array $item
     */
    public function add_phone_to_attendee_details( $item ) {
        
        if ( ! isset( $item['order_id'] ) ) {
            return;
        }

        $phone_number = get_post_meta( $item['order_id'], '_billing_phone', true );
        
        if ( empty( $phone_number ) ) {
        	return;
        }

        printf( '<div class="event-tickets-ticket-purchaser">%1$s: %2$s</div>', esc_html__( 'Phone', 'tribe-extension' ), sanitize_text_field( $phone_number ) );
    }
}