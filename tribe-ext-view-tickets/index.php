<?php
/**
 * Plugin Name:     Event Tickets Plus Extension: Show and Print Tickets
 * Description:     Adds option for viewing and printing tickets. In the admin area hover over an item in the Attendee List and click "View Tickets". Or, a ticket holder can view them from the frontend in their list of tickets for an event.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__View_Print_Tickets
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
class Tribe__Extension__View_Print_Tickets extends Tribe__Extension {

	/**
	 * URL key for specifying a order number
	 *
	 * @var string
	 */
	public $view_ticket_url_key = 'tribe_view_ticket_order';

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Tickets__Main' );
		$this->add_required_plugin( 'Tribe__Tickets_Plus__Main' );

		$this->set_url( 'https://theeventscalendar.com/extensions/show-print-tickets/' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		if ( version_compare( PHP_VERSION, '5.3.2', '<' ) ) {
			_doing_it_wrong(
				$this->get_name(),
				'This Extension requires PHP 5.3.2 or newer to work. Please contact your website host and inquire about updating PHP.',
				'1.0.0'
			);
			return;
		}

		add_action( 'wp_loaded', array( $this, 'show_ticket_page' ) );
		add_filter( 'woocommerce_order_actions', array( $this, 'add_woo_view_action' ) );
		add_action( 'woocommerce_order_action_tribe_view_ticket', array( $this, 'forward_woo_action' ) );
		add_action( 'event_tickets_attendees_table_row_actions', array( $this, 'event_tickets_attendees_table_row_actions' ), 0, 2 );
		add_action( 'event_tickets_orders_attendee_contents', array( $this, 'event_tickets_orders_attendee_contents' ), 10, 2 );
	}

	/**
	 * Displays ticket order when the URL contains a view ticket action
	 */
	public function show_ticket_page() {
		if ( ! isset( $_GET[ $this->view_ticket_url_key ] ) ) {
			return;
		}

		// Include the class and functions that power this.
		require_once( 'src/functions/general.php' );
		require_once( 'src/Tribe/Extension/Tickets_Order_Helper.php' );

		$order_id = intval( $_GET[ $this->view_ticket_url_key ] );
		$order_helper = new Tribe__Extension__Tickets_Order_Helper( $order_id );
		$ticket_provider = $order_helper->get_provider_instance();

		if ( empty( $ticket_provider ) ) {
			return;
		}

		$attendees = $order_helper->get_attendees();
		$holder_email = isset( $attendees[0]['purchaser_email'] ) ? $attendees[0]['purchaser_email'] : null;
		$current_user = wp_get_current_user();

		// Stop users from viewing tickets unless they're admins or purchased them.
		if ( ! current_user_can( 'edit_others_tribe_events' ) && $holder_email !== $current_user->user_email ) {
			return;
		}

		if ( ! empty( $attendees ) ) {
			echo $ticket_provider->generate_tickets_email_content( $attendees );
		} else {
			esc_html_e( 'No attendees found for this order.', 'tribe-extension' );
		}

		exit;
	}

	/**
	 * Add ticket link to front end My Tickets page
	 *
	 * @see event_tickets_orders_attendee_contents
	 */
	public function event_tickets_orders_attendee_contents( $attendee, $post ) {
		echo $this->get_ticket_link( $attendee['order_id'] );
	}

	/**
	 * Adds view ticket link to backend Attendee List
	 *
	 * @see event_tickets_attendees_table_row_actions
	 */
	public function event_tickets_attendees_table_row_actions( $actions, $order_item ) {
		$actions[] = $this->get_ticket_link( $order_item['order_id'] );
		return $actions;
	}

	/**
	 * Adds View tickets link to Woo Order Actions
	 *
	 * @see woocommerce_order_actions
	 */
	public function add_woo_view_action( $actions = array() ) {
		$actions['tribe_view_ticket'] = esc_html__( 'View Ticket', 'tribe-extension' );
		return $actions;
	}

	/**
	 * Forwards user to view tickets page on Woo's View Tickets order action
	 *
	 * @param $order string The ID of the Woo order
	 */
	public function forward_woo_action( $order ) {
		wp_redirect( $this->get_view_ticket_url( $order ) );
		exit;
	}

	/**
	 * Get a link to the ticket view
	 *
	 * @param $order_id string
	 *
	 * @return string The HTML link element
	 */
	public function get_ticket_link( $order_id ) {
		$url = $this->get_view_ticket_url( $order_id );

		$output = sprintf(
			'<a href="%1$s" class="tribe-view-ticket-link">%2$s</a>',
			esc_attr( $url ),
			esc_html__( 'View Ticket', 'tribe-extenstion' )
		);

		return $output;
	}

	/**
	 * Gets the view ticket URL
	 *
	 * @param $order_id string The order ID
	 *
	 * @return string URL
	 */
	public function get_view_ticket_url( $order_id ) {
		$url = add_query_arg(
			array( $this->view_ticket_url_key => $order_id ),
			trailingslashit( home_url() )
		);

		return $url;
	}
}
