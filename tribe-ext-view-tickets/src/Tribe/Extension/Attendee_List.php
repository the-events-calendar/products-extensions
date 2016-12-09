<?php
if ( class_exists( 'Tribe__Extension__Attendee_List' ) ) {
	return;
}

/**
 * Get a list of attendees
 */
class Tribe__Extension__Attendee_List {

	public static function get_attendees( $order ) {
		switch ( get_post_type( $order ) ) {
			case 'shop_order':
				return self::woocommerce( $order );
				break;

			default:
				return self::rsvp( $order );
				break;
		}
	}

	/**
	 * Looks up the attendees for an RSVP order
	 *
	 * @param string $order The order/transaction ID.
	 *
	 * @return array Attendee list
	 */
	public static function rsvp( $order ) {
		$rsvp_class = Tribe__Tickets__RSVP::get_instance();

		$attendees = array();
		$query     = new WP_Query( array(
			'post_type'      => Tribe__Tickets__RSVP::ATTENDEE_OBJECT,
			'meta_key'       => $rsvp_class->order_key,
			'meta_value'     => $order,
			'posts_per_page' => - 1,
		) );

		foreach ( $query->posts as $post ) {
			$product = get_post( get_post_meta( $post->ID, Tribe__Tickets__RSVP::ATTENDEE_PRODUCT_KEY, true ) );

			$attendees[] = array(
				'event_id'      => get_post_meta( $post->ID, Tribe__Tickets__RSVP::ATTENDEE_EVENT_KEY, true ),
				'product_id'    => $product->ID,
				'ticket_name'   => $product->post_title,
				'holder_name'   => get_post_meta( $post->ID, $rsvp_class->full_name, true ),
				'holder_email'  => get_post_meta( $post->ID, $rsvp_class->email, true ),
				'order_id'      => $order,
				'ticket_id'     => $post->ID,
				'security_code' => get_post_meta( $post->ID, $rsvp_class->security_code, true ),
				'optout'        => (bool) get_post_meta( $post->ID, Tribe__Tickets__RSVP::ATTENDEE_OPTOUT_KEY, true ),
			);
		}

		return $attendees;
	}

	/**
	 * Looks up the attendees for an order
	 *
	 * @param string $order Woo Order ID.
	 *
	 * @return array Attendee list
	 */
	public static function woocommerce( $order ) {
		$wootickets = Tribe__Tickets_Plus__Commerce__WooCommerce__Main::get_instance();

		$args = array(
			'post_type'      => $wootickets->attendee_object,
			'meta_key'       => $wootickets->atendee_order_key,
			'meta_value'     => $order,
			'posts_per_page' => -1,
		);

		$query = new WP_Query( $args );

		$attendees = array();

		foreach ( $query->posts as $post ) {
			$product = get_post( get_post_meta( $post->ID, $wootickets->atendee_product_key, true ) );
			$ticket_unique_id = get_post_meta( $post->ID, '_unique_id', true );
			$ticket_unique_id = $ticket_unique_id === '' ? $post->ID : $ticket_unique_id;

			$attendees[]      = array(
				'event_id'      => get_post_meta( $post->ID, $wootickets->atendee_event_key, true ),
				'product_id'    => $product->ID,
				'ticket_name'   => $product->post_title,
				'holder_name'   => get_post_meta( $order, '_billing_first_name', true ) . ' ' . get_post_meta( $order, '_billing_last_name', true ),
				'order_id'      => $order,
				'ticket_id'     => $ticket_unique_id,
				'qr_ticket_id'  => $post->ID,
				'security_code' => get_post_meta( $post->ID, $wootickets->security_code, true ),
			);
		}

		return $attendees;
	}
}