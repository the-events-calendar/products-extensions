<?php
/**
 * Plugin Name:     The Events Calendar Extension: Facebook Dev for EA
 * Description:     Provides a new Facebook Dev origin for Event Aggregator.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Facebook_Dev_Origin
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
class Tribe__Extension__Facebook_Dev_Origin extends Tribe__Extension {

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {

		// @todo Add URL.
		$this->set_url( 'https://theeventscalendar.com/extensions/TBD/' );
		$this->set_version( '1.0.0' );

	}

	/**
	 * Get origin label text.
	 *
	 * @return string
	 */
	public static function get_origin() {

		return 'facebook-dev';

	}

	/**
	 * Get origin label text.
	 *
	 * @return string
	 */
	public static function get_origin_label() {

		return __( 'Facebook Dev', 'the-events-calendar' );

	}

	/**
	 * Get origin URL regex.
	 *
	 * @return string
	 */
	public static function get_origin_url_regex() {

		return '^(https?:\/\/)?(www\.)?facebook\.[a-z]{2,3}(\.[a-z]{2})?\/';

	}

	/**
	 * Get origin example URL.
	 *
	 * @return string
	 */
	public static function get_origin_example_url() {

		return 'facebook.com/event/1234567891012345/';

	}

	/**
	 * Get whether or not this origin requires OAuth to import events.
	 *
	 * @return boolean Whether this origin requires OAuth to import events.
	 */
	public static function get_origin_oauth_requirement() {

		return true;

	}

	/**
	 * Get origin default source.
	 *
	 * @return string
	 */
	public static function get_origin_default_source() {

		return self::get_origin_account_source_url();

	}

	/**
	 * Get origin account source URL.
	 *
	 * @return string
	 */
	public static function get_origin_account_source_url() {

		return 'https://www.facebook.com/me';

	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {

		add_filter( 'tribe_addons_tab_fields', array( $this, 'add_addon_fields' ) );
		add_filter( 'tribe_aggregator_fields', array( $this, 'add_ea_settings_fields' ), 10, 4 );
		add_action( 'tribe_events_status_third_party', array( $this, 'add_origin_to_status' ) );
		add_action( 'tribe_events_aggregator_import_form', array( $this, 'add_origin_to_import_form' ), 10, 2 );
		add_action( 'tribe_events_aggregator_refine_keyword_exclusions', array( $this, 'add_origin_to_refine_exclusions' ) );
		add_action( 'tribe_events_aggregator_refine_location_exclusions', array( $this, 'add_origin_to_refine_exclusions' ) );

	}

	/**
	 * Add add-on fields for origin.
	 *
	 * @param array $addon_fields Add-on fields.
	 *
	 * @return array Add-on fields.
	 */
	public function add_addon_fields( $addon_fields ) {

		$origin       = sanitize_text_field( self::get_origin() );
		$origin_label = self::get_origin_label();

		$is_token_valid   = $this->is_ea_token_valid();
		$auth_url         = $this->get_auth_url();
		$disconnect_url   = $this->get_disconnect_url();
		$disconnect_label = '';

		if ( ! $is_token_valid ) {
			/* translators: The placeholder used is for the Origin label */
			$button_label = _x( 'Connect to %1$s', 'button text for connection to EA origin', 'the-events-calendar' );
		} else {
			/* translators: The placeholder used is for the Origin label */
			$button_label = _x( 'Refresh your connection to %1$s', 'button text for refreshing connection with EA origin', 'the-events-calendar' );

			$disconnect_label = __( 'Disconnect', 'the-events-calendar' );
		}

		$button_label = sprintf( $button_label, $origin_label );

		/* translators: The placeholder used is for the Origin label */
		$info_text = _x( 'You need to connect Event Aggregator to %1$s to import your events.', 'description of connection for EA origin', 'the-events-calendar' );
		$info_text = sprintf( $info_text, $origin_label );

		/* translators: The placeholder used is for the Origin label */
		$invalid_text = _x( 'You need to connect to %1$s for Event Aggregator to work properly', 'notice for needing connection for EA origin', 'the-events-calendar' );
		$invalid_text = sprintf( $invalid_text, $origin_label );

		ob_start();
		include __DIR__ . '/src/admin-views/addon-fields.php';
		$token_html = ob_get_clean();

		$custom_addon_fields = array(
			$origin . '-start'        => array(
				'type' => 'html',
				'html' => '<h3>' . esc_html( $origin_label ) . '</h3>',
			),
			$origin . '-info-box'     => array(
				'type' => 'html',
				'html' => '<p>' . esc_html( $info_text ) . '</p>',
			),
			$origin . '_token_button' => array(
				'type' => 'html',
				'html' => $token_html,
			),
		);

		$addon_fields = array_merge( $addon_fields, $custom_addon_fields );

		return $addon_fields;

	}

	/**
	 * Add fields to EA settings page.
	 *
	 * @param array $fields                  List of aggregator fields.
	 * @param array $origin_post_statuses    List of post statuses.
	 * @param array $origin_categories       List of event categories.
	 * @param array $origin_show_map_options List of show map options.
	 *
	 * @return array List of aggregator fields.
	 */
	public function add_ea_settings_fields( $fields, $origin_post_statuses, $origin_categories, $origin_show_map_options = array() ) {

		$origin       = sanitize_text_field( self::get_origin() );
		$origin_label = self::get_origin_label();

		/* translators: The placeholder used is for the Origin label */
		$heading_text = _x( '%1$s Import Settings', 'heading text for origin import settings', 'the-events-calendar' );
		$heading_text = sprintf( $heading_text, $origin_label );

		$heading = '<h3 id="tribe-import-%1$s-settings">%2$s</h3>';
		$heading = sprintf( $heading, esc_attr( $origin ), esc_html( $heading_text ) );

		/* translators: The placeholder used is for the Origin label */
		$status_tooltip = _x( 'The default post status for events imported for %1$s', 'tooltip text for default post status EA origin setting', 'the-events-calendar' );
		$status_tooltip = sprintf( $status_tooltip, $origin_label );

		/* translators: The placeholder used is for the Origin label */
		$category_tooltip = _x( 'The default event category for events imported for %1$s', 'tooltip text for default event category EA origin setting', 'the-events-calendar' );
		$category_tooltip = sprintf( $category_tooltip, $origin_label );

		/* translators: The placeholder used is for the Origin label */
		$show_map_tooltip = _x( 'Show Google Map by default on imported event and venues for %1$s', 'tooltip text for default event map status EA origin setting', 'the-events-calendar' );
		$show_map_tooltip = sprintf( $show_map_tooltip, $origin_label );

		$custom_fields = array(
			$origin . '-defaults' => array(
				'type'     => 'html',
				'html'     => $heading,
				'priority' => 18.1,
			),
			'tribe_aggregator_default_' . $origin . '_post_status' => array(
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Default Status', 'the-events-calendar' ),
				'tooltip'         => esc_html( $status_tooltip ),
				'size'            => 'medium',
				'validation_type' => 'options',
				'default'         => '',
				'can_be_empty'    => true,
				'parent_option'   => Tribe__Events__Main::OPTIONNAME,
				'options'         => $origin_post_statuses,
				'priority'        => 18.2,
			),
			'tribe_aggregator_default_' . $origin . '_category' => array(
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Default Event Category', 'the-events-calendar' ),
				'tooltip'         => esc_html( $category_tooltip ),
				'size'            => 'medium',
				'validation_type' => 'options',
				'default'         => '',
				'can_be_empty'    => true,
				'parent_option'   => Tribe__Events__Main::OPTIONNAME,
				'options'         => $origin_categories,
				'priority'        => 18.3,
			),
		);

		if ( ! empty( $origin_show_map_options ) ) {
			$custom_fields[ 'tribe_aggregator_default_' . $origin . '_show_map' ] = array(
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Show Google Map', 'the-events-calendar' ),
				'tooltip'         => esc_html( $show_map_tooltip ),
				'size'            => 'medium',
				'validation_type' => 'options',
				'default'         => 'no',
				'can_be_empty'    => true,
				'parent_option'   => Tribe__Events__Main::OPTIONNAME,
				'options'         => $origin_show_map_options,
				'priority'        => 18.4,
			);
		}

		$fields = array_merge( $fields, $custom_fields );

		return $fields;

	}

	/**
	 * Add origin to status table.
	 *
	 * @param array $indicator_icons List of indicator icons.
	 */
	public function add_origin_to_status( $indicator_icons ) {

		$origin       = sanitize_text_field( self::get_origin() );
		$origin_label = self::get_origin_label();

		$is_token_valid = $this->is_ea_token_valid();
		$auth_url       = $this->get_auth_url();

		$indicator = 'good';
		$notes     = '&nbsp;';
		$text      = 'Connected';

		$needs_connection = false;

		if ( ! $is_token_valid && self::get_origin_oauth_requirement() ) {
			$needs_connection = true;
		}

		if ( $needs_connection ) {
			$indicator = 'warning';

			/* translators: The placeholder used is for the Origin label */
			$text = _x( 'You have not connected Event Aggregator to %1$s', 'no connection notice for EA origin', 'the-events-calendar' );
			$text = sprintf( $text, $origin_label );

			/* translators: The placeholder used is for the Origin label */
			$link_text = _x( 'Connect to %1$s', 'link for connecting EA origin', 'the-events-calendar' );
			$link_text = sprintf( $link_text, $origin_label );

			$notes = '<a href="' . esc_url( $auth_url ) . '">' . esc_html( $link_text ) . '</a>';
		} else {
			$indicator = 'warning';

			/* translators: The placeholder used is for the Origin label */
			$text = _x( 'Limited connectivity with %1$s', 'notice for unavailable oauth for EA origin', 'the-events-calendar' );
			$text = sprintf( $text, $origin_label );

			$notes = esc_html__( 'The service has disabled oAuth. Some types of events may not import.', 'the-events-calendar' );
		}

		$origin_logo = plugins_url( 'src/resources/images/' . $origin . '.png', __FILE__ );

		include __DIR__ . '/src/admin-views/status.php';

	}

	/**
	 * Add origin to import form.
	 *
	 * @param string $aggregator_action Aggregator action (new or edit).
	 * @param array  $form_args         Form arguments.
	 */
	public function add_origin_to_import_form( $aggregator_action, $form_args ) {

		$origin_regex       = self::get_origin_url_regex();
		$origin_example_url = self::get_origin_example_url();
		$default_eb_source  = self::get_origin_default_source();

		$origin       = sanitize_text_field( self::get_origin() );
		$origin_label = self::get_origin_label();

		$is_token_valid = $this->is_ea_token_valid();
		$auth_url       = $this->get_auth_url();

		$form_args['origin_slug'] = $origin;

		$field = (object) array(
			'label'       => __( 'Import Type:', 'the-events-calendar' ),
			'placeholder' => __( 'Select Import Type', 'the-events-calendar' ),
			'help'        => __( 'One-time imports include all currently listed events, while scheduled imports automatically grab new events and updates on a set schedule. Single events can be added via a one-time import.', 'the-events-calendar' ),
			'source'      => $origin . '_import_type',
		);

		$frequency = (object) array(
			'placeholder' => __( 'Scheduled import frequency', 'the-events-calendar' ),
			'help'        => __( 'Select how often you would like events to be automatically imported.', 'the-events-calendar' ),
			'source'      => $origin . '_import_frequency',
		);

		$frequencies = Tribe__Events__Aggregator__Cron::instance()->get_frequency();

		$data_depends   = '#tribe-ea-field-origin';
		$data_condition = $origin;

		if ( $is_token_valid ) {
			$data_depends   = '#tribe-has-' . $origin . '-credentials';
			$data_condition = '1';

			/* translators: The placeholder used is for the Origin label */
			$credentials_text = _x( 'Please connect to %1$s to enable event imports.', 'notice to connect the EA origin', 'the-events-calendar' );
			$credentials_text = sprintf( $credentials_text, $origin_label );

			/* translators: The placeholder used is for the Origin label */
			$credentials_button = _x( 'Connect to %1$s', 'button text to connect the EA origin', 'the-events-calendar' );
			$credentials_button = sprintf( $credentials_button, $origin_label );

			include __DIR__ . '/src/admin-views/import-form/enter-credentials.php';
		}

		include __DIR__ . '/src/admin-views/import-form/frequency.php';

		$ea_page = Tribe__Events__Aggregator__Page::instance();

		if ( 'edit' === $aggregator_action ) {
			$ea_page->template( 'fields/schedule', $form_args );
		}

		$field              = (object) array();
		$field->label       = __( 'Import Source', 'the-events-calendar' );
		$field->placeholder = __( 'Select Source', 'the-events-calendar' );
		$field->help        = __( 'Import events directly from your connected account or from a public URL.', 'the-events-calendar' );

		$field->options = array(
			array(
				'id'   => self::get_origin_account_source_url(),
				'text' => __( 'Import from your account', 'the-events-calendar' ),
			),
			array(
				'id'   => 'source_type_url',
				'text' => __( 'Import from URL', 'the-events-calendar' ),
			),
		);

		include __DIR__ . '/src/admin-views/import-form/source.php';

		$field              = (object) array();
		$field->label       = __( 'URL:', 'the-events-calendar' );
		$field->placeholder = $origin_example_url;

		/* translators: The placeholder used is for the example event URL */
		$field->help = _x( 'Enter an event URL, e.g. %1$s', 'help text for origin event source url', 'the-events-calendar' );
		$field->help = sprintf( $field->help, 'https://www.' . $origin_example_url );

		include __DIR__ . '/src/admin-views/import-form/source-url.php';

		$ea_page->template( 'origins/refine', $form_args );

		include __DIR__ . '/src/admin-views/import-form/preview.php';

	}

	/**
	 * Add origin to refine exclusions list.
	 *
	 * @param array $exclusions List of origins excluded.
	 *
	 * @return array List of origins excluded.
	 */
	public function add_origin_to_refine_exclusions( $exclusions ) {

		$exclusions[] = self::get_origin();

		return $exclusions;

	}

	/**
	 * Authorize token with EA and setup security key if it's not set yet.
	 *
	 * @return boolean
	 */
	public function authorize_ea_token() {

		// if the service hasn't enabled oauth for origin, always assume it is valid
		if ( ! tribe( 'events-aggregator.main' )->api( 'origins' )->is_oauth_enabled( self::get_origin() ) ) {
			return true;
		}

		// @todo do this
		$authorization_response = tribe( 'events-aggregator.service' )->has_eventbrite_authorized();

		if ( empty( $authorization_response->status ) || 'success' !== $authorization_response->status ) {
			return false;
		}

		// The security key is sent on initial authorization, we need to save it if we have it.
		if ( ! empty( $authorization_response->data->secret_key ) ) {
			$this->set_ea_security_key( $authorization_response->data->secret_key );
		}

		return $this->is_ea_token_valid();

	}

	/**
	 * Get the authentication URL.
	 *
	 * @param array $args URL arguments.
	 *
	 * @return string Authentication URL.
	 */
	public function get_auth_url( $args = array() ) {

		/** @var stdClass|WP_Error $ea_service_api */
		$ea_service_api = tribe( 'events-aggregator.service' )->api();

		if ( is_wp_error( $ea_service_api ) ) {
			return '';
		}

		$url = $ea_service_api->domain . sanitize_text_field( self::get_origin() ) . '/' . sanitize_text_field( $ea_service_api->key );

		$defaults = array(
			'back'      => 'settings',
			'referral'  => rawurlencode( home_url() ),
			'admin_url' => rawurlencode( get_admin_url() ),
			'type'      => 'new',
			'lang'      => get_bloginfo( 'language' ),
		);

		$args = wp_parse_args( $args, $defaults );

		return add_query_arg( $args, $url );

	}

	/**
	 * Get the disconnect URL.
	 *
	 * @return string Disconnect URL.
	 */
	public function get_disconnect_url() {

		$current_url = Tribe__Settings::instance()->get_url( array( 'tab' => 'addons' ) );

		$action = 'disconnect-' . sanitize_text_field( self::get_origin() );

		return wp_nonce_url(
			add_query_arg(
				'action',
				$action,
				$current_url
			),
			$action
		);

	}

	/**
	 * Get origin security key for EA.
	 *
	 * @return string Origin security key for EA.
	 */
	public function get_ea_security_key() {

		return tribe_get_option( sanitize_text_field( self::get_origin() ) . '_security_key' );

	}

	/**
	 * Set origin security key for EA.
	 *
	 * @param string $security_key Origin security key for EA.
	 */
	public function set_ea_security_key( $security_key ) {

		tribe_update_option( sanitize_text_field( self::get_origin() ) . '_security_key', $security_key );

	}

	/**
	 * Get arguments for EA.
	 *
	 * @return array EA arguments.
	 */
	public function get_ea_args() {

		$args = array(
			'referral'   => rawurlencode( home_url() ),
			'url'        => rawurlencode( site_url() ),
			'secret_key' => $this->get_ea_security_key(),
		);

		/**
		 * Allow filtering for which params we are sending to EA for Token callback.
		 *
		 *
		 * @param array $args Which arguments are sent to Token Callback.
		 */
		return apply_filters( 'tribe_aggregator_' . self::get_origin() . '_token_callback_args', $args );

	}

	/**
	 * Check if we have a valid token with EA.
	 *
	 * @return boolean Whether the EA token is valid.
	 */
	public function is_ea_token_valid() {

		$validate = $this->validate_ea_token();

		return $validate && ! is_wp_error( $validate );

	}

	/**
	 * Validate token with EA.
	 *
	 * @return stdClass|WP_Error Response or WP_Error if there was a problem.
	 */
	public function validate_ea_token() {

		$args = $this->get_ea_args();

		/** @var Tribe__Events__Aggregator__Service $ea_service */
		$ea_service = tribe( 'events-aggregator.service' );

		$response = $ea_service->get( sanitize_text_field( self::get_origin() ) . '/validate', $args );

		// If we have an WP_Error we return only CSV
		if ( is_wp_error( $response ) ) {
			// @todo How to register/add error messages?
			return tribe_error( 'core:aggregator:invalid-token', array(), array( 'response' => $response ) );
		}

		return $response;

	}

	/**
	 * Disconnect token on EA.
	 *
	 * @return stdClass|WP_Error Response or WP_Error if there was a problem.
	 */
	public function disconnect_ea_token() {

		$args = $this->get_ea_args();

		/** @var Tribe__Events__Aggregator__Service $ea_service */
		$ea_service = tribe( 'events-aggregator.service' );

		$response = $ea_service->get( sanitize_text_field( self::get_origin() ) . '/disconnect', $args );

		// If we have an WP_Error we return only CSV
		if ( is_wp_error( $response ) ) {
			// @todo How to register/add error messages?
			return tribe_error( 'core:aggregator:invalid-token', array(), array( 'response' => $response ) );
		}

		return $response;

	}

}
