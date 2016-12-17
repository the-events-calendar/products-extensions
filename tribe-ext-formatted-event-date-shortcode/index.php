<?php
/**
* Plugin Name: The Events Calendar Extension: Formatted Event Date Shortcode
* Description: Output event Start Time or End Time in <a href="http://php.net/manual/function.date.php" target="_blank">whatever valid date/time format you want</a>. Example: [tribe_formatted_event_date id=1234 format="F j, Y, \\a\\t g:ia T" timezone="America/New_York"] would output Event ID 1234's start date (which is 11am in America/Chicago) like this: "August 2, 2017, at 12:00pm EDT"
* Version: 1.0.1
* Extension Class: Tribe__Extension__Formatted_Event_Date_Shortcode
* Author: Modern Tribe, Inc.
* Author URI: http://m.tri.be/1971
* License: GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// 2016-09-15 Cliff created for https://theeventscalendar.com/support/forums/topic/_eventstartdate-format/
// 2016-12-07 Cliff turned into an extension
// 2016-12-20 Cliff modified to become v1.0.1 to overcome bugs in TEC Core and improve this extension's code and error messages

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
class Tribe__Extension__Formatted_Event_Date_Shortcode extends Tribe__Extension {
	
	// Define the shortcode name
	public $shortcode_name = 'tribe_formatted_event_date';
	
	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 * 
	 * @access: public
	 */
	public function construct() {
		// Each plugin required by this extension
		$this->add_required_plugin( 'Tribe__Events__Main', '4.0' ); // main thing we are concerned about is '_EventTimezone' meta key, but there are protections here against it not existing anyway
		
		// Set the extension's TEC URL
		$this->set_url( 'https://theeventscalendar.com/extensions/formatted-event-date-shortcode/' );		
	}
	
	/**
	 * Extension initialization and hooks.
	 * 
	 * @access: public
	 */
	public function init() {
		add_shortcode( $this->shortcode_name, array( $this, 'shortcode_output' ) );
	}
	
	/**
	 * Get Event Date as String for Event Start Time (or End Time)
	 *
	 * @return: false|string
	 */
	public function get_event_date_string( $event_id, $start_end = 'start' ) {
		if ( empty( $event_id ) ) {
			return false;
		}
		
		if ( 'start' === $start_end ) {
			$type = 'Start';
		} else {
			$type = 'End';
		}
		
		if ( ! class_exists( 'Tribe__Events__Timezones' ) ) {
			return false;
		}
		
		if ( ! method_exists( 'Tribe__Events__Timezones', 'get_event_timezone_string' ) ) {
			return false;
		}

		$event_timezone = Tribe__Events__Timezones::get_event_timezone_string( $event_id );
		
		if ( ! in_array( $event_timezone, timezone_identifiers_list() ) ) {
			return false;
		}
		
		$event_date = get_post_meta( $event_id, "_Event{$type}Date", true );
		
		if ( empty( $event_date ) ) {
			return false;
		}
		
		return sprintf( '%s %s', $event_date, $event_timezone );
	}
	
	/**
	 * Get Unix Timestamp for Event Date as String
	 *
	 * @return: false|int
	 */
	public function get_event_timestamp( $event_date_as_string ) {
		if ( empty( $event_date_as_string ) ) {
			return false;
		}
		
		return strtotime( $event_date_as_string ); // strtotime is false for an empty string
	}
		
	/**
	 * Logic for the shortcode.
	 * Examples:
	 * 1) [tribe_formatted_event_date id=1234 format="F j, Y, \\a\\t g:ia"] (notice double escaping the word "at") -- Outputs "August 2, 2017, at 4:00pm" for Event/Post ID 1234's start datetime
	 * 2) [tribe_formatted_event_date id=1234 timezone="America/New_York" format="dS F, Y @ G:i A" start_end="end"] -- Outputs "02nd August, 2017 @ 18:00 PM" for Event/Post ID 1234's end datetime in a specific timezone
	 * 3) [tribe_formatted_event_date format="dS F Y"] -- Outputs "02nd August 2017" for the current post ID's start datetime
	 * 
	 * @access: public
	 *
	 * @return false|string
	 */
	public function shortcode_output( $atts ) {
		$defaults = array(
			'id'		=> get_the_ID(), // Post ID of a single Event
			'start_end'	=> '', // valid values are 'start' (or blank) or 'end'
			'format'	=> '', // REQUIRED -- see http://php.net/manual/function.date.php
			'timezone'	=> '', // example: 'America/Chicago' -- see http://php.net/manual/timezones.php
			'class'		=> '',
		);
		
		$atts = shortcode_atts( $defaults, $atts, $this->shortcode_name );
		
		$id = intval( $atts['id'] );
		
		// bail if Post ID is not set or is not an Event post type
		// https://theeventscalendar.com/function/tribe_is_event/
		if ( empty( $id ) || ! tribe_is_event( $id ) ) {
			if ( current_user_can( 'edit_posts' ) ) { // Contributor or higher
				return __( 'The "id" argument for this shortcode is either missing, or this post does not exist, or this post is not of the proper post type. (This message is only shown to Contributors and higher level users.)', 'tribe-extension' );
			} else {
				return false;
			}
		}
		
		$format = trim( $atts['format'] );
		
		// bail if date format is not specified (we do not care if it is valid)
		if ( empty( $format ) ) {
			if ( current_user_can( 'edit_posts' ) ) { // Contributor or higher
				return sprintf(
					__( 'The "format" argument for this shortcode is required. Please reference %s. (This message is only shown to Contributors and higher level users.)', 'tribe-extension' ),
					'<a target="_blank" href="http://php.net/manual/function.date.php">http://php.net/manual/function.date.php</a>'
				);
			} else {
				return false;
			}
		}
		
		$start_end = trim( strtolower( $atts['start_end'] ) );
		
		// default to 'start'
		if ( 'end' !== $start_end ) {
			$start_end = 'start';
		}
		
		// Event's timezone could be different from user's desired timezone output
		$event_timezone = Tribe__Events__Timezones::get_event_timezone_string( $id );
		
		// User-specified timezone from the shortcode argument
		$timezone = trim( $atts['timezone'] );
		
		if ( empty( $timezone ) ) {
			$timezone = $event_timezone;
		}
		
		// This should only display if the user tried to enter a timezone but did so incorrectly (i.e. should NOT display if timezone shortcode argument is left blank)
		if ( ! in_array( $timezone, timezone_identifiers_list() ) ) {
			if ( current_user_can( 'edit_posts' ) ) { // Contributor or higher
				return sprintf(
					__( 'The "timezone" argument for this shortcode is invalid. Please reference %s. (This message is only shown to Contributors and higher level users.)', 'tribe-extension' ),
					'<a target="_blank" href="http://php.net/manual/timezones.php">http://php.net/manual/timezones.php</a>'
				);
			} else {
				return false;
			}
		}
		
		// using our own methods here due to https://central.tri.be/issues/70923 (even if fixed, it is good to keep these to support backwards compatibility)
		$event_date_as_string = $this->get_event_date_string( $id, $start_end, $event_timezone );
		
		if ( empty( $event_date_as_string ) ) {
			if ( current_user_can( 'edit_posts' ) ) { // Contributor or higher
				return __( "There was an error with this event's date information. (This message is only shown to Contributors and higher level users.)", 'tribe-extension' );
			} else {
				return false;
			}
		}
		
		// get timestamp, use it in DateTime UTC, convert it to user's desired timezone and format
		$event_timestamp = $this->get_event_timestamp( $event_date_as_string );
		
		$event_datetime = new DateTime( '@' . $event_timestamp, new DateTimeZone ( 'UTC' ) );
		$event_datetime->setTimezone( new DateTimeZone( $timezone ) );
		
		$event_date = $event_datetime->format( $format );
		
		// https://developer.wordpress.org/reference/functions/sanitize_html_class/
		$class = sanitize_html_class( $atts['class'] );
		if ( ! empty( $class ) ) {
			$class = sprintf( '%s %s', sanitize_html_class( $this->shortcode_name ), $class );
		} else {
			$class = sanitize_html_class( $this->shortcode_name );
		}
		
		$output = sprintf( '<span class="%s">%s</span>',
			$class,
			esc_html( $event_date )
		);
		
		return $output;
	}
	
}