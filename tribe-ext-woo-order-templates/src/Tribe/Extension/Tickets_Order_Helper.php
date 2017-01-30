<?php
// Do not load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( class_exists( 'Tribe__Extension__Tickets_Order_Helper' ) ) {
	return;
}

/**
 * Helps get the Event IDs, attendees, and ticket provider associated with an order ID
 */
class Tribe__Extension__Tickets_Order_Helper {

	/**
	 * The ID of the order this class will assist with
	 *
	 * @var int
	 */
	protected $order_id;

	/**
	 * The classname for the ticket provider
	 *
	 * @var string
	 */
	protected $provider_classname;

	/**
	 * The instance of the ticket provider class
	 *
	 * @var object
	 */
	protected $provider_instance;

	/**
	 * Sets up the variables for this order
	 *
	 * @param $order_id string|int The order ID
	 */
	public function __construct( $order_id ) {
		$this->order_id = intval( $order_id );
		$this->set_provider_classname();
	}

	/*
	 * Get the ticket provider for a given order
	 *
	 * @return string|null
	 */
	protected function set_provider_classname() {
		$ticket_modules = Tribe__Tickets__Tickets::modules();
		$class_name = null;

		// Accounts for RSVPs not having grouped orders.
		if ( Tribe__Tickets__RSVP::ATTENDEE_OBJECT === get_post_type( $this->order_id ) ) {
			$class_name = 'Tribe__Tickets__RSVP';
		}

		foreach ( $ticket_modules as $module_class => $module_description ) {
			$event_id = $module_class::get_instance()->get_event_id_from_order_id( $this->order_id );

			// This instance is the correct ticket provider.
			if ( false !== $event_id ) {
				$class_name = $module_class;
				break;
			}
		}

		if ( null !== $class_name ) {
			$this->provider_classname = $class_name;
			$this->provider_instance = $class_name::get_instance();
		}
	}

	/**
	 * Gets the ticket provider classname
	 *
	 * @return string|null
	 */
	public function get_provider_classname() {
		return $this->provider_classname;
	}

	/**
	 * Gets the ticket provider instance
	 *
	 * @return object|null
	 */
	public function get_provider_instance() {
		return $this->provider_instance;
	}

	/*
	 * Gets the attendees for the order
	 *
	 * @return array List of attendees
	 */
	public function get_attendees() {
		$order_attendees = array();

		// If we have not found an active ticker provider, we're out of luck.
		if ( empty( $this->provider_instance ) ) {
			return $order_attendees;
		}

		$event_attendees = array();

		$event_ids = $this->get_event_ids();

		foreach ( $event_ids as $event ) {
			$event_attendees = array_merge(
				$event_attendees,
				$this->provider_instance->get_attendees_array( $event )
			);
		}

		foreach ( $event_attendees as $attendee ) {
			if ( ! isset( $attendee['order_id'] ) || intval( $attendee['order_id'] ) !== $this->order_id ) {
				continue;
			}

			// Oddly the attendees email demands a slightly different format for most of these.
			// So below we duplicate keys to give it the format it expects.
			$attendee['event_id'] = $this->provider_instance->get_event_id_from_attendee_id( $attendee['attendee_id'] );
			$attendee['ticket_name'] = $attendee['ticket'];
			$attendee['holder_name'] = $attendee['purchaser_name'];
			$attendee['order_id'] = $this->order_id;
			$attendee['ticket_id'] = $attendee['product_id'];
			$attendee['qr_ticket_id'] = $attendee['attendee_id'];
			$attendee['security_code'] = $attendee['security'];

			$order_attendees[] = $attendee;
		}

		return $order_attendees;
	}

	/*
	 * Get the events IDs for the order
	 *
	 * @return array All event IDs found, can be empty
	 */
	public function get_event_ids() {
		$event_ids = array();

		// Account for RSVP not having proper order IDs.
		if ( 'Tribe__Tickets__RSVP' === $this->provider_classname ) {
			$id_query = get_post_meta( $this->order_id, Tribe__Tickets__RSVP::ATTENDEE_EVENT_KEY );

			foreach ( $id_query as $i ) {
				$id = intval( $i );
				$event_ids[ $id ] = $id;
			}
		} elseif ( ! empty( $this->provider_classname ) ) {
			$class_reflection   = new ReflectionClass( $this->provider_instance );
			$attendee_order_key = tribe_call_private_method( $this->provider_instance, 'get_attendee_order_key', $class_reflection );
			$attendee_event_key = tribe_call_private_method( $this->provider_instance, 'get_attendee_order_key', $class_reflection );
			$attendee_object    = tribe_call_private_method( $this->provider_instance, 'get_attendee_object', $class_reflection );

			if ( empty( $attendee_order_key ) || empty( $attendee_event_key ) || empty( $attendee_object ) ) {
				return $event_ids;
			}

			$attendees = get_posts( array(
				'post_type'  => $attendee_object,
				'meta_key'   => $attendee_order_key,
				'meta_value' => $this->order_id,
				'posts_per_page' => -1,
			) );

			foreach ( $attendees as $i ) {
				$id = $this->provider_instance->get_event_id_from_attendee_id( $i->ID );
				$event_ids [ $id ] = $id;
			}
		}

		return $event_ids;
	}
}
