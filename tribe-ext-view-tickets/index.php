<?php
/**
 * Plugin Name:     Event Tickets Plus Extension: Show and Print tickets
 * Description:     Adds option for viewing and printing tickets in the admin area. Currently supports WooCommerce, you can view tickets by going to the order in the admin area and clicking Order Actions > View Tickets.
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
	 * URL key for specifying a ticket provider
	 *
	 * @var string
	 */
	public $view_ticket_url_key_type = 'tribe_view_order_type';

	/**
	 * @var int Contains the requested order #
	 */
	protected $order_id;
	/**
	 * @var string Contains the order type, such as 'Woo'
	 */
	protected $order_type;

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
		add_action( 'wp_loaded', array( $this, 'show_page' ) );
		add_filter( 'woocommerce_order_actions', array( $this, 'add_woo_view_action' ) );
		add_action( 'woocommerce_order_action_tribe_view_ticket', array( $this, 'forward_woo_action' ) );
	}

	public function show_page() {
		if ( ! isset( $_GET[ $this->view_ticket_url_key ] ) || ! isset( $_GET[ $this->view_ticket_url_key_type ] ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_others_tribe_events' ) ) {
			return;
		}

		$this->order_id = intval( $_GET[ $this->view_ticket_url_key ] );
		$this->order_type = (string) $_GET[ $this->view_ticket_url_key_type ];

		$ticket_provider = get_post_type( $this->order_id );

		

		Tribe__Tickets__Tickets::get_event_attendees

		// Eventually we can add support for more ticket types.
		switch ( $this->order_type ) {
			case 'woo':
				$woo = wc_get_order( $this->order_id );
				// Double check that we actually have a valid order.
				if ( $woo ) {
					$this->output_woo_tickets( $this->order_id );
				}
				break;

			default:
				break;
		}

	}

	public function output_woo_tickets( $order_id ) {
		$wootickets = Tribe__Tickets_Plus__Commerce__WooCommerce__Main::get_instance();

		$args = array(
			'post_type'      => $wootickets->attendee_object,
			'meta_key'       => $wootickets->atendee_order_key,
			'meta_value'     => $order_id,
			'posts_per_page' => -1,
		);

		$query = new WP_Query( $args );

		$attendees = array();

		foreach ( $query->posts as $post ) {
			$product = get_post( get_post_meta( $post->ID, $wootickets->atendee_product_key, true ) );
			$ticket_unique_id = get_post_meta( $post->ID, '_unique_id', true );
			$ticket_unique_id = '' === $ticket_unique_id ? $post->ID : $ticket_unique_id;

			$attendees[]      = array(
				'event_id'      => get_post_meta( $post->ID, $wootickets->atendee_event_key, true ),
				'product_id'    => $product->ID,
				'ticket_name'   => $product->post_title,
				'holder_name'   => get_post_meta( $order_id, '_billing_first_name', true ) . ' ' . get_post_meta( $order_id, '_billing_last_name', true ),
				'order_id'      => $order_id,
				'ticket_id'     => $ticket_unique_id,
				'qr_ticket_id'  => $post->ID,
				'security_code' => get_post_meta( $post->ID, $wootickets->security_code, true ),
			);
		}

		echo $wootickets->generate_tickets_email_content( $attendees );
		exit;
	}

	public function add_woo_view_action( $actions = array() ) {
		$actions['tribe_view_ticket'] = __( 'View Tickets', 'tribe-extension' );
		return $actions;
	}

	public function forward_woo_action( $order ) {
		$url = add_query_arg(
			array(
				$this->view_ticket_url_key => $order->id,
				$this->view_ticket_url_key_type => 'woo',
			),
			trailingslashit( home_url() )
		);

		wp_redirect( $url );
		exit;
	}
}
