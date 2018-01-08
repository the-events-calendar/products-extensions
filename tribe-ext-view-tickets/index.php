<?php
/**
 * Plugin Name:     Event Tickets Plus Extension: Show and Print Tickets
 * Description:     Adds option for viewing and printing tickets, or downloading them as PDFs. In the admin area hover over an item in the Attendee List and click "View Tickets". Or, a ticket holder can view them from the frontend in their list of tickets for an event.
 * Version:         2.0.1
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
	 * Indicates if the necessary lib and setting is checked for PDF functionality
	 *
	 * @var bool
	 */
	protected $pdf_export_enabled = false;

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Tickets__Main' );
		$this->add_required_plugin( 'Tribe__Tickets_Plus__Main' );

		$this->set_url( 'https://theeventscalendar.com/extensions/show-print-tickets/' );
		$this->set_version( '2.0.1' );
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

		$dkpdf_loaded = defined( 'DKPDF_PLUGIN_DIR' );
		$pdfs_enabled = tribe_get_option( 'tribe_extension_enable_pdf_tickets', false );

		if ( ! $dkpdf_loaded && $pdfs_enabled ) {
			tribe_notice( 'tribe_extension_dkpdf_not_active', array( $this, 'notice_dkpdf_not_active' ) );
		}

		$this->pdf_export_enabled = ( $dkpdf_loaded && $pdfs_enabled );

		add_action( 'wp_loaded', array( $this, 'show_ticket_page' ) );
		add_filter( 'woocommerce_order_actions', array( $this, 'add_woo_view_action' ) );
		add_action( 'woocommerce_order_action_tribe_view_ticket', array( $this, 'forward_woo_action' ) );
		add_action( 'event_tickets_attendees_table_row_actions', array( $this, 'event_tickets_attendees_table_row_actions' ), 0, 2 );
		add_action( 'event_tickets_orders_attendee_contents', array( $this, 'event_tickets_orders_attendee_contents' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'add_settings' ) );

		if ( $this->pdf_export_enabled ) {
			add_action( 'tribe_tickets_ticket_email_ticket_bottom', array( $this, 'in_ticket_pdf_link' ), 100 );
		}
	}

	/**
	 * Adds settings options
	 */
	public function add_settings() {
		if ( ! class_exists( 'Tribe__Extension__Settings_Helper' ) ) {
			require_once dirname( __FILE__ ) . '/src/Tribe/Settings_Helper.php';
		}
		$setting_helper = new Tribe__Extension__Settings_Helper();

		$fields = array(
			'tribe_extension_enable_pdf_tickets' => array(
				'type'            => 'checkbox_bool',
				'label'           => esc_html__( 'Download PDF Tickets', 'tribe-extension' ),
				'tooltip'         => sprintf(
					esc_html__( 'Add a PDF download link to tickets. This feature requires that the %s plugin is installed and activated.', 'tribe-extension' ),
					$this->dkpdf_link()
				),
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
	 * Displays ticket order when the URL contains a view ticket action
	 */
	public function show_ticket_page() {
		if ( ! isset( $_GET[ $this->view_ticket_url_key ] ) ) {
			return;
		}

		// Include the class and functions that power this.
		require_once( 'src/functions/general.php' );
		require_once( 'src/Tribe/Extension/Tickets_Order_Helper2.php' );

		$order_id = intval( $_GET[ $this->view_ticket_url_key ] );
		$is_pdf_ticket = ! empty( $_GET[ $this->pdf_ticket_url_key ] );
		$order_helper = new Tribe__Extension__Tickets_Order_Helper2( $order_id );
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

		if ( empty( $attendees ) ) {
			return;
		}

		if ( $is_pdf_ticket && $this->pdf_export_enabled ) {
			// Prevent the download PDF link from appearing inside the PDF.
			remove_action( 'tribe_tickets_ticket_email_ticket_bottom', array( $this, 'in_ticket_pdf_link' ), 100 );

			$this->output_pdf(
				$ticket_provider->generate_tickets_email_content( $attendees ),
				$order_id
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
			$text = __( 'Download PDF', 'tribe-extenstion' );
		} else {
			$text = __( 'View Ticket', 'tribe-extenstion' );
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
	 * Outputs downloadable PDF to browser
	 *
	 * @param $html
	 */
	function output_pdf( $html, $filename ) {
		// Mute mPDF's many notices.
		@$mpdf = $this->get_mpdf( $html );
		@$mpdf->Output( $filename . '.pdf', 'I' );

		exit;
	}


	/**
	 * Converts HTML to mPDF object
	 *
	 * @param string $html The full HTML you want converted to a PDF
	 *
	 * @return mPDF
	 */
	function get_mpdf( $html ) {
		require_once ( DKPDF_PLUGIN_DIR . 'includes/mpdf60/mpdf.php' );

		// page orientation
		$dkpdf_page_orientation = get_option( 'dkpdf_page_orientation', '' );

		$format = apply_filters( 'dkpdf_pdf_format', 'A4' );

		if ( 'horizontal' === $dkpdf_page_orientation ) {
			$format .= '-L';
		}

		// font size
		$dkpdf_font_size = get_option( 'dkpdf_font_size', '12' );
		$dkpdf_font_family = '';

		// margins
		$dkpdf_margin_left = get_option( 'dkpdf_margin_left', '15' );
		$dkpdf_margin_right = get_option( 'dkpdf_margin_right', '15' );
		$dkpdf_margin_top = get_option( 'dkpdf_margin_top', '50' );
		$dkpdf_margin_bottom = get_option( 'dkpdf_margin_bottom', '30' );
		$dkpdf_margin_header = get_option( 'dkpdf_margin_header', '15' );

		// creating and setting the pdf
		$mpdf = new mPDF(
			'utf-8',
			$format,
			$dkpdf_font_size,
			$dkpdf_font_family,
			$dkpdf_margin_left,
			$dkpdf_margin_right,
			$dkpdf_margin_top,
			$dkpdf_margin_bottom,
			$dkpdf_margin_header
		);

		// Make full UTF-8 charset work.
		$mpdf->useAdobeCJK = true;
		$mpdf->autoScriptToLang = true;
		$mpdf->autoLangToFont = true;

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
			esc_html__( 'Download a PDF copy of this ticket.', 'tribe-extenstion' )
		);
	}


	/**
	 * Outputs the download dkpdf notice
	 */
	public function notice_dkpdf_not_active() {
		$message = sprintf(
			esc_html__( 'The Download PDF feature requires that the %s plugin is downloaded and activated.', 'tribe-extension' ),
			$this->dkpdf_link()
		);

		printf(
			'<div class="error"><p><strong>%1$s</strong> %2$s</p></div>',
			$this->get_name(),
			$message
		);
	}


	/**
	 * Gets an HTML link to dkpdf plugin download page
	 *
	 * @return string
	 */
	protected function dkpdf_link() {
		return '<a href="plugin-install.php?tab=plugin-information&plugin=dk-pdf&TB_iframe=true" class="thickbox">DK PDF</a>';
	}
}
