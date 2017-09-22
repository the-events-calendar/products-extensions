<?php
/**
 * Plugin Name:     Event Tickets Extension: PDF Tickets
 * Description:     When this extension plugin is active, Event Tickets (RSVP) and Event Tickets Plus (WooCommerce and EDD) tickets will be turned into PDFs, saved to your Uploads directory but not added to your Media Library, and attached to the outgoing ticket emails. Each attendee's ticket will be a separate PDF attachment in the single email. The Attendees Report Table will have a link to each PDF Ticket, allowing you to view any ticket in PDF format. Attendees will be able to obtain a PDF copy of their ticket by viewing each event's "View your Tickets" link. This extension has no wp-admin settings to configure. If you want PDF Tickets functionality disabled, deactivate this plugin.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__PDF_Tickets
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
class Tribe__Extension__PDF_Tickets extends Tribe__Extension {

	// TODO: check public, private, protected
	// TODO: docblocks
	// TODO: sanity to the order of methods
	// TODO: add header/footer content via filter (image, raw HTML, or just plain text) -- see Ticket Image in the email
	// TODO: add filters to disable per ticket type (RSVP, Woo, EDD)
	/**
	 * The PDFs to be attached to the ticket email.
	 *
	 * RSVPs: One PDF attachment per attendee, even in a single order.
	 * WooCommerce: One PDF attachment per order, not per attendee (different from RSVPs).
	 *
	 * @var array
	 */
	protected $attachments_array = array();

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Tickets__Main', '4.5.2' );

		// TODO: only if ET+ is active
		$this->add_required_plugin( 'Tribe__Tickets_Plus__Main', '4.5.0.1' );

		// TODO: support for Community Tickets Attendees Table

		$this->set_url( 'https://theeventscalendar.com/extensions/pdf-tickets/' ); // TODO: Write article
	}

	/**
	 * Extension initialization and hooks.
	 *
	 * PHP 5.3.7+ is required to avoid blank page content
	 * @link https://mpdf.github.io/troubleshooting/known-issues.html
	 */
	public function init() {
		if ( version_compare( PHP_VERSION, '5.3.7', '<' ) ) {
			_doing_it_wrong(
				$this->get_name(),
				'This Extension requires PHP 5.3.7 or newer to work. Please contact your website host and inquire about updating PHP.',
				'1.0.0'
			);

			return;
		}

		add_action( 'event_tickets_attendees_table_row_actions', array(
			$this,
			'pdf_attendee_table_row_actions'
		), 0, 2 );

		add_action( 'event_tickets_orders_attendee_contents', array(
			$this,
			'pdf_attendee_table_row_action_contents'
		), 10, 2 );

		add_action( 'event_tickets_rsvp_attendee_created', array(
			$this,
			'do_upload_pdf'
		), 50, 1 );

		add_action( 'event_ticket_woo_attendee_created', array(
			$this,
			'do_upload_pdf'
		), 50, 1 );

		add_action( 'event_ticket_edd_attendee_created', array(
			$this,
			'do_upload_pdf'
		), 50, 1 );

		add_action( 'template_redirect', array(
			$this,
			'if_pdf_url_404_upload_pdf_then_reload'
		) );
	}

	/**
	 *
	 *
	 * @return string
	 */
	private function mpdf_lib_dir() {
		$path = __DIR__ . '/vendor/mpdf';

		return trailingslashit( $path );
	}

	/**
	 * Get the absolute path to the WordPress uploads directory,
	 * with a trailing slash.
	 *
	 * It will return a path to where the WordPress /uploads/ directory is,
	 * whether it is in the default location or whether a constant has been
	 * defined or a filter used to specify an alternate location. The path
	 * it returns will look something like
	 * /path/to/wordpress/wp-content/uploads/
	 *
	 * @return string The uploads directory path.
	 */
	protected function uploads_directory_path() {
		$upload_dir = wp_upload_dir();

		return trailingslashit( $upload_dir['basedir'] );
	}

	/**
	 * Get the URL to the WordPress uploads directory,
	 * with a trailing slash.
	 *
	 * It will return a URL to where the WordPress /uploads/ directory is,
	 * whether it is in the default location or whether a constant has been
	 * defined or a filter used to specify an alternate location. The URL
	 * it returns will look something like
	 * http://example.com/wp-content/uploads/ regardless of the current
	 * month we are in.
	 *
	 * @return string The uploads directory URL.
	 */
	protected function uploads_directory_url() {
		$upload_dir = wp_upload_dir();

		return trailingslashit( $upload_dir['baseurl'] );
	}

	protected function string_starts_with( $full, $start ) {
		$string_position = strpos( $full, $start );
		if ( 0 === $string_position ) {
			return true;
		} else {
			return false;
		}
	}

	protected function string_ends_with( $full, $end ) {
		$comparison = substr_compare( $full, $end, strlen( $full ) - strlen( $end ), strlen( $end ) );
		if ( 0 === $comparison ) {
			return true;
		} else {
			return false;
		}
	}

	// TODO: runs 4 times per attendee
	protected function get_event_meta_key_from_attendee_id( $attendee_id ) {
		$attendee_id = absint( $attendee_id );

		if ( 0 >= $attendee_id ) {
			return false;
		}

		// Get all the custom fields for the Attendee Post ID
		$attendee_custom_fields = get_post_custom( $attendee_id );

		/**
		 * Find the first custom field key that ends in "_event", although there should not
		 * ever be more than one
		 *
		 * The key is named this way even if a ticket is assigned to a post type
		 * other than tribe_events (e.g. post, page)
		 *
		 * Chose not to use get_post_meta() here because we do not know the ticket type;
		 * therefore, we do not know the exact meta_key to search for, and we want to
		 * avoid multiple database calls.
		 */
		foreach ( $attendee_custom_fields as $key => $value ) {
			/*
			 * example keys that would match:
			 *   _tribe_rsvp_event
			 *   _tribe_wooticket_event
			 *   _tribe_eddticket_event
			 *
			 * Could be enhanced to support ATTENDEE_EVENT_KEY, but that would be really edge case.
			 */
			$ends_in_event = $this->string_ends_with( $key, '_event' );

			if ( true === $ends_in_event ) {
				$starts_with_tribe = $this->string_starts_with( $key, '_tribe_' );
				if ( true === $starts_with_tribe ) {
					$event_key = $key;
					break;
				}
			}
		}

		if ( empty( $event_key ) ) {
			return false;
		}

		return $event_key;
	}

	protected function get_event_id_from_attendee_id( $attendee_id ) {
		$event_key = $this->get_event_meta_key_from_attendee_id( $attendee_id );

		$event_id = absint( get_post_meta( $attendee_id, $event_key, true ) );

		if ( empty( $event_id ) ) {
			return false;
		}

		return $event_id;

	}

	protected function get_ticket_provider_slug_from_attendee_id( $attendee_id ) {
		$event_key = $this->get_event_meta_key_from_attendee_id( $attendee_id );
		/**
		 * Determine the ticket type from the event key
		 *
		 * These slugs are not just made up. They are the same as the
		 * 'provider_slug' key that comes from get_order_data() in each
		 * ticket provider's main classes. However, we are simply using
		 * them for file naming purposes.
		 */
		if ( false !== strpos( $event_key, 'rsvp' ) ) {
			$ticket_provider_slug = 'rsvp';
		} elseif ( false !== strpos( $event_key, 'wooticket' ) ) {
			$ticket_provider_slug = 'woo';
		} elseif ( false !== strpos( $event_key, 'eddticket' ) ) {
			$ticket_provider_slug = 'edd';
		} else {
			$ticket_provider_slug = ''; // unknown ticket type
		}

		return $ticket_provider_slug;
	}

	protected function get_ticket_provider_main_class_instance( $ticket_provider_slug ) {
		if ( 'rsvp' === $ticket_provider_slug ) {
			if ( ! class_exists( 'Tribe__Tickets__RSVP' ) ) {
				return false;
			} else {
				$instance = Tribe__Tickets__RSVP::get_instance();
			}
		} elseif ( 'woo' === $ticket_provider_slug ) {
			if ( ! class_exists( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' ) ) {
				return false;
			} else {
				$instance = Tribe__Tickets_Plus__Commerce__WooCommerce__Main::get_instance();
			}
		} elseif ( 'edd' === $ticket_provider_slug ) {
			if ( ! class_exists( 'Tribe__Tickets_Plus__Commerce__EDD__Main' ) ) {
				return false;
			} else {
				$instance = Tribe__Tickets_Plus__Commerce__EDD__Main::get_instance();
			}
		} else {
			return false; // unknown ticket provider
		}

		return $instance;
	}

	/**
	 * PDF file name without leading server path or URL, created solely from
	 * the Attendee ID.
	 *
	 * Naming convention for this extension's PDFs.
	 *
	 * @param $attendee_id Ticket Attendee ID
	 *
	 * @return bool|string
	 */
	protected function get_pdf_name( $attendee_id = 0 ) {
		$event_id = $this->get_event_id_from_attendee_id( $attendee_id );

		$ticket_provider_slug = $this->get_ticket_provider_slug_from_attendee_id( $attendee_id );

		// Make sure Attendee ID is wrapped in double-underscores so get_attendee_id_from_attempted_url() works
		$file_name = sprintf( 'et_%d_%s__%d__',
			$event_id,
			$ticket_provider_slug,
			$attendee_id
		);

		$file_name .= md5( $file_name );

		$file_name = substr( $file_name, 0, 50 );

		// This filter is available if you decide you must use it, but note that some functionality may be lost if you utilize it.
		$file_name = apply_filters( 'event_tickets_pdf_tickets_get_pdf_name', $file_name, $event_id, $ticket_provider_slug, $attendee_id );

		$file_name .= '.pdf';

		// remove all whitespace from file name, just as a sanity check
		$file_name = preg_replace( '/\s+/', '', $file_name );

		return $file_name;
	}

	public function get_pdf_path( $attendee_id ) {
		return $this->uploads_directory_path() . $this->get_pdf_name( $attendee_id );
	}

	public function get_pdf_url( $attendee_id ) {
		return $this->uploads_directory_url() . $this->get_pdf_name( $attendee_id );
	}

	/**
	 * RSVPs: Creates ticket PDF and saves to WP's uploads directory.
	 *
	 * @since 2.1.0
	 *
	 * @param      $attendee_id ID of attendee ticket
	 * @param bool $email       Add PDF to email attachments array
	 *
	 * @return bool
	 */
	public function do_upload_pdf( $attendee_id, $email = true ) {

		$successful = false;

		$ticket_provider_slug = $this->get_ticket_provider_slug_from_attendee_id( $attendee_id );

		$ticket_instance = $this->get_ticket_provider_main_class_instance( $ticket_provider_slug );

		if ( empty( $ticket_instance ) ) {
			return $successful;
		}

		$event_id = $this->get_event_id_from_attendee_id( $attendee_id );

		$attendees = $ticket_instance->get_attendees_array( $event_id );

		if (
			empty( $attendees )
			|| ! is_array( $attendees )
		) {
			return $successful;
		}

		$attendees_array = array();

		//file_put_contents('dump.txt', var_export($attendees,true));

		foreach ( $attendees as $attendee ) {
			if ( $attendee['attendee_id'] == $attendee_id ) {
				$attendees_array[] = $attendee;
			}
		}

		if ( empty( $attendees_array ) ) {
			return $successful;
		}

		$html = $ticket_instance->generate_tickets_email_content( $attendees_array );

		if ( empty( $html ) ) {
			return $successful;
		}

		$file_name = $this->get_pdf_path( $attendee_id );

		if ( empty( $file_name ) ) {
			return $successful;
		}

		if ( file_exists( $file_name ) ) {
			$successful = true;
		} else {
			$this->output_pdf( $html, $file_name );

			$successful = true;
		}

		// TODO: do_action hook -- like maybe to add to media library if desired?

		if (
			true === $successful
			&& true === $email
		) {
			$this->attachments_array[] = $file_name;

			if ( 'rsvp' === $ticket_provider_slug ) {
				add_filter( 'tribe_rsvp_email_attachments', array( $this, 'email_attach_pdf' ) );
			} elseif ( 'woo' === $ticket_provider_slug ) {
				// TODO: ticket attachments are getting added to Your Tickets, Order Receipt, and Admin Notification emails -- likely only want it attached to the Your Tickets email -- didn't fully test the other two.
				add_filter( 'woocommerce_email_attachments', array( $this, 'email_attach_pdf' ) );
			} elseif ( 'edd' === $ticket_provider_slug ) {
				add_filter( 'edd_ticket_receipt_attachments', array( $this, 'email_attach_pdf' ) );
			} else {
				// unknown ticket type so no emailing to do
			}
		}

		return $successful;
	}

	/**
	 * Attach the PDF to the ticket email
	 *
	 * RSVP, Woo, and EDD all just pass an attachments array so we can get away
	 * with a single, simple function here.
	 *
	 * @param $attachments
	 *
	 * @return array
	 */
	public function email_attach_pdf( $attachments ) {
		$attachments = array_merge( $attachments, $this->attachments_array );

		// just a sanity check
		$attachments = array_unique( $attachments );

		return $attachments;
	}

	/**
	 * Create the HTML link to the PDF Ticket file.
	 *
	 * @return string The HTML code
	 */
	public function ticket_link( $attendee_id ) {
		$text = __( 'PDF Ticket', 'tribe-extension' );

		$target = apply_filters( 'event_tickets_pdf_tickets_link_target', '_blank' );

		$output = sprintf(
			'<a href="%s"',
			esc_attr( $this->get_pdf_url( $attendee_id ) )
		);

		if ( ! empty( $target ) ) {
			$output .= sprintf( ' target="%s"',
				esc_attr( $target )
			);
		}

		$output .= sprintf(
			' class="tribe-view-ticket-link">%s</a>',
			esc_html( $text )
		);

		return $output;
	}

	/**
	 * Add ticket link to front-end "View your Tickets" page
	 *
	 * @see event_tickets_orders_attendee_contents
	 */
	public function pdf_attendee_table_row_action_contents( $attendee ) {
		echo $this->ticket_link( $attendee['attendee_id'] );
	}


	/**
	 * Adds view ticket link to wp-admin Attendee List
	 *
	 * @see event_tickets_attendees_table_row_actions
	 */
	public function pdf_attendee_table_row_actions( $actions, $item ) {
		$actions[] = $this->ticket_link( $item['attendee_id'] );

		return $actions;
	}

	/**
	 * Outputs PDF
	 *
	 * @param string $html      HTML content to be turned into PDF.
	 * @param string $file_name Full file name, including path on server.
	 *                          The name of the file. If not specified, the document will be sent
	 *                          to the browser (destination I).
	 *                          BLANK or omitted: "doc.pdf"
	 * @param string $dest      I: send the file inline to the browser. The plug-in is used if available.
	 *                          The name given by $filename is used when one selects the "Save as"
	 *                          option on the link generating the PDF.
	 *                          D: send to the browser and force a file download with the name
	 *                          given by $filename.
	 *                          F: save to a local file with the name given by $filename (may
	 *                          include a path).
	 *                          S: return the document as a string. $filename is ignored.
	 *
	 * @link https://mpdf.github.io/reference/mpdf-functions/output.html
	 */
	protected function output_pdf( $html, $file_name, $dest = 'F' ) {
		if ( empty( $file_name ) ) {
			$file_name = 'et_ticket_' . uniqid();
		}

		if ( '.pdf' !== substr( $file_name, - 4 ) ) {
			$file_name .= '.pdf';
		}

		/**
		 * Empty the output buffer to ensure the website page's HTML is not included by accident.
		 *
		 * @link https://mpdf.github.io/what-else-can-i-do/capture-html-output.html
		 * @link https://stackoverflow.com/a/35574170/893907
		 */
		ob_clean();

		// Mute mPDF's many notices.
		$mpdf = $this->get_mpdf( $html );
		$mpdf->Output( $file_name, $dest );
	}

	/**
	 * Converts HTML to mPDF object
	 *
	 * @param string $html The full HTML you want converted to a PDF
	 *
	 * @return mPDF
	 */
	protected function get_mpdf( $html ) {
		require_once( $this->mpdf_lib_dir() . 'mpdf.php' );

		/**
		 * Creating and setting the PDF
		 *
		 * Reference vendor/mpdf/config.php, especially since it may not match the documentation.
		 *
		 * @link https://mpdf.github.io/reference/mpdf-variables/overview.html
		 * @link https://github.com/mpdf/mpdf/pull/490
		 */
		$mpdf = new mPDF( 'c' ); // 'c' sets the mPDF Mode to use onlyCoreFonts so that we do not need to include any fonts (like the dejavu... ones) in vendor/mpdf/ttfontdata

		$mpdf->WriteHTML( $html );

		return $mpdf;
	}

	/**
	 *
	 * @link https://css-tricks.com/snippets/php/get-current-page-url/
	 *
	 * @return string
	 */
	private function get_current_page_url() {
		$url = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://' . $_SERVER["SERVER_NAME"] : 'https://' . $_SERVER["SERVER_NAME"];
		$url .= ( $_SERVER["SERVER_PORT"] !== '80' ) ? ":" . $_SERVER["SERVER_PORT"] : "";
		$url .= $_SERVER["REQUEST_URI"];

		return $url;
	}

	private function get_attendee_id_from_attempted_url( $url ) {
		$url = strtolower( esc_url( $url ) );

		$beginning_url_check = $this->uploads_directory_url() . 'et_';

		// bail if...
		if (
			// no URL
			empty( $url )
			// not looking for a file in Uploads that begins with 'et_'
			|| false === $this->string_starts_with( $url, $beginning_url_check )
			// not looking for a file ending in '.pdf'
			|| false === $this->string_ends_with( $_SERVER['REQUEST_URI'], '.pdf' )
		) {
			return false;
		}

		// remove from the front
		$file_name = str_replace( $beginning_url_check, '', $url );

		// look for an integer with double underscores on both sides
		preg_match( '/__(\d+)__/', $file_name, $matches );

		// bail if not found
		if ( empty( $matches ) ) {
			return false;
		}

		$guessed_attendee_id = absint( $matches[1] );

		if ( 0 >= $guessed_attendee_id ) {
			return false;
		}

		$post_type = get_post_type( $guessed_attendee_id );

		if (
			empty( $post_type )
			|| false === $this->string_starts_with( $post_type, 'tribe_' )
		) {
			return false;
		}

		return $guessed_attendee_id; // hopefully we were right!
	}

	public function if_pdf_url_404_upload_pdf_then_reload() {
		if ( ! is_404() ) {
			return;
		}

		$url = strtolower( $this->get_current_page_url() );

		$guessed_attendee_id = $this->get_attendee_id_from_attempted_url( $url );

		if ( empty( $guessed_attendee_id ) ) {
			return;
		}

		$query_key = 'et_ticket_created';

		if ( isset ( $_GET[ $query_key ] ) ) {
			$already_retried = true;
		} else {
			$already_retried = false;
		}

		if ( true === $already_retried ) {
			return;
		}

		// if we got this far, we probably guessed correctly so let's generate the PDF
		$this->do_upload_pdf( $guessed_attendee_id );

		/**
		 * Redirect to retrying reloading the PDF
		 *
		 * Cache buster and technically a new URL so status code 307 Temporary Redirect applies
		 *
		 * @link https://en.wikipedia.org/wiki/List_of_HTTP_status_codes#3xx_Redirection
		 */
		$url = add_query_arg( $query_key, time() );
		wp_redirect( $url, 307 );
		exit;
	}

}
