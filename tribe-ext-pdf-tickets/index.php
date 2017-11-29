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
	 * The custom field name in which to store a ticket's Unique ID.
	 *
	 * For security purposes, We generate a Unique ID to be used as part of the
	 * generated file name. We need to store it in the database for
	 * lookup purposes.
	 *
	 * @var string
	 */
	public $pdf_ticket_meta_key = '_tribe_ext_pdf_tickets_unique_id';

	/**
	 * The query argument key for the Attendee ID.
	 *
	 * @var string
	 */
	public $pdf_unique_id_query_arg_key = 'tribe_ext_pdf_tickets_unique_id';

	/**
	 * The query argument key for retrying loading an attempted PDF.
	 *
	 * @var string
	 */
	public $pdf_retry_url_query_arg_key = 'tribe_ext_pdf_tickets_retry';

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

		/**
		 * Ideally, we would only flush rewrite rules on plugin activation and
		 * deactivation, but we cannot on activation due to the way extensions
		 * get loaded. Therefore, we flush rewrite rules a different way while
		 * plugin is activated. The deactivation hook does work inside the
		 * extension class, though.
		 *
		 * @link https://developer.wordpress.org/reference/functions/flush_rewrite_rules/#comment-597
		 */
		add_action( 'admin_init', array( $this, 'admin_flush_rewrite_rules_if_needed' ) );
		register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
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
	 * mPDF version 7.0+ requires PHP 5.6+ with the mbstring and gd extensions.
	 * Permalinks are required to be set in order to use this plugin. If they
	 * are not set, display an informative admin error with a link to the
	 * Permalink Settings admin screen and do not load the rest of this plugin.
	 */
	public function init() {
		if ( version_compare( PHP_VERSION, '5.6', '<' ) ) {
			$message = '<p>' . $this->get_name();

			$message .= __( ' requires PHP 5.6 or newer to work (as well as the `mbstring` and `gd` PHP extensions). Please contact your website host and inquire about updating PHP.', 'tribe-extension' );

			$message .= '</p>';

			tribe_notice( $this->get_name(), $message, 'type=error' );

			return;
		}

		$permalink_structure = get_option( 'permalink_structure' );
		if ( ! empty( $permalink_structure ) ) {
			// Event Tickets
			add_filter( 'event_tickets_attendees_table_row_actions', array( $this, 'pdf_attendee_table_row_actions' ), 0, 2 );

			add_action( 'event_tickets_orders_attendee_contents', array( $this, 'pdf_attendee_table_row_action_contents' ), 10, 2 );

			add_action( 'event_tickets_rsvp_attendee_created', array( $this, 'do_upload_pdf' ), 50, 1 );

			// Event Tickets Plus: WooCommerce
			add_action( 'event_ticket_woo_attendee_created', array( $this, 'do_upload_pdf' ), 50, 1 );

			// Event Tickets Plus: Easy Digital Downloads
			add_action( 'event_ticket_edd_attendee_created', array( $this, 'do_upload_pdf' ), 50, 1 );

			// Add rewrite rules
			add_action( 'init', array( $this, 'add_pdf_file_rewrite_rules' ) );
			add_action( 'query_vars', array( $this, 'add_custom_query_vars' ) );
			add_action( 'redirect_canonical', array( $this, 'make_non_trailing_slash_the_canonical' ), 10, 2 );

			// For generating a PDF on the fly
			add_action( 'template_redirect', array( $this, 'load_pdf' ) );
		} else {
			if (
				! is_admin()
				|| (
					defined( 'DOING_AJAX' )
					&& DOING_AJAX
				)
			) {
				return;
			}

			global $pagenow; // an Admin global

			$message = '<p style="font-style: italic">';

			$message .= sprintf( esc_html__( 'Permalinks must be enabled in order to use %s.', 'tribe-extension' ), $this->get_name() );

			$message .= '</p>';

			// Do not display link to Permalink Settings page when we are on it.
			if ( 'options-permalink.php' !== $pagenow ) {
				$message .= '<p>';

				$message .= sprintf( '<a href="%s">%s</a>',
					esc_url( admin_url( 'options-permalink.php' ) ),
					__( 'Change your Permalink settings', 'tribe-extension' )
				);

				$message .= __( ' or deactivate this plugin.', 'tribe-extension' );

				$message .= '</p>';
			}

			tribe_notice( $this->get_name(), $message, 'type=error' );
		}

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

		$upload_dir = trailingslashit( $upload_dir['basedir'] );

		/**
		 * Filter to change the path to where PDFs will be created.
		 *
		 * This could be useful if you wanted to tack on 'pdfs/' to put them in
		 * a subdirectory of the Uploads directory.
		 *
		 * @param $upload_dir
		 */
		return apply_filters( 'tribe_ext_pdf_tickets_uploads_dir_path', $upload_dir );
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
	 * The text before the {unique_id}.pdf in the file name.
	 *
	 * Default is "tribe_tickets_"
	 *
	 * @var string
	 *
	 * @return string
	 */
	private function get_file_name_prefix() {
		/**
		 * Filter to change the string before the Unique ID part of the
		 * generated file name.
		 *
		 * @param $prefix
		 */
		$prefix = apply_filters( 'tribe_ext_pdf_tickets_file_name_prefix', 'tribe_tickets_' );

		return (string) $prefix;
	}

	/**
	 * Prepend file name prefix to the Unique ID.
	 *
	 * Example: tribe_tickets_abc123xyz789
	 *
	 * @param $unique_id
	 *
	 * @return string
	 */
	private function combine_prefix_and_unique_id( $unique_id ) {
		return $this->get_file_name_prefix() . $unique_id;
	}


	/**
	 * Full PDF file name on the server.
	 *
	 * Does not include leading server path or URL.
	 * Does include the .pdf file extension.
	 *
	 * @param $attendee_id Ticket Attendee ID.
	 *
	 * @return string
	 */
	protected function get_pdf_name( $attendee_id = 0 ) {
		$unique_id = $this->get_unique_id_from_attendee_id( $attendee_id );

		$name = '';

		if ( ! empty( $unique_id ) ) {
			$name = $this->combine_prefix_and_unique_id( $unique_id ) . '.pdf';
		}

		return $name;
	}

	/**
	 * Get absolute path to the PDF file, including ".pdf" at the end.
	 *
	 * @param $attendee_id
	 *
	 * @return string
	 */
	private function get_pdf_path( $attendee_id ) {
		return $this->uploads_directory_path() . $this->get_pdf_name( $attendee_id );
	}

	/**
	 * Get the Unique ID for the given Attendee ID.
	 *
	 * Lookup Unique ID in the database. If it does not exist yet, generate it
	 * and save it to the database for future lookups.
	 *
	 * @param int $attendee_id
	 *
	 * @return string
	 */
	private function get_unique_id_from_attendee_id( $attendee_id ) {
		$unique_id = get_post_meta( $attendee_id, $this->pdf_ticket_meta_key, true );

		if ( empty( $unique_id ) ) {
			$unique_id = uniqid( '', true );

			// uniqid() with more_entropy results in something like '59dfc07503b009.71316471'
			$unique_id = str_replace( '.', '', $unique_id );

			/**
			 * Filter to customize the Unique ID part of the generated PDF file name.
			 *
			 * If you use this filter, you may also need to use the
			 * tribe_ext_pdf_tickets_unique_id_regex filter.
			 *
			 * @param $unique_id
			 * @param $attendee_id
			 */
			$unique_id = apply_filters( 'tribe_ext_pdf_tickets_unique_id', $unique_id, $attendee_id );

			$unique_id = sanitize_file_name( $unique_id );

			add_post_meta( $attendee_id, $this->pdf_ticket_meta_key, $unique_id, true );
		}

		return $unique_id;
	}

	/**
	 * @param $unique_id
	 *
	 * @return int
	 */
	private function get_attendee_id_from_unique_id( $unique_id ) {
		$args = array(
			// cannot use 'post_type' => 'any' because these post types have `exclude_from_search` set to TRUE (because `public` is FALSE)
			'post_type'      => array(
				Tribe__Tickets__RSVP::ATTENDEE_OBJECT,
				Tribe__Tickets_Plus__Commerce__WooCommerce__Main::ATTENDEE_OBJECT,
				Tribe__Tickets_Plus__Commerce__EDD__Main::ATTENDEE_OBJECT,
			),
			'nopaging'       => true,
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'   => $this->pdf_ticket_meta_key,
					'value' => $unique_id,
				)
			)
		);

		$attendee_id_array = get_posts( $args );

		if ( empty( $attendee_id_array[0] ) ) {
			$attendee_id = 0;
		} else {
			$attendee_id = $attendee_id_array[0];
		}

		return $attendee_id;
	}

	/**
	 * Get the true full URL to the PDF file, including ".pdf" at the end.
	 *
	 * Example result: http://example.com/wp-content/uploads/tribe_tickets_{unique_id}.pdf
	 *
	 * @param $attendee_id
	 *
	 * @return string
	 */
	private function get_direct_pdf_url( $unique_id ) {
		$attendee_id = $this->get_attendee_id_from_unique_id( $unique_id );

		$file_url = $this->uploads_directory_url() . $this->get_pdf_name( $attendee_id );

		return esc_url( $file_url );
	}

	/**
	 * The URL rewrite base for the file download.
	 *
	 * Example: tickets_download
	 *
	 * @return string
	 */
	private function get_download_base_slug() {
		$tickets_bases = Tribe__Tickets__Tickets_View::instance()->add_rewrite_base_slug();

		$base = sprintf( '%s_%s',
			sanitize_title_with_dashes( $tickets_bases['tickets'][0] ),
			sanitize_key( __( 'download', 'tribe-extension' ) )
		);

		return $base;
	}

	/**
	 * Get the public-facing URL to the PDF file.
	 *
	 * Example: http://example.com/tickets_download/{unique_id}
	 *
	 * @param $attendee_id
	 *
	 * @return string
	 */
	private function get_pdf_link( $attendee_id ) {
		$unique_id = $this->get_unique_id_from_attendee_id( $attendee_id );

		$url = home_url( '/' ) . $this->get_download_base_slug();

		$url = trailingslashit( $url ) . $unique_id;

		return esc_url( $url );
	}

	/**
	 * The regex to determine if a string is in the proper format to be a
	 * Unique ID in the context of this extension.
	 *
	 * @return string
	 */
	protected function get_unique_id_regex() {
		/**
		 * Filter to adapt the regex for matching Unique ID.
		 *
		 * Use in conjunction with the tribe_ext_pdf_tickets_unique_id filter.
		 *
		 * @param $regex_pattern
		 */
		$unique_id_regex = apply_filters( 'tribe_ext_pdf_tickets_unique_id_regex', '[a-z0-9]{1,}' );

		return (string) $unique_id_regex;
	}

	/**
	 * Regex for the file download rewrite rule.
	 *
	 * example.com/tickets_download/{unique_id} (without trailing slash)
	 *
	 * @return string
	 */
	protected function get_file_rewrite_regex() {
		$regex_for_file = sprintf( '^%s/(%s)[/]?$', $this->get_download_base_slug(), $this->get_unique_id_regex() );

		return $regex_for_file;
	}

	/**
	 * Add the needed WordPress rewrite rules.
	 *
	 * example.com/tickets_download/{unique_id} (without trailing slash) goes
	 * to the PDF file, and
	 * example.com/tickets_download/ (with or without trailing slash) goes to
	 * the site's homepage for the sake of search engines or curious users
	 * exploring hackable URLs.
	 */
	public function add_pdf_file_rewrite_rules() {
		$query_for_file = sprintf( 'index.php?%s=$matches[1]', $this->pdf_unique_id_query_arg_key );

		add_rewrite_rule( $this->get_file_rewrite_regex(), $query_for_file, 'top' );

		// example.com/tickets_download/ (optional trailing slash) to home page
		add_rewrite_rule( '^' . $this->get_download_base_slug() . '[/]?$', 'index.php', 'top' );
	}

	/**
	 * Add the needed WordPress query variable to get the Unique ID.
	 *
	 * @param $vars
	 *
	 * @return array
	 */
	public function add_custom_query_vars( $vars ) {
		$vars[] = $this->pdf_unique_id_query_arg_key;

		return $vars;
	}

	/**
	 * Disable WordPress trying to add a trailing slash to our PDF file URLs.
	 *
	 * Example: http://example.com/tickets_download/{unique_id}
	 * Without the leading ^ because we are comparing against the full URL,
	 * not creating a rewrite rule. Without the ending $ because we might have a
	 * URL query string.
	 *
	 * @param $redirect_url  The URL with a trailing slash added (in most
	 *                       setups).
	 * @param $requested_url Our unmodified URL--without a trailing slash.
	 *
	 * @return bool|string
	 */
	public function make_non_trailing_slash_the_canonical( $redirect_url, $requested_url ) {
		$pattern_wo_slash = sprintf( '/\/%s\/(%s)/', $this->get_download_base_slug(), $this->get_unique_id_regex() );

		if ( preg_match( $pattern_wo_slash, $requested_url ) ) {
			return false;
		}

		return $redirect_url;
	}


	/**
	 * Ideally, we would only flush rewrite rules on plugin activation, but we
	 * cannot use register_activation_hook() due to the timing of when
	 * extensions load. Therefore, we flush rewrite rules on every visit to the
	 * wp-admin Plugins screen (where we'd expect you to be if you just
	 * activated a plugin)... only if our rewrite rule is not already in the
	 * rewrite rules array.
	 */
	public function admin_flush_rewrite_rules_if_needed() {
		global $pagenow;

		if ( 'plugins.php' !== $pagenow ) {
			return;
		}

		$rewrite_rules = get_option( 'rewrite_rules' );

		if ( empty( $rewrite_rules ) ) {
			return;
		}

		if ( ! array_key_exists( $this->get_file_rewrite_regex(), $rewrite_rules ) ) {
			$this->add_pdf_file_rewrite_rules();

			flush_rewrite_rules();
		}
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
		 * @param $ticket_class
		 * @param $event_id
		 * @param $attendee_id
		 * @param $file_name
		 */
		do_action( 'tribe_ext_pdf_tickets_uploaded_pdf', $ticket_class, $event_id, $attendee_id, $file_name );

		/**
		 * Filter to disable PDF email attachments, either entirely (just pass
		 * false) or per event, attendee, ticket type, or some other logic.
		 *
		 * @param $email
		 * @param $ticket_class
		 * @param $event_id
		 * @param $attendee_id
		 * @param $file_name
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

				/**
				 * Action hook that fires during email attaching but only for
				 * unknown ticket types.
				 *
				 * @param $ticket_class
				 * @param $event_id
				 * @param $attendee_id
				 * @param $file_name
				 */
				do_action( 'tribe_ext_pdf_tickets_uploaded_to_email_unknown_ticket_type', $ticket_class, $event_id, $attendee_id, $file_name );
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
			esc_url( $this->get_pdf_link( $attendee_id ) )
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
	public function pdf_attendee_table_row_actions( $row_actions, $item ) {
		$row_actions[] = $this->ticket_link( $item['attendee_id'] );

		return $row_actions;
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
		require_once( __DIR__ . '/vendor/autoload.php' );

		// to avoid this fatal error: https://github.com/mpdf/mpdf/issues/524
		$html = str_ireplace( ' !important', '', $html );

		/**
		 * Creating and setting the PDF
		 *
		 * Reference vendor/mpdf/config.php, especially since it may not
		 * match the documentation.
		 * 'c' mode sets the mPDF Mode to use onlyCoreFonts so that we do not
		 * need to include any fonts (like the dejavu... ones) in
		 * vendor/mpdf/mpdf/ttfonts
		 * Therefore, this entire ttfonts directory is non-existent in the .zip
		 * build via Composer, which changes saves over 90 MB disk space
		 * unzipped and the .zip itself goes from over 40 MB to under 3 MB.
		 *
		 * @link https://mpdf.github.io/reference/mpdf-variables/overview.html
		 * @link https://github.com/mpdf/mpdf/pull/490
		 */
		$mpdf = new \Mpdf\Mpdf( array( 'mode' => 'c' ) );

		$mpdf->WriteHTML( $html );

		return $mpdf;
	}

	/**
	 * Tell WordPress to 404 instead of continuing loading the template it would
	 * otherwise load, such as matching lower-priority rewrite rule matches
	 * (e.g. page or attachment).
	 */
	private function force_404() {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
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
	public function load_pdf() {
		// Must use get_query_var() because of working with WordPress' internal (private) rewrites, and tribe_get_request_var() can only get the $_GET superglobal.
		$unique_id = get_query_var( $this->pdf_unique_id_query_arg_key );

		if ( empty( $unique_id ) ) {
			// do not force 404 at this point
			return;
		}

		$attendee_id = $this->get_attendee_id_from_unique_id( $unique_id );

		if ( empty( $attendee_id ) ) {
			$this->force_404();

			return;
		} else {
			// if we have an Attendee ID but the URL ends with a backslash (wouldn't happen if we already redirected to create and retry), then redirect to version without trailing slash (for canonical purposes). Does not intercept if manually adding an unexpected query var but that's not a worry since this is already unlikely and just for canonical purposes.
			if ( '/' === substr( $_SERVER['REQUEST_URI'], - 1, 1 ) ) {
				$url = rtrim( $_SERVER['REQUEST_URI'], '/' );

				wp_redirect( esc_url_raw( $url ), 301 ); // Moved Permanently
				exit;
			}
		}

		$file_name = $this->get_pdf_path( $attendee_id );

		if ( empty( $file_name ) ) {
			$this->force_404();

			return;
		}

		if ( file_exists( $file_name ) ) {
			header( 'Content-Type: application/pdf', true );

			header( "X-Robots-Tag: none", true );

			// inline tells the browser to display, not download, but some browsers (or browser settings) will always force downloading
			$disposition = sprintf( 'Content-Disposition: inline; filename="%s"', $this->get_pdf_name( $attendee_id ) );
			header( $disposition, true );

			// Optional but enables keeping track of the download progress and detecting if the download was interrupted
			header( 'Content-Length: ' . filesize( $file_name ), true );

			readfile( $file_name );
			exit;
		}


		// only retry once
		$retry_query_var = get_query_var( $this->pdf_retry_url_query_arg_key );
		if ( ! empty( $retry_query_var ) ) {
			$this->force_404();

			return;
		} else {
			$created_pdf = $this->do_upload_pdf( $attendee_id, false );

			if ( false === $created_pdf ) {
				$this->force_404();

				return;
			} else {
				/**
				 * Redirect to retrying reloading the PDF.
				 *
				 * Cache buster and technically a new URL so status code 307
				 * Temporary Redirect applies.
				 *
				 * @link https://en.wikipedia.org/wiki/List_of_HTTP_status_codes#3xx_Redirection
				 */
				$url = add_query_arg( $this->pdf_retry_url_query_arg_key, time(), $this->get_pdf_link( $attendee_id ) );

				wp_redirect( esc_url_raw( $url ), 307 );

				exit;
			}
		}
	}

}