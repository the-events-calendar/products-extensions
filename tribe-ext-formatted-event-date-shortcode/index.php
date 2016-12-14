<?php
/**
* Plugin Name: The Events Calendar Extension: Formatted Event Date Shortcode
* Description: Output event Start Time or End Time in <a href="http://php.net/manual/en/function.date.php" target="_blank">whatever valid date/time format you want</a>. Example: [tribe_formatted_event_date id=1234 format="F j, Y, \\a\\t g:ia T" timezone="America/New_York"] would output Event ID 1234's start date (which is 11am in America/Chicago) like this: "August 2, 2017, at 12:00pm EST"
* Version: 1.0.0
* Extension Class: Tribe__Extension__Formatted_Event_Date_Shortcode
* Author: Modern Tribe, Inc.
* Author URI: http://m.tri.be/1971
* License: GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Created 2016-09-15 for https://theeventscalendar.com/support/forums/topic/_eventstartdate-format/
// Turned into an Extension by Cliff on 2016-12-07

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
	
	// Array of the possible values from the "Timezone" section of the table at http://php.net/manual/en/function.date.php
	private $timezone_formats = array(
		'e',
		'I',
		'O',
		'P',
		'T',
		'Z',
	);
	
	// Same as $this->timezone_formats except in regex format, e.g. for preg_match and preg_replace
	private $timezone_formats_regex = array(
		'/e/',
		'/I/',
		'/O/',
		'/P/',
		'/T/',
		'/Z/',
	);
	
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
	 * Outputs various timezone formats manually
	 * Unless we manually do this here, the "raw" timezone format would output in WordPress' timezone (i.e. static, not dynamic to the event), which is inaccurate and confusing.
	 *
	 * @access: private
	 *
	 * @return: string
	 */
	private function get_timezone_format_as_esc_string( $timezone_format, $timezone ) {
		if ( empty( $timezone_format ) || empty( $timezone ) ) {
			return '';
		}
		
		// based on http://stackoverflow.com/a/35474695
		$dateTime = new DateTime();
		$dateTime->setTimeZone( new DateTimeZone( $timezone ) ); 
		$timezone_result = $dateTime->format( $timezone_format );
		
		// some of the timezone formats output a number, like: "1", "+0200", or "-43200" (without quotes)
		if ( ! is_numeric( $timezone_result ) // I, O, Z
			&&  false === strpos( $timezone_result, ':' ) // P
		) {
			$array = str_split( $timezone_result );
			
			$timezone_result = '\\' . implode( '\\', $array );
		}
		
		return $timezone_result;
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
			'timezone'	=> '', // example: 'America/Chicago' -- see http://php.net/manual/en/timezones.php
			'format'	=> '', // see http://php.net/manual/en/function.date.php
			'start_end'	=> '', // valid values are 'start' (or blank) or 'end'
			'class'		=> '',
		);
		
		$atts = shortcode_atts( $defaults, $atts, $this->shortcode_name );
		
		$id = intval( $atts['id'] );
		
		// bail if Post ID is not set or is not an Event post type
		// https://theeventscalendar.com/function/tribe_is_event/
		if ( empty( $id ) || ! tribe_is_event( $id ) ) {
			return false;
		}
		
		$timezone = trim( $atts['timezone'] );
		
		if ( empty( $timezone ) ) {
			$timezone = get_post_meta( $id, '_EventTimezone', true ); // there is not a variable or constant in The Events Calendar for this meta key :(
		}
		
		// We MUST have a valid timezone because otherwise sending an empty string of a timezone to tribe_get_start_date() or tribe_get_end_date() results in inaccurate display
		if ( ! in_array( $timezone, timezone_identifiers_list() ) ) {
			if ( current_user_can( 'edit_posts' ) ) { // Contributor or higher
				return __( 'Invalid timezone supplied. Please fix the "timezone" argument for this shortcode or edit this event to make sure it has timezone data. (This message is only shown to Contributors and higher level users.)', 'tribe-extension' );
			} else {
				return false;
			}
		}
		
		// if $format is empty, will get defaulted to the format from https://theeventscalendar.com/function/tribe_format_date/
		$format = trim( $atts['format'] );
		
		// Massage the $format to allow outputting the FIRST timezone format.
		// LIMITATION: if a letter is escaped but it is the first that matches a timezone format, we do not test for this and it will not actually get escaped as intended, if at all.
		// If more than one timezone format is used, all but the FIRST will be fully ignored.
		if ( ! empty( $format ) ) {
			// get the first timezone format
			$timezone_format = '';
			foreach ( $this->timezone_formats as $key => $value ) {
				if ( false !== strpos( $format, $value ) ) {
					$timezone_format = substr( $format, strpos( $format, $value ), 1 );
					break;
				}
			}
			
			// Temporary placeholder to be replaced with manually-created timezone format string
			// Make sure no character is one from http://php.net/manual/en/function.date.php
			$timezone_temp_placeholder = '$$$$';
			
			// replace the first timezone format with a placeholder for later
			$format = preg_replace( $this->timezone_formats_regex, $timezone_temp_placeholder, $format, 1 );
			
			// get rid of all other timezone formats
			$format = str_replace( $this->timezone_formats, '', $format );
			
			// inject manual timezone format as escaped string so it will not be converted to a datetime format
			$format = str_replace( $timezone_temp_placeholder, $this->get_timezone_format_as_esc_string( $timezone_format, $timezone ), $format );
			
			$format = trim( $format );
		}
		
		$start_end = trim( $atts['start_end'] );
		// default to 'start'
		if ( empty( $start_end ) ) {
			$start_end = 'start';
		}
		
		if ( 'start' !== $start_end && 'end' !== $start_end ) {
			return false; // bad entry
		}
		
		if ( 'start' === $start_end ) {
			// https://theeventscalendar.com/function/tribe_get_start_date/
			$event_date = tribe_get_start_date( $id, true, $format, $timezone );
		} else { // 'end'
			// https://theeventscalendar.com/function/tribe_get_end_date/
			$event_date = tribe_get_end_date( $id, true, $format, $timezone );
		}
		
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
