<?php
/**
 * Plugin Name:     Event Tickets Plus Extension: Show and Print Tickets
 * Description:     Adds options for viewing and printing tickets, downloading them as PDFs, and/or attaching PDF tickets to ticket emails. In the admin area, hover over an item in the Attendee List and click "View Tickets". Or a ticket holder can view them from the frontend in their list of tickets for an event. PDFs must be enabled via the settings at wp-admin > Events > Settings > Tickets tab.
 * Version:         2.1.0
 * Extension Class: Tribe__Extension__View_Print_Tickets
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
class Tribe__Extension__View_Print_Tickets extends Tribe__Extension {

	/**
	 * URL key for specifying a order number
	 *
	 * @var string
	 */
	public $view_ticket_url_key = 'tribe_view_ticket_order';

	/**
	 * URL key for specifying a order number
	 *
	 * @var string
	 */
	public $pdf_ticket_url_key = 'tribe_pdf_ticket';

	/**
	 * Indicates if the necessary lib and setting is checked for PDF view functionality.
	 *
	 * @var bool
	 */
	protected $pdf_export_enabled = false;

	/**
	 * Indicates if the necessary lib and setting is checked for PDF email attachments functionality.
	 *
	 * @var bool
	 */
	protected $pdf_attach_enabled = false;

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
		$this->add_required_plugin( 'Tribe__Tickets_Plus__Main', '4.5.0.1' );

		$this->set_url( 'https://theeventscalendar.com/extensions/show-print-tickets/' );
		$this->set_version( '2.1.0' );
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

		$this->pdf_export_enabled = tribe_get_option( 'tribe_extension_enable_pdf_tickets', false );
		$this->pdf_attach_enabled = tribe_get_option( 'tribe_extension_enable_pdf_tickets_attach', false );

		add_action( 'wp_loaded', array( $this, 'show_ticket_page' ) );
		add_filter( 'woocommerce_order_actions', array( $this, 'add_woo_view_action' ) );
		add_action( 'woocommerce_order_action_tribe_view_ticket', array( $this, 'forward_woo_action' ) );
		add_action( 'event_tickets_attendees_table_row_actions', array(
			$this,
			'event_tickets_attendees_table_row_actions'
		), 0, 2 );
		add_action( 'event_tickets_orders_attendee_contents', array(
			$this,
			'event_tickets_orders_attendee_contents'
		), 10, 2 );
		add_action( 'admin_init', array( $this, 'add_settings' ) );

		add_action( 'event_tickets_rsvp_attendee_created', array( $this, 'upload_pdf_rsvp' ), 50, 2 );
		add_action( 'event_ticket_woo_attendee_created', array( $this, 'upload_pdf_woo' ), 50, 2 );
		add_action( 'event_ticket_edd_attendee_created', array( $this, 'upload_pdf_edd' ), 50, 2 );
	}

	/**
	 * Adds settings options
	 */
	public function add_settings() {
		require_once dirname( __FILE__ ) . '/src/Tribe/Extension/Settings_Helper.php';
		$setting_helper = new Tribe__Extension__Settings_Helper();

		$fields = array(
			'tribe_extension_enable_pdf_tickets'        => array(
				'type'            => 'checkbox_bool',
				'label'           => esc_html__( 'Download PDF Tickets', 'tribe-extension' ),
				'tooltip'         => esc_html__( 'Add a PDF download link to tickets.', 'tribe-extension' ),
				'default'         => false,
				'validation_type' => 'boolean',
			),
			'tribe_extension_enable_pdf_tickets_attach' => array(
				'type'            => 'checkbox_bool',
				'label'           => esc_html__( 'Attach ticket PDFs to ticket emails', 'tribe-extension' ),
				'tooltip'         => esc_html__( 'If checked, the PDF ticket will be attached to ticket emails.', 'tribe-extension' ),
				'default'         => false,
				'validation_type' => 'boolean',
			),
		);

		$setting_helper->add_fields(
			$fields,
			'event-tickets',
			'ticket-commerce-form-location'
		);
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
	 * whether it is in the default location or whether an UPLOADS constant has
	 * been defined to specify an alternate location. The path it returns will
	 * look something like /path/to/wordpress/wp-content/uploads/ regardless
	 * of the current month we are in.
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
	 * whether it is in the default location or whether an UPLOADS constant has
	 * been defined to specify an alternate location. The URL it returns will
	 * look something like http://example.com/wp-content/uploads/ regardless
	 * of the current month we are in.
	 *
	 * @return string The uploads directory URL.
	 */
	protected function uploads_directory_url() {
		$upload_dir = wp_upload_dir();

		return trailingslashit( $upload_dir['baseurl'] );
	}

	/**
	 * PDF file name without full server path.
	 *
	 * Naming convention for this extension's PDFs.
	 *
	 * @param      $post_id     Event or other Post Type post ID
	 * @param      $ticket_type RSVP, Woo, EDD, or other to prefix with
	 * @param      $attendee_id Ticket Attendee ID
	 *
	 * @return bool|string
	 */
	protected function get_pdf_name( $post_id, $ticket_type, $attendee_id ) {
		if (
			empty( $post_id )
			|| empty( $ticket_type )
			|| empty( $attendee_id )
		) {
			return false;
		}

		$file_name = sprintf( 'et_%d_%s_%d',
			$post_id,
			strtolower( $ticket_type ),
			$attendee_id
		);

		$file_name = sprintf( '%s_%s', $file_name, md5( $file_name ) );

		$file_name = substr( $file_name, 0, 50 );

		$file_name = apply_filters( 'tribe_ext_view_tickets_get_pdf_name', $file_name, $ticket_type, $post_id, $attendee_id );

		$file_name .= '.pdf';

		// remove all whitespace from file name
		$file_name = preg_replace( '/\s+/', '', $file_name );

		return $file_name;
	}

	/**
	 * RSVPs: Creates ticket PDF and saves to WP's uploads directory.
	 *
	 * @since 2.1.0
	 *
	 * @param $attendee_id ID of attendee ticket
	 * @param $post_id     ID of event
	 *
	 * @return bool
	 */
	public function upload_pdf_rsvp( $attendee_id, $post_id ) {

		$successful = false;

		if ( false === $this->pdf_attach_enabled ) {
			return $successful;
		}

		if ( ! class_exists( 'Tribe__Tickets__RSVP' ) ) {
			return $successful;
		}

		$ticket_instance = Tribe__Tickets__RSVP::get_instance();

		if ( empty( $ticket_instance ) ) {
			return $successful;
		}

		$attendees = $ticket_instance->get_attendees_array( $post_id );

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

		$html = $ticket_instance->generate_tickets_email_content( $attendees_array );

		if ( empty( $html ) ) {
			return $successful;
		}

		$file_name = $this->uploads_directory_path() . $this->get_pdf_name( $post_id, 'rsvp', $attendee_id );

		if ( empty( $file_name ) ) {
			return $successful;
		}

		if ( file_exists( $file_name ) ) {
			$successful = true;
		} else {
			$this->output_pdf( $html, $file_name );

			$successful = true;
		}

		if ( true === $successful ) {
			$this->attachments_array[] = $file_name;
			add_filter( 'tribe_rsvp_email_attachments', array( $this, 'email_attach_pdf_rsvp' ), 10, 1 );
		}

		return $successful;
	}

	/**
	 * RSVPs: Attach the PDF to the ticket email
	 *
	 * @param $attachments
	 *
	 * @return array
	 */
	public function email_attach_pdf_rsvp( $attachments ) {
		$attachments = array_merge( $attachments, $this->attachments_array );

		$attachments = array_unique( $attachments );

		return $attachments;
	}

	/**
	 * WooCommerce Tickets: Creates ticket PDF and saves to WP's uploads directory.
	 *
	 * @since 2.1.0
	 *
	 * @param $attendee_id ID of attendee ticket
	 * @param $event_id    ID of event
	 * @param $order       WooCommerce order
	 * @param $product_id  WooCommerce product ID
	 *
	 * @return bool
	 */
	public function upload_pdf_woo( $attendee_id, $event_id ) {

		$successful = false;

		if ( false === $this->pdf_attach_enabled ) {
			return $successful;
		}

		if ( ! class_exists( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' ) ) {
			return $successful;
		}

		$ticket_instance = Tribe__Tickets_Plus__Commerce__WooCommerce__Main::get_instance();

		if ( empty( $ticket_instance ) ) {
			return $successful;
		}

		$attendees = $ticket_instance->get_attendees_by_id( $attendee_id );

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

		$html = $ticket_instance->generate_tickets_email_content( $attendees_array );

		if ( empty( $html ) ) {
			return $successful;
		}

		$file_name = $this->uploads_directory_path() . $this->get_pdf_name( $event_id, 'woo', $attendee_id );

		if ( empty( $file_name ) ) {
			return $successful;
		}

		if ( file_exists( $file_name ) ) {
			$successful = true;
		} else {
			$this->output_pdf( $html, $file_name );

			$successful = true;
		}

		if ( true === $successful ) {
			$this->attachments_array[] = $file_name;
			add_filter( 'woocommerce_email_attachments', array( $this, 'email_attach_pdf_woo' ), 10, 1 );
		}

		return $successful;
	}

	/**
	 * WooCommerce Tickets: Attach the PDF to the ticket email
	 *
	 * @param $attachments
	 *
	 * @return array
	 */
	public function email_attach_pdf_woo( $attachments ) {
		$attachments = array_merge( $attachments, $this->attachments_array );

		$attachments = array_unique( $attachments );

		return $attachments;
	}

	/**
	 * Easy Digital Downloads Tickets: Creates ticket PDF and saves to WP's uploads directory.
	 *
	 * @since 2.1.0
	 *
	 * @param $attendee_id ID of attendee ticket
	 * @param $event_id    ID of event
	 *
	 * @return bool
	 */
	public function upload_pdf_edd( $attendee_id, $event_id ) {

		$successful = false;

		if ( false === $this->pdf_attach_enabled ) {
			return $successful;
		}

		if ( ! class_exists( 'Tribe__Tickets_Plus__Commerce__EDD__Main' ) ) {
			return $successful;
		}

		$ticket_instance = Tribe__Tickets_Plus__Commerce__EDD__Main::get_instance();

		if ( empty( $ticket_instance ) ) {
			return $successful;
		}

		$attendees = $ticket_instance->get_attendees_by_id( $attendee_id );

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

		$html = $ticket_instance->generate_tickets_email_content( $attendees_array );

		if ( empty( $html ) ) {
			return $successful;
		}

		$file_name = $this->uploads_directory_path() . $this->get_pdf_name( $event_id, 'edd', $attendee_id );

		if ( empty( $file_name ) ) {
			return $successful;
		}

		if ( file_exists( $file_name ) ) {
			$successful = true;
		} else {
			$this->output_pdf( $html, $file_name );

			$successful = true;
		}

		if ( true === $successful ) {
			$this->attachments_array[] = $file_name;
			add_filter( 'edd_ticket_receipt_attachments', array( $this, 'email_attach_pdf_edd' ), 10, 1 );
		}

		return $successful;
	}

	/**
	 * Easy Digital Downloads Tickets: Attach the PDF to the ticket email
	 *
	 * @param $attachments
	 *
	 * @return array
	 */
	public function email_attach_pdf_edd( $attachments ) {
		$attachments = array_merge( $attachments, $this->attachments_array );

		$attachments = array_unique( $attachments );

		return $attachments;
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
		require_once( 'src/Tribe/Extension/Tickets_Order_Helper2.php' );

		$order_id        = intval( $_GET[ $this->view_ticket_url_key ] );
		$is_pdf_ticket   = ! empty( $_GET[ $this->pdf_ticket_url_key ] );
		$order_helper    = new Tribe__Extension__Tickets_Order_Helper2( $order_id );
		$ticket_provider = $order_helper->get_provider_instance();

		if ( empty( $ticket_provider ) ) {
			return;
		}

		$attendees    = $order_helper->get_attendees();
		$holder_email = isset( $attendees[0]['purchaser_email'] ) ? $attendees[0]['purchaser_email'] : null;
		$current_user = wp_get_current_user();

		// Stop users from viewing tickets unless they're admins or purchased them.
		if ( ! current_user_can( 'edit_others_tribe_events' ) && $holder_email !== $current_user->user_email ) {
			return;
		}

		if ( empty( $attendees ) ) {
			return;
		}

		if ( $is_pdf_ticket && $this->pdf_export_enabled ) {
			// Prevent the download PDF link from appearing inside the PDF.
			// TODO - remove?
			// remove_action( 'tribe_tickets_ticket_email_ticket_bottom', array( $this, 'in_ticket_pdf_link' ), 100 );

			$this->output_pdf(
				$ticket_provider->generate_tickets_email_content( $attendees ),
				$order_id,
				'I'
			);
		} else {
			echo $ticket_provider->generate_tickets_email_content( $attendees );
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

		if ( $this->pdf_export_enabled ) {
			echo ' | ' . $this->get_ticket_link( $attendee['order_id'], true );
		}
	}


	/**
	 * Adds view ticket link to backend Attendee List
	 *
	 * @see event_tickets_attendees_table_row_actions
	 */
	public function event_tickets_attendees_table_row_actions( $actions, $order_item ) {
		$actions[] = $this->get_ticket_link( $order_item['order_id'] );

		if ( $this->pdf_export_enabled ) {
			$actions[] = $this->get_ticket_link( $order_item['order_id'], true );
		}

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
		wp_redirect( $this->get_view_ticket_url( $order->id ) );
		exit;
	}


	/**
	 * Get a link to the ticket view
	 *
	 * @param int  $order_id     The order ID
	 * @param bool $download_pdf Link to PDF download page
	 *
	 * @return string The HTML link element
	 */
	public function get_ticket_link( $order_id, $download_pdf = false ) {
		$url = $this->get_view_ticket_url( $order_id, $download_pdf );

		if ( $download_pdf ) {
			$text = __( 'Download PDF', 'tribe-extension' );
		} else {
			$text = __( 'View Ticket', 'tribe-extension' );
		}

		$output = sprintf(
			'<a href="%1$s" class="tribe-view-ticket-link">%2$s</a>',
			esc_attr( $url ),
			esc_html( $text )

		);

		return $output;
	}


	/**
	 * Gets the view ticket URL
	 *
	 * @param int  $order_id     The order ID
	 * @param bool $download_pdf Link to PDF download page
	 *
	 * @return string URL
	 */
	public function get_view_ticket_url( $order_id, $download_pdf = false ) {
		$query_args = array( $this->view_ticket_url_key => $order_id );

		if ( $this->pdf_export_enabled && $download_pdf ) {
			$query_args[ $this->pdf_ticket_url_key ] = 'true';
		}

		$url = add_query_arg( $query_args, trailingslashit( home_url() ) );

		return $url;
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
		//exit; // TODO: if we have exit here, the PDF ticket to the server only does the first attendee, and so we can't have it here -- but why was it ever added?
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
		$mpdf = new mPDF(
			'UTF-8',
			'LETTER', // default is A4
			'12' // I think default is 9
		);

		// Make full UTF-8 charset work.
		$mpdf->useAdobeCJK      = true;
		$mpdf->autoScriptToLang = true;
		$mpdf->autoLangToFont   = true;

		$mpdf->WriteHTML( $html );

		return $mpdf;
	}

	/**
	 * @see tribe_tickets_ticket_email_ticket_bottom
	 */
	public function in_ticket_pdf_link( $ticket ) {
		$url = $this->get_view_ticket_url( $ticket['qr_ticket_id'], true );

		printf(
			'<center><p style="text-align:center;"><a href="%1$s" class="tribe-view-ticket-link">%2$s</a></p></center>',
			esc_attr( $url ),
			esc_html__( 'Download a PDF copy of this ticket.', 'tribe-extension' )
		);
	}

}
