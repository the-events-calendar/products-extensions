<?php
/**
* Plugin Name: The Events Calendar Extension: Formatted Event Date Shortcode
* Description: Output event Start Time or End Time in <a href="http://php.net/manual/en/function.date.php" target="_blank">whatever valid date/time format you want</a>. Example: [tribe_formatted_event_date id=1234 format="F jS, Y" timezone="America/New_York"] would output like "August 2nd, 2017". <strong>NOTE:</strong> "T" and other timezone output formats will be stripped from the "format" argument <em>if</em> you specify a "timezone" argument as to avoid misstatements because "T" and others will output your WordPress timezone, not taking into account this shortcode's "timezone" argument.
* Version: 1.0.0
* Extension Class: Tribe__Extension__Formatted_Event_Date_Shortcode
* Author: Modern Tribe, Inc.
* Author URI: http://m.tri.be/1971
* License: GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Created 2016-09-15 for https://theeventscalendar.com/support/forums/topic/_eventstartdate-format/
// Turned into an Extension by Cliff on 2016-12-06

/*
*** Usage Examples: ***

1)
Output Event ID 1234's Start Date, like
*** August 2, 2017 at 4:00pm ***
(notice double escaping the word "at")
[tribe_formatted_event_date id=1234 format="F j, Y \\a\\t g:ia"]

2)
Output Event ID 1234's End Date in a different timezone, like
*** 02nd August, 2017 @ 18:00 PM ***
[tribe_formatted_event_date id=1234 timezone="America/New_York" format="dS F, Y @ G:i A" start_end="end"]

NOTE: Timezones (including timezone abbreviations) are not allowed in the "format" shortcode argument; they will be removed. Therefore, if the event is 11am CST but you want to display it as 12pm EST, you will need to add the "EST" manually after (i.e. outside of) the shortcode.

3)
Output current single Event's/post's Start Date like
*** 02nd August 2017 ***
[tribe_formatted_event_date format="dS F Y"]

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
class Tribe__Extension__Formatted_Event_Date_Shortcode extends Tribe__Extension {
	
	// Define the shortcode name
	public $shortcode_name = 'tribe_formatted_event_date';
	
	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		// add version here in addition to the header comment if/when there is an update to this extension
		// $this->set( 'version', '1.0.0' );
		
		// Each plugin required by this extension
		$this->add_required_plugin( 'Tribe__Events__Main', '4.3.3' );
		
		// Set the extension's tec.com URL
		// $this->set_url( 'https://theeventscalendar.com/extensions/formatted-event-date-shortcode/' )
	}
	
	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		add_shortcode( $this->shortcode_name, array( $this, 'tribe_formatted_event_date_logic' ) );
	}
		
	/**
	 * Logic for the shortcode.
	 *
	 * @return false|string
	 */
	public function tribe_formatted_event_date_logic( $atts ) {
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
				return __( 'Invalid timezone supplied. Please fix the "timezone" argument for this shortcode or edit this event to make sure it has timezone data. (This message is only shown to Contributors and higher level users.)', 'tribe_formatted_event_date' );
			} else {
				return false;
			}
		}
		
		
		$format = trim( $atts['format'] );
		
		// Unless we do something like http://stackoverflow.com/a/35474695, the timezone format will always output WordPress' timezone, not each event's.
		if ( ! empty( $format ) ) {
			// from "Timezone" section of the table at http://php.net/manual/en/function.date.php
			$formats_to_remove = array(
				'e',
				'I',
				'O',
				'P',
				'T',
				'Z',
			);
			
			$format = trim( str_replace( $formats_to_remove, '', $format ) );
		}
		
		$start_end = trim( $atts['start_end'] );
		// default to 'start'
		if ( empty( $start_end ) ) {
			$start_end = 'start';
		}
		
		if ( 'start' !== $start_end && 'end' !== $start_end ) {
			return false; // bad entry
		}
		
		if ( 'start' == $start_end ) {
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