<?php
/**
 * Plugin Name:     Event Tickets Extension: PDF Tickets
 * Description:     RSVP, WooCommerce, and Easy Digital Downloads will become PDFs (will be saved to your Uploads directory) and attached to the ticket email.
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

	/**
	 * The custom field in which to store the attendee's PDF ticket's file name.
	 *
	 * We generate a random file name for security purposes, but we store it in
	 * the database for lookup purposes. It should not end in ".pdf" because
	 * the file extension gets added manually during output of the full file
	 * path or the full URL/link.
	 *
	 * @var string
	 */
	public $pdf_ticket_meta_key = '_tribe_tickets_pdf_file_name';

	/**
	 * The query argument key for the Attendee ID.
	 *
	 * @var string
	 */
	public $pdf_url_query_arg_key = 'tribe_tickets_pdf_attendee';

	/**
	 * The query argument key for retrying loading an attempted PDF.
	 *
	 * @var string
	 */
	public $pdf_retry_url_query_arg_key = 'tribe_tickets_pdf_retried';

	/**
	 * An array of the absolute file paths of the PDF(s) to be attached
	 * to the ticket email.
	 *
	 * One PDF attachment per attendee, even in a single order.
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

		add_action( 'tribe_plugins_loaded', array( $this, 'required_tribe_classes' ), 0 );

		$this->set_url( 'https://theeventscalendar.com/extensions/pdf-tickets/' );
	}

	/**
	 * Check required plugins after all Tribe plugins have loaded.
	 */
	public function required_tribe_classes() {
		if ( Tribe__Dependency::instance()->is_plugin_active( 'Tribe__Tickets_Plus__Main' ) ) {
			$this->add_required_plugin( 'Tribe__Tickets_Plus__Main', '4.5.6' );

			if ( Tribe__Dependency::instance()->is_plugin_active( 'Tribe__Events__Community__Tickets__Main' ) ) {
				$this->add_required_plugin( 'Tribe__Events__Community__Tickets__Main', '4.4.3' );
			}

		}
	}

	/**
	 * Extension initialization and hooks.
	 *
	 * PHP 5.3.7+ is required to avoid blank page content via mPDF.
	 *
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

		// Event Tickets
		add_action( 'event_tickets_attendees_table_row_actions', array( $this, 'pdf_attendee_table_row_actions' ), 0, 2 );

		add_action( 'event_tickets_orders_attendee_contents', array( $this, 'pdf_attendee_table_row_action_contents' ), 10, 2 );

		add_action( 'event_tickets_rsvp_attendee_created', array( $this, 'do_upload_pdf' ), 50, 1 );

		// Event Tickets Plus: WooCommerce
		add_action( 'event_ticket_woo_attendee_created', array( $this, 'do_upload_pdf' ), 50, 1 );

		// Event Tickets Plus: Easy Digital Downloads
		add_action( 'event_ticket_edd_attendee_created', array( $this, 'do_upload_pdf' ), 50, 1 );

		// For generating a PDF on the fly
		add_action( 'template_redirect', array( $this, 'if_pdf_url_404_upload_pdf_then_reload' ) );
	}

	/**
	 * The absolute path to the mPDF library directory.
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
	 * regardless of the "Organize my uploads into month- and year-based
	 * folders" option in wp-admin > Settings > Media.
	 *
	 * @return string The uploads directory path.
	 */
	protected function uploads_directory_path() {
		$upload_dir = wp_upload_dir();

		return trailingslashit( $upload_dir['basedir'] );
	}

	/**
	 * Get the URL to the WordPress uploads directory, with a trailing slash.
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

	/**
	 * PDF file name without leading server path or URL, created solely from
	 * the Attendee ID.
	 *
	 * Naming convention for this extension's PDFs, excluding the ".pdf" file
	 * extension. Stored as a custom field on the Attendee ID (ticket post ID).
	 *
	 * @param $attendee_id Ticket Attendee ID.
	 *
	 * @return string
	 */
	protected function get_pdf_name( $attendee_id = 0 ) {
		$file_name = get_post_meta( $attendee_id, $this->pdf_ticket_meta_key, true );

		if ( ! empty( $file_name ) ) {
			return $file_name;
		}

		// should only be one result
		$event_ids = tribe_tickets_get_event_ids( $attendee_id );

		if ( ! empty( $event_ids ) ) {
			$event_id = $event_ids[0];
		} else {
			$event_id = 0;
		}

		$ticket_provider_data = tribe_tickets_get_ticket_provider( $attendee_id );

		$ticket_class = $ticket_provider_data->className;

		$file_name = $this->generate_random_pdf_name( $attendee_id, $event_id, $ticket_class );

		add_post_meta( $attendee_id, $this->pdf_ticket_meta_key, $file_name, true );

		return $file_name;
	}

	/**
	 * Generate unique ID file name.
	 *
	 * @param int $attendee_id
	 * @param int $event_id
	 * @param int $ticket_class
	 *
	 * @return string
	 */
	private function generate_random_pdf_name( $attendee_id = 0, $event_id = 0, $ticket_class = '' ) {
		$file_name = uniqid( 'tribe_tickets_', true );

		// uniqid() with more_entropy results in something like '59dfc07503b009.71316471'
		$file_name = str_replace( '.', '', $file_name );

		/**
		 * Filter to customize the file name of the generated PDF.
		 *
		 * Could choose to limit the length, add additional randomization, or
		 * even remove the file extension if needed for some reason.
		 *
		 * @var $file_name
		 * @var $ticket_class
		 * @var $event_id
		 * @var $attendee_id
		 */
		$file_name = apply_filters( 'tribe_ext_pdf_tickets_generate_random_pdf_name', $file_name, $ticket_class, $event_id, $attendee_id );

		$file_name = sanitize_file_name( $file_name );

		return $file_name;
	}

	/**
	 * Get absolute path to the PDF file, including ".pdf" at the end.
	 *
	 * @param $attendee_id
	 *
	 * @return string
	 */
	private function get_pdf_path( $attendee_id ) {
		return $this->uploads_directory_path() . $this->get_pdf_name( $attendee_id ) . '.pdf';
	}

	/**
	 * Get the full URL to the PDF file, including ".pdf" at the end.
	 *
	 * Example result: http://yoursite.com/wp-content/uploads/tribe_tickets_xh2msh810osajsz.pdf?tribe_tickets_pdf_attendee=824
	 *
	 * @param $attendee_id
	 *
	 * @return string
	 */
	private function get_pdf_url( $attendee_id ) {
		$file_url = $this->uploads_directory_url() . $this->get_pdf_name( $attendee_id ) . '.pdf';

		$file_url = add_query_arg( $this->pdf_url_query_arg_key, $attendee_id, $file_url );

		return esc_url( $file_url );
	}

	/**
	 * Create PDF, save to server, and add to email queue.
	 *
	 * @param      $attendee_id ID of attendee ticket.
	 * @param bool $email       Add PDF to email attachments array.
	 *
	 * @return bool
	 */
	public function do_upload_pdf( $attendee_id, $email = true ) {
		$successful = false;

		$ticket_provider_data = tribe_tickets_get_ticket_provider( $attendee_id );
		$ticket_class         = $ticket_provider_data->className;
		$ticket_instance      = $ticket_class::get_instance();

		if ( empty( $ticket_instance ) ) {
			return $successful;
		}

		// should only be one result
		$event_ids = tribe_tickets_get_event_ids( $attendee_id );

		if ( ! empty( $event_ids ) ) {
			$event_id  = $event_ids[0];
			$attendees = $ticket_instance->get_attendees_array( $event_id );
		}

		if (
			empty( $attendees )
			|| ! is_array( $attendees )
		) {
			return $successful;
		}

		$attendees_array = array();

		foreach ( $attendees as $attendee ) {
			if ( $attendee['attendee_id'] == $attendee_id ) {
				$attendees_array[] = $attendee;
			}
		}

		if ( empty( $attendees_array ) ) {
			return $successful;
		}

		/**
		 * Because $html is the full HTML DOM sent to the PDF generator, adding
		 * anything to the beginning or the end would likely cause problems.
		 *
		 * If you want to alter what gets sent to the PDF generator, follow the
		 * Themer's Guide for tickets/email.php or use that template file's
		 * existing hooks.
		 *
		 * @link https://theeventscalendar.com/knowledgebase/themers-guide/#tickets
		 */
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
			$successful = $this->output_pdf( $html, $file_name );
		}

		/**
		 * Action hook after the PDF ticket gets created.
		 *
		 * Might be useful if you want the PDF file added to the Media Library
		 * via wp_insert_attachment(), for example.
		 *
		 * @var $ticket_class
		 * @var $event_id
		 * @var $attendee_id
		 * @var $file_name
		 */
		do_action( 'tribe_ext_pdf_tickets_uploaded_pdf', $ticket_class, $event_id, $attendee_id, $file_name );

		/**
		 * Filter to disable PDF email attachments, either entirely (just pass
		 * false) or per event, attendee, ticket type, or some other logic.
		 *
		 * @var $email
		 * @var $ticket_class
		 * @var $event_id
		 * @var $attendee_id
		 * @var $file_name
		 */
		$email = (bool) apply_filters( 'tribe_ext_pdf_tickets_attach_to_email', $email, $ticket_class, $event_id, $attendee_id, $file_name );

		if (
			true === $successful
			&& true === $email
		) {
			$this->attachments_array[] = $file_name;

			if ( 'Tribe__Tickets__RSVP' === $ticket_class ) {
				add_filter( 'tribe_rsvp_email_attachments', array( $this, 'email_attach_pdf' ) );
			} elseif ( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' === $ticket_class ) {
				add_filter( 'tribe_tickets_plus_woo_email_attachments', array( $this, 'email_attach_pdf', ) );
			} elseif ( 'Tribe__Tickets_Plus__Commerce__EDD__Main' === $ticket_class ) {
				add_filter( 'edd_ticket_receipt_attachments', array( $this, 'email_attach_pdf' ) );
			} else {
				// unknown ticket type so no emailing to do
			}
		}

		return $successful;
	}

	/**
	 * Attach the queued PDF(s) to the ticket email.
	 *
	 * RSVP, Woo, and EDD filters all just pass an attachments array so we can
	 * get away with a single, simple function here.
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
	 * Create the HTML link markup (a href) for a PDF Ticket file.
	 *
	 * @param $attendee_id
	 *
	 * @return string
	 */
	protected function ticket_link( $attendee_id ) {
		$text = __( 'PDF Ticket', 'tribe-extension' );

		/**
		 * Filter to control the link target for Attendees Report links.
		 *
		 * @param $target
		 */
		$target = apply_filters( 'tribe_ext_pdf_tickets_link_target', '_blank' );

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
			' class="tribe-ext-pdf-ticket-link">%s</a>',
			esc_html( $text )
		);

		return $output;
	}

	/**
	 * Add link to the PDF ticket to the front-end "View your Tickets" page.
	 *
	 * @see Tribe__Extension__PDF_Tickets::ticket_link()
	 */
	public function pdf_attendee_table_row_action_contents( $attendee ) {
		echo $this->ticket_link( $attendee['attendee_id'] );
	}


	/**
	 * Add a link to each ticket's PDF ticket on the wp-admin Attendee List.
	 *
	 * Community Events Tickets' Attendee List/Table comes from the same
	 * source as the wp-admin one so no extra work to get it working there.
	 *
	 * @see Tribe__Extension__PDF_Tickets::ticket_link()
	 */
	public function pdf_attendee_table_row_actions( $actions, $item ) {
		$actions[] = $this->ticket_link( $item['attendee_id'] );

		return $actions;
	}

	/**
	 * Outputs PDF.
	 *
	 * @see  Tribe__Extension__PDF_Tickets::get_mpdf()
	 * @see  mPDF::Output()
	 *
	 * @link https://mpdf.github.io/reference/mpdf-functions/output.html
	 *
	 * @param string $html      HTML content to be turned into PDF.
	 * @param string $file_name Full file name, including path on server.
	 *                          The name of the file. If not specified, the
	 *                          document will be sent to the browser
	 *                          (destination I).
	 *                          BLANK or omitted: "doc.pdf"
	 * @param string $dest      I: send the file inline to the browser. The
	 *                          plug-in is used if available.
	 *                          The name given by $filename is used when one
	 *                          selects the "Save as" option on the link
	 *                          generating the PDF.
	 *                          D: send to the browser and force a file
	 *                          download with the name given by $filename.
	 *                          F: save to a local file with the name given by
	 *                          $filename (may include a path).
	 *                          S: return the document as a string.
	 *                          $filename is ignored.
	 *
	 * @return bool
	 */
	protected function output_pdf( $html, $file_name, $dest = 'F' ) {
		if ( empty( $file_name ) ) {
			return false;
		}

		/**
		 * Empty the output buffer to ensure the website page's HTML is not
		 * included by accident.
		 *
		 * @link https://mpdf.github.io/what-else-can-i-do/capture-html-output.html
		 * @link https://stackoverflow.com/a/35574170/893907
		 */
		ob_clean();

		$mpdf = $this->get_mpdf( $html );
		$mpdf->Output( $file_name, $dest );

		return true;
	}

	/**
	 * Converts HTML to mPDF object.
	 *
	 * @see mPDF::WriteHTML()
	 *
	 * @param string $html The full HTML you want converted to a PDF.
	 *
	 * @return mPDF
	 */
	protected function get_mpdf( $html ) {
		require_once( $this->mpdf_lib_dir() . 'mpdf.php' );

		/**
		 * Creating and setting the PDF
		 *
		 * Reference vendor/mpdf/config.php, especially since it may not
		 * match the documentation.
		 * 'c' mode sets the mPDF Mode to use onlyCoreFonts so that we do not
		 * need to include any fonts (like the dejavu... ones) in
		 * vendor/mpdf/ttfontdata
		 *
		 * @link https://mpdf.github.io/reference/mpdf-variables/overview.html
		 * @link https://github.com/mpdf/mpdf/pull/490
		 */
		$mpdf = new mPDF( 'c' );

		$mpdf->WriteHTML( $html );

		return $mpdf;
	}

	/**
	 * Create and upload a 404'd PDF Ticket, then redirect to it now that
	 * it exists.
	 *
	 * If we attempted to load a PDF Ticket but it was not found (404), then
	 * create the PDF Ticket, upload it to the server, and reload the attempted
	 * URL, adding a query string on the end as a cache buster and so the
	 * 307 Temporary Redirect code is technically valid.
	 *
	 * @see Tribe__Extension__PDF_Tickets::do_upload_pdf()
	 */
	public function if_pdf_url_404_upload_pdf_then_reload() {
		if ( ! is_404() ) {
			return;
		}

		if ( ! function_exists( 'tribe_get_request_var' ) ) {
			return;
		}

		// Check if we already retried, in which case we should stop retrying
		$already_retried = tribe_get_request_var( $this->pdf_retry_url_query_arg_key );

		if ( ! empty( $already_retried ) ) {
			return;
		}

		// Get the Attendee ID and then try for the first time
		$attendee_id = tribe_get_request_var( $this->pdf_url_query_arg_key );

		if ( empty( $attendee_id ) ) {
			return;
		}

		$this->do_upload_pdf( $attendee_id, false );

		/**
		 * Redirect to retrying reloading the PDF.
		 *
		 * Cache buster and technically a new URL so status code 307
		 * Temporary Redirect applies.
		 *
		 * @link https://en.wikipedia.org/wiki/List_of_HTTP_status_codes#3xx_Redirection
		 */
		$url = add_query_arg( $this->pdf_retry_url_query_arg_key, time() );

		wp_redirect( esc_url_raw( $url ), 307 );

		exit;
	}

}