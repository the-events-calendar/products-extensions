<?php
/**
* Plugin Name: Eventbrite Tickets Extension: Additional Options
* Description: Adds a new Eventbrite options section to the bottom of wp-admin > Events > Settings > Imports tab. Options include text above or below iframe, iframe height, moving ticket location on Single Event view, displaying tickets for Private Eventbrite events, change API URL (e.g. from .com to .co.uk), and more.
* Version: 1.0
* Extension Class: Tribe__Extension__Eventbrite_Addl_Opts
* Author: Modern Tribe, Inc.
* Author URI: http://m.tri.be/1971
* License: GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
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
class Tribe__Extension__Eventbrite_Addl_Opts extends Tribe__Extension {

	protected $opts_prefix = 'tribe_ext_eventbrite_opts_';

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 *
	 * @access protected
	 */
	protected function construct() {
		// Each plugin required by this extension
		$this->add_required_plugin( 'Tribe__Events__Main', '4.4' ); // due to using tribe() from Tribe-Common as of 4.4
		$this->add_required_plugin( 'Tribe__Events__Tickets__Eventbrite__Main', '4.4' ); // just to match version of Tribe__Events__Main

		// Set the extension's TEC URL
		$this->set_url( 'https://theeventscalendar.com/extensions/eventbrite-additional-options/' );
	}

	/**
	 * Adds settings options
	 */
	public function add_settings() {
		require_once dirname( __FILE__ ) . '/src/Tribe/Settings_Helper.php';

		$setting_helper = new Tribe__Extension__Settings_Helper();

		$fields = array(
			$this->opts_prefix . 'heading' => array(
				'type' => 'html',
				'html' => '<h3>' . esc_html__( 'Eventbrite Additional Options', 'tribe-extension' ) . '</h3>',
			),
			$this->opts_prefix . 'helper_text' => array(
				'type' => 'html',
				'html' => '<p>' . esc_html__( 'The following fields are from an Extension. These additional Eventbrite options allow you to change how the Eventbrite Tickets add-on operates, such as altering the API call, changing the imported data, or how the event is displayed on your site.', 'tribe-extension' ) . '</p>',
			),

			$this->opts_prefix . 'content_before' => array(
				'type'				=> 'text',
				'label'				=> esc_html__( '<h3> text to display Before/Above iframe', 'tribe-extension' ),
				'tooltip'			=> esc_html__( 'By default there is no content before the iframe. You might want to try something like "Eventbrite Tickets". It will be output as an <h3> heading with "tribe_ext_eventbrite_opts" class.', 'tribe-extension' ),
				'validation_type'	=> 'textarea', // runs wp_kses()
			),

			$this->opts_prefix . 'content_after' => array(
				'type'				=> 'text',
				'label'				=> esc_html__( '<span> text to display After/Below iframe', 'tribe-extension' ),
				'tooltip'			=> esc_html__( 'By default there is no content after the iframe. It will be output as a <span> with "tribe_ext_eventbrite_opts" class.', 'tribe-extension' ),
				'validation_type'	=> 'textarea', // runs wp_kses()
			),

			// see https://central.tri.be/issues/62402
			$this->opts_prefix . 'iframe_px' => array(
				'type'				=> 'text',
				'label'				=> esc_html__( 'Display Tickets iframe Height (pixels)', 'tribe-extension' ),
				'default'			=> 200, // required else positive_int validation will throw an error each time settings are saved if this field is left blank
				'tooltip'			=> esc_html__( 'The default is 200.', 'tribe-extension' ),
				'validation_type'	=> 'positive_int',
			),

			$this->opts_prefix . 'locale' => array(
				'type'				=> 'dropdown',
				'label'				=> esc_html__( 'Eventbrite Locale', 'tribe-extension' ),
				'default'			=> 'en_US',
				'tooltip'			=> sprintf( esc_html__( 'This affects the API call. The default is %sUnited States (en_US)%s.', 'tribe-extension' ), '<strong>', '</strong>' ),
				'validation_type'	=> 'options',
				'options'			=> $this->eb_locale_country_name_array(),
			),

			$this->opts_prefix . 'ticket_single_location' => array(
				'type'				=> 'dropdown',
				'label'				=> esc_html__( 'Single Event Display Location', 'tribe-extension' ),
				'default'			=> 'tribe_events_single_event_after_the_meta',
				'tooltip'			=> sprintf( esc_html__( 'Where should the Eventbrite Tickets iframe display on your Single Events pages? The default is %sAfter the Meta%s.', 'tribe-extension' ), '<strong>', '</strong>' ),
				'validation_type'	=> 'options',
				'options'			=> $this->single_event_display_locations_array(),
			),

			$this->opts_prefix . 'show_admin_cost_field' => array(
				'type'				=> 'checkbox_bool',
				'label'				=> esc_html__( 'Display The Events Calendar\'s "Event Cost" field.', 'tribe-extension' ),
				'tooltip'			=> esc_html__( 'By default, The Events Calendar\'s "Event Cost" field is hidden when Eventbrite Tickets or Event Tickets Plus is active. Check this box to re-enable it.', 'tribe-extension' ),
				'validation_type'	=> 'boolean',
			),

			$this->opts_prefix . 'show_tickets_private_events' => array(
				'type'				=> 'checkbox_bool',
				'label'				=> esc_html__( 'Display Tickets for Private Events', 'tribe-extension' ),
				'tooltip'			=> esc_html__( 'By default, Eventbrite Tickets only displays tickets for events marked Public on Eventbrite. Check this box to also display tickets for the Private events that you have imported.', 'tribe-extension' ),
				'validation_type'	=> 'boolean',
			),

		);

		$setting_helper->add_fields(
			$fields,
			'imports',
			'tribe_aggregator_disable_header', // if Event Aggregator is enabled, below Meetup, else below CSV
			true
		);
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'add_settings' ) );

		$before = tribe_get_option( $this->opts_prefix . 'content_after' );
		if ( ! empty( $before ) ) {
			add_filter( 'tribe_events_eventbrite_before_the_tickets', array( $this, 'before_iframe' ) );
		}

		$after = tribe_get_option( $this->opts_prefix . 'content_after' );
		if ( ! empty( $after ) ) {
			add_filter( 'tribe_events_eventbrite_after_the_tickets', array( $this, 'after_iframe' ) );
		}

		$show_tickets_private_events = tribe_get_option( $this->opts_prefix . 'show_tickets_private_events' );
		if ( ! empty( $show_tickets_private_events ) ) {
			add_filter( 'tribe_events_eventbrite_the_tickets', array( $this, 'render_tickets_iframe_private_eb_events' ) );
		}

		add_filter( 'tribe_events_eb_iframe_html', array( $this, 'iframe_height' ), 10, 3 );

		if ( 'en_US' !== $this->get_chosen_eb_locale() ) {
			add_filter( 'tribe-eventbrite-base_api_url', array( $this, 'api_url' ) );

			add_filter( 'tribe_eb_api_sync_event', array( $this, 'eb_currency_for_update_create' ), 10, 5 );
		}

		// originally from https://theeventscalendar.com/knowledgebase/use-the-event-cost-field-with-tickets-installed/
		// cannot use https://github.com/moderntribe/products-extensions/blob/master/tribe-ext-show-cost-field/index.php because that Extension requires Event Tickets Plus active
		$show_admin_cost_field = tribe_get_option( $this->opts_prefix . 'show_admin_cost_field' );
		if ( ! empty( $show_admin_cost_field ) ) {
			add_filter( 'tribe_events_admin_show_cost_field', '__return_true', 100 );
		}

		// originally from https://theeventscalendar.com/knowledgebase/moving-the-eventbrite-tickets-box/
		$eb_new_ticket_location = tribe_get_option( $this->opts_prefix . 'ticket_single_location' );
		$eb_ticket_default_location = 'tribe_events_single_event_after_the_meta';
		if ( ! empty( $eb_new_ticket_location ) && $eb_ticket_default_location !== $eb_new_ticket_location ) {
			$display_tickets = array( tribe( 'eventbrite.main' ), 'print_ticket_form' );
			remove_action( $eb_ticket_default_location, $display_tickets, 9 );
			add_action( $eb_new_ticket_location, $display_tickets );
		}
	}

	public function before_iframe() {
		$text = tribe_get_option( $this->opts_prefix . 'content_before' );

		$text = sprintf( '<h3 class="tribe_ext_eventbrite_opts">%s</h3>', $text );

		return $text;
	}

	public function after_iframe() {
		$text = tribe_get_option( $this->opts_prefix . 'content_after' );

		$text = sprintf( '<span class="tribe_ext_eventbrite_opts">%s</span>', $text );

		return $text;
	}

	// originally from https://theeventscalendar.com/knowledgebase/changing-the-height-of-the-eventbrite-tickets-iframe/
	public function iframe_height( $html, $event_id, $post_id ) {
		// should only be a positive integer, per validation
		$new_height = intval( tribe_get_option( $this->opts_prefix . 'iframe_px' ) );

		if ( 200 === $new_height ) {
			return $html;
		}

		if ( empty( $new_height ) || false === strpos( $html, 'eventbrite-ticket-embed' ) ) {
			return $html;
		}

		$replace_this = 'style="height:200px;';

		$replace_with = sprintf( 'style="height: %dpx;', $new_height ); // integers only

		$new_html = str_replace( $replace_this, $replace_with, $html );

		return $new_html;
	}

//	public function eb_credit_link_to_locale( $html, $event_id, $post_id ) {
//
//		$replace_with = sprintf( 'eventbrite%s', $this->get_eb_tld() );
//
//		$new_html = str_replace( 'eventbrite.com', $replace_with, $html );
//
//		return $new_html;
//	}

	public function eb_currency_for_update_create( $args, $mode, $eventbrite_id, $post, $params ) {
		$currency = $this->get_currency_from_eb_locale();

		$args['event.currency'] = $currency;

		return $args;
	}

	/**
	 * List of locales supported by Eventbrite
	 * This list is hand-crafted from the Region Selector in the global footer of Eventbrite.com pages. Screenshot at https://cl.ly/412T23182w1Q
	 * @access private
	 * @return array
	 */
	private function eb_locale_country_name_array() {
		$array = array(
			'es_AR'		=> __( 'Argentina', 'tribe-extension' ),
			'en_AU'		=> __( 'Australia', 'tribe-extension' ),
			'pt_BR'		=> __( 'Brazil', 'tribe-extension' ),
			'en_CA'		=> __( 'Canada (EN)', 'tribe-extension' ),
			'fr_CA'		=> __( 'Canada (FR)', 'tribe-extension' ),
			'de_DE'		=> __( 'Germany', 'tribe-extension' ),
			'es_ES'		=> __( 'Spain', 'tribe-extension' ),
			'fr_FR'		=> __( 'France', 'tribe-extension' ),
			'en_HK'		=> __( 'Hong Kong', 'tribe-extension' ),
			'en_IE'		=> __( 'Ireland', 'tribe-extension' ),
			'it_IT'		=> __( 'Italy', 'tribe-extension' ),
			'nl_NL'		=> __( 'Netherlands', 'tribe-extension' ),
			'en_NZ'		=> __( 'New Zealand', 'tribe-extension' ),
			'pt_PT'		=> __( 'Portugal', 'tribe-extension' ),
			'en_SG'		=> __( 'Singapore', 'tribe-extension' ),
			'en_GB'		=> __( 'United Kingdom', 'tribe-extension' ),
			'en_US'		=> __( 'United States', 'tribe-extension' ),
		);

		$array = $this->append_array_keys_to_values( $array );

		return $array;
	}

	private function append_array_keys_to_values( $array = '' ) {
		if ( ! is_array( $array ) || empty( $array ) ) {
			return false;
		}

		$new_array = array();

		foreach ( $array as $key => $value ) {
			$new_array[$key] = sprintf( '%s (%s)', $value, $key );
		}

		return $new_array;
	}

	/**
	 * Get user-selected Eventbrite locale
	 *
	 * @access private
	 * @return string
	 */
	private function get_chosen_eb_locale() {
		$locale = tribe_get_option( $this->opts_prefix . 'locale' );

		if ( empty( $locale )
			|| ! is_string( $locale )
			|| ! array_key_exists( $locale, $this->eb_locale_country_name_array() )
		) {
			$locale = 'en_US';
		}

		return $locale;
	}

	/**
	 * Get Eventbrite currency from specified locale
	 * This list is hand-crafted from noting the currency specified in the calculator at https://www.eventbrite.com/fees/ (per TLD) after selecting each region from Eventbrite's Region Selector. Could just play with PHP's https://secure.php.net/localeconv but decided against it to avoid changing system settings.
	 * @access private
	 * @return string
	 */
	private function get_currency_from_eb_locale() {
		$locale = $this->get_chosen_eb_locale();

		$locale_currencies = array(
			'es_AR'		=> 'ARS',
			'en_AU'		=> 'AUD',
			'pt_BR'		=> 'BRL',
			'en_CA'		=> 'CAD',
			'fr_CA'		=> 'CAD',
			'de_DE'		=> 'EUR',
			'es_ES'		=> 'EUR',
			'fr_FR'		=> 'EUR',
			'en_HK'		=> 'HKD', // EB's fees calculator does not display
			'en_IE'		=> 'EUR',
			'it_IT'		=> 'EUR',
			'nl_NL'		=> 'EUR',
			'en_NZ'		=> 'NZD',
			'pt_PT'		=> 'EUR',
			'en_SG'		=> 'SGD', // EB's fees calculator does not display
			'en_GB'		=> 'GBP',
			'en_US'		=> 'USD',
		);

		$currency = $locale_currencies[$locale];

		return $currency;
	}

	/**
	 * Get Eventbrite Top-Level Domain (TLD) from specified locale
	 * This list is hand-crafted from noting the domain change after selecting each region from Eventbrite's Region Selector.
	 * @access private
	 * @return string
	 */
	private function eb_locale_to_tld() {
		$locale = $this->get_chosen_eb_locale();

		$locale_tlds = array(
			'es_AR'		=> '.com.ar',
			'en_AU'		=> '.com.au',
			'pt_BR'		=> '.br',
			'en_CA'		=> '.ca',
			'fr_CA'		=> '.ca', // it is the same!
			'de_DE'		=> '.de',
			'es_ES'		=> '.es',
			'fr_FR'		=> '.fr',
			'en_HK'		=> '.hk',
			'en_IE'		=> '.ie',
			'it_IT'		=> '.it',
			'nl_NL'		=> '.nl',
			'en_NZ'		=> '.co.nz',
			'pt_PT'		=> '.pt',
			'en_SG'		=> '.sg',
			'en_GB'		=> '.co.uk',
			'en_US'		=> '.com',
		);

		$tld = $locale_tlds[$locale];

		return $tld;
	}

	/**
	 * Get Eventbrite TLD from user-chosen Eventbrite Locale
	 * @access private
	 * @return string
	 */
	private function get_eb_tld() {
		return $this->eb_locale_to_tld( $this->get_chosen_eb_locale() );
	}

	// originally from https://theeventscalendar.com/knowledgebase/configuring-eventbrite-tickets-to-use-eventbrite-co-uk/
	public function api_url( $url ) {
		$replace_with = $this->get_eb_tld();

		$new_url = str_replace( '.com', $replace_with, $url );

		return esc_url( $new_url );
	}

	// originally from https://tribe.slack.com/archives/products-support/p1485534655007614?thread_ts=1485534292.007600&cid=C2ZAM3ECF
	// which was for https://theeventscalendar.com/support/forums/topic/why-does-plugin-not-work-for-private-events-yet-iframe-widget-does/
	/**
	 * Displays Eventbrite Tickets iframe even if the Eventbrite event is Private
	 * @return string|void
	 */
	public function render_tickets_iframe_private_eb_events() {
		$post_id  = get_the_ID();
		$api = tribe( 'eventbrite.api' );
		$event = $api->get_event( $post_id );

		if ( empty( $event->id ) ) {
			return;
		}

		$event_id = $event->id;

		$iframe_src = sprintf( 'https://www.eventbrite%s/tickets-external?eid=%d&amp;ref=etckt&v=2', $this->get_eb_tld(), $event_id );
		$iframe_src = apply_filters( 'tribe_events_eb_iframe_url', $iframe_src );

		$html = '';

		if (
			! empty( $event_id ) &&
			$api->is_live( $post_id ) &&
			tribe_event_show_tickets( $post_id )
		) {
			$html = sprintf(
				'<div class="eventbrite-ticket-embed" style="width:100%%; text-align:left;">
						<iframe id="eventbrite-tickets-%1$s" src="%2$s" style="height:200px; width:100%%; overflow:auto;"></iframe>
						<div style="font-family:Helvetica, Arial; font-size:10px; padding:5px 0 5px; margin:2px; width:100%%; text-align:left;">
							<a target="_blank" href="https://www.eventbrite.com/features?ref=etckt" style="color:#ddd; text-decoration:none;">Event registration</a>
							<span style="color:#ddd"> powered by </span>
							<a target="_blank" href="https://www.eventbrite.com?ref=etckt" style="color:#ddd; text-decoration:none;">Eventbrite</a>
						</div>
					</div>', $event_id, $iframe_src );
		}

		$html = apply_filters( 'tribe_template_factory_debug', $html, 'Tribe__Events__Eventbrite__Template::the_tickets' );
		return apply_filters( 'tribe_events_eb_iframe_html', $html, $event_id, $post_id );
	}

	private function single_event_display_locations_array() {
		$array = array(
			'tribe_events_single_event_before_the_content'  => __( 'After Featured Image, Before Description', 'tribe-extension' ),
			'tribe_events_single_event_after_the_content'   => __( 'After Description', 'tribe-extension' ),
			'tribe_events_single_event_before_the_meta'     => __( 'Before the Meta', 'tribe-extension' ),
			'tribe_events_single_event_after_the_meta'      => __( 'After the Meta', 'tribe-extension' ),
		);

		return $array;
	}

}