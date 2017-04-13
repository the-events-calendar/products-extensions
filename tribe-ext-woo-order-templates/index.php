<?php
/**
 * Plugin Name:     Event Tickets Plus Extension: Enhance Woo Order Templates
 * Description:     Adds event and attendee information to the WooCommerce order pages, including emails and the checkout screen.
 * Version:         1.0.1
 * Extension Class: Tribe__Extension__Woo_Order_Templates
 * Author:          Modern Tribe, Inc.
 * Author URI:      http://m.tri.be/1971
 * License:         GPLv2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 */
defined( 'WPINC' ) or die;

// Do not load unless Tribe Common is fully loaded.
if ( ! class_exists( 'Tribe__Extension' ) ) {
	return;
}

/**
 * Extension main class, class begins loading on init() function.
 */
class Tribe__Extension__Woo_Order_Templates extends Tribe__Extension {

	/**
	 * Indicates whether the attendee stylesheet has been output or not
	 *
	 * @var bool
	 */
	protected $woo_attendee_styles_output = false;

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Tickets__Main' );
		$this->add_required_plugin( 'Tribe__Tickets_Plus__Main' );
		$this->set_version( '1.0.1' );
		$this->set_url( 'https://theeventscalendar.com/extensions/enhance-woo-order-screens-and-emails/' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		require_once dirname( __FILE__ ) . '/src/functions/general.php';

		add_action( 'woocommerce_order_item_meta_start', array( $this, 'woocommerce_echo_event_info' ), 100, 4 );

		// Hide the event title that gets added by Community Tickets, to prevent duplicates.
		if ( class_exists( 'Tribe__Events__Community__Tickets__Main' ) ) {
			remove_action( 'woocommerce_order_item_meta_start', array( Tribe__Events__Community__Tickets__Main::instance(), 'add_order_item_details' ), 10 );
		}
	}

	/**
	 * Echoes the attendee meta when attached to relevant Woo Action
	 *
	 * @see action woocommerce_order_item_meta_end
	 */
	public function woocommerce_echo_event_info( $item_id, $item, $order, $plain_text = '' ) {
		$wootix = Tribe__Tickets_Plus__Commerce__WooCommerce__Main::get_instance();
		$order_status = $order->get_status();
		$item_data = $item->get_data();

		// Generate tickets early so we can get attendee meta.
		// Note, if the default order status is one that does affect stock, no tickets will be generated.
		// Since Event Tickets Plus is not yet fully Woo 3.0 compatible, suppress notices.
		@$wootix->generate_tickets(
			$order->get_id(),
			$order_status,
			$order_status
		);

		$event = $wootix->get_event_for_ticket( $item_data['product_id'] );

		// Show event details if this ticket is for a tribe event.
		if ( ! empty( $event ) ) {
			$event_time = tribe_events_event_schedule_details( $event, '<em>', '</em>' );
			$event_address = tribe_get_full_address( $event );
			$event_details = array();

			// Output event title in same format as Community Tickets.
			$event_details[] = sprintf(
				'<a href="%1$s" class="event-title">%2$s</a>',
				esc_attr( get_permalink( $event ) ),
				esc_html( get_the_title( $event ) )
			);

			if ( ! empty( $event_time ) ) {
				$event_details[] = $event_time;
			}
			if ( ! empty( $event_address ) ) {
				$event_details[] = $event_address;
			}
			printf(
				'<div class="tribe-event-details">%1$s</div>',
				implode( $event_details, '<br />' )
			);
		}

		$this->output_woo_attendee_styles();
		$this->echo_attendee_meta( $order->get_id(), $item_data['product_id'] );
	}

	/**
	 * Echoes the woo attendee stylesheet if it's not already included in the page
	 *
	 * This allows you to output the stylesheet after wp_head(), which simplifies
	 * this extension a decent amount.
	 */
	protected function output_woo_attendee_styles() {
		if ( $this->woo_attendee_styles_output ) {
			return;
		}

		$this->woo_attendee_styles_output = true;

		echo '<style type="text/css">';
		include( 'src/resources/tribe-attendee-meta-table.css' );
		echo '</style>';
	}

	/**
	 * Echoes attendee meta for every attendee in selected order
	 *
	 * @param string $order_id  Order or RSVP post ID.
	 * @param string $ticket_id The specific ticket to output attendees for.
	 */
	protected function echo_attendee_meta( $order_id, $ticket_id = null ) {
		require_once dirname( __FILE__ ) . '/src/Tribe/Extension/Tickets_Order_Helper.php';

		$order_helper = new Tribe__Extension__Tickets_Order_Helper( $order_id );
		$attendees = $order_helper->get_attendees();

		foreach ( $attendees as $attendee ) {
			// Skip attendees that are not for this ticket type.
			if ( ! empty( $ticket_id ) && $ticket_id != $attendee['product_id'] ) {
				continue;
			}

			$table_columns = array();

			$table_columns[] = array(
				sprintf(
					'<strong class="tribe-attendee-meta-heading">%1$s</strong>',
					esc_html_x( 'Ticket ID', 'tribe-extension', 'Attendee meta table.' )
				),
				sprintf(
					'<strong class="tribe-attendee-meta-heading">%1$s</strong>',
					esc_html( $attendee['ticket_id'] )
				),
			);

			$fields = $this->get_attendee_meta( $attendee['product_id'], $attendee['qr_ticket_id'] );
			if ( ! empty( $fields ) ) {
				foreach ( $fields as $field ) {
					$table_columns[] = array(
						esc_html( $field['label'] ),
						esc_html( $field['value'] ),
					);
				}
			}

			$table_columns[] = array(
				esc_html_x( 'Security Code', 'tribe-extension', 'Attendee meta table.' ),
				esc_html( $attendee['security_code'] ),
			);

			$table = new Tribe__Simple_Table( $table_columns );
			$table->html_escape_td_values = false;
			$table->table_attributes = array(
				'class' => 'tribe-attendee-meta',
			);

			echo $table->output_table();
		}
	}

	/**
	 * Get attendee meta
	 *
	 * @param string $ticket_id    Ticket ID.
	 * @param string $qr_ticket_id QR Ticket ID.
	 *
	 * @return array Attendee meta array.
	 */
	protected function get_attendee_meta( $ticket_id, $qr_ticket_id ) {
		$output = array();

		$meta_fields = Tribe__Tickets_Plus__Main::instance()->meta()->get_meta_fields_by_ticket( $ticket_id );
		$meta_data = get_post_meta( $qr_ticket_id, Tribe__Tickets_Plus__Meta::META_KEY, true );

		foreach ( $meta_fields as $field ) {
			if ( 'checkbox' === $field->type && isset( $field->extra['options'] ) ) {
				$values = array();
				foreach ( $field->extra['options'] as $option ) {
					$key = $field->slug . '_' . sanitize_title( $option );

					if ( isset( $meta_data[ $key ] ) ) {
						$values[] = $meta_data[ $key ];
					}
				}

				$value = implode( ', ', $values );
			} elseif ( isset( $meta_data[ $field->slug ] ) ) {
				$value = $meta_data[ $field->slug ];
			} else {
				continue;
			}

			if ( ! empty( $value ) ) {
				$output[ $field->slug ] = array(
					'slug' => $field->slug,
					'label' => $field->label,
					'value' => $value,
				);
			}
		}

		return $output;
	}
}