<?php

// Do not load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! class_exists( 'Tribe__View_Helper' ) ) {
	return;
}
/**
 * Class Tribe__View_Helper
 *
 * @todo This class is old, it will be massively redone with the new view architecture in 4.5 then integrated into Core. Until then it works.
 *
 * Helper primarily for working with multiple views. Some usages:
 * - Running code on any of multiple views
 * - Getting list of available views for settings fields
 * - Determining which view is currently displaying
 */

class Tribe__View_Helper {

	/**
	 * A list of views this class can help with, each item can contain:
	 *
	 * array(
	 *  'name' =         Descriptive name of the view
	 *  'class' =        Required PHP class for view to exist
	 *  'is_func' =      Function used to check if this is the view
	 *  'eventDisplay' = What eventDisplay query var will be present
	 *  'past' =         (bool) Has separate views for past and upcoming
	 *  'tax' =          (bool) Has taxonomy pages
	 * )
	 */
	public static $supported_views = array(
		'month' => array(
			'name' => 'Month',
			'class' => 'Tribe__Events__Main',
			'is_func' => 'tribe_is_month',
			'eventDisplay' => 'month',
			'tax' => true,
		),

		'week' => array(
			'name' => 'Week',
			'class' => 'Tribe__Events__Pro__Main',
			'is_func' => 'tribe_is_week',
			'eventDisplay' => 'week',
			'tax' => true,
		),

		'day' => array(
			'name' => 'Day',
			'class' => 'Tribe__Events__Main',
			'is_func' => 'tribe_is_day',
			'eventDisplay' => 'day',
			'tax' => true,
		),

		'list' => array(
			'name' => 'List',
			'class' => 'Tribe__Events__Main',
			'is_func' => 'tribe_is_list_view',
			'eventDisplay' => 'list',
			'past' => true,
			'tax' => true,
		),

		'map' => array(
			'name' => 'Map',
			'class' => 'Tribe__Events__Pro__Main',
			'is_func' => 'tribe_is_map',
			'eventDisplay' => 'map',
			'past' => true,
			'tax' => true,
		),

		'photo' => array(
			'name' => 'Photo',
			'class' => 'Tribe__Events__Pro__Main',
			'is_func' => 'tribe_is_photo',
			'eventDisplay' => 'photo',
			'past' => true,
			'tax' => true,
		),

		'single-event' => array(
			'name' => 'Single Event',
			'class' => 'Tribe__Events__Main',
			'is_func' => 'tribe_is_single_event',
			'eventDisplay' => 'single-event',
		),

		'recurring-all' => array(
			'name' => 'Single Event: Recurring /all/',
			'class' => 'Tribe__Events__Pro__Main',
			'is_func' => 'tribe_is_recurring_all',
			'eventDisplay' => 'all',
			'past' => true,
			'tax' => true,
		),

		'organizer' => array(
			'name' => 'Organizer',
			'class' => 'Tribe__Events__Main',
			'is_func' => 'tribe_is_organizer_page',
		),

		'venue' => array(
			'name' => 'Venue',
			'class' => 'Tribe__Events__Main',
			'is_func' => 'tribe_is_venue_page',
		),

		'community-my-events' => array(
			'name' => 'Community: My Events',
			'class' => 'Tribe__Events__Community__Main',
			'is_func' => 'tribe_is_community_my_events_page',
		),

		'community-edit-events' => array(
			'name' => 'Community: Edit Events',
			'class' => 'Tribe__Events__Community__Main',
			'is_func' => 'tribe_is_community_edit_event_page',
		),
	);

	/**
	 * Checks if the current view is one of the ones specified
	 *
	 * @param mixed $views Array containing view names or string containing a single view
	 *
	 * @return boolean
	 */
	public static function is_view( $views = array() ) {
		// Convert single view string to array
		$views = (array) $views;

		$is_view = ( array_search( self::which_view(), $views ) !== false );

		return $is_view;
	}


	/**
	 * Returns which supported tribe view is displaying, if any
	 *
	 * @return mixed A string containing a $supported_views[ key ], or NULL
	 */
	public static function which_view() {
		foreach ( self::get_available_views() as $view_key => $view ) {
			// Call the function to check if this is the view
			if ( ! empty( $view['is_func'] ) && call_user_func( $view['is_func'] ) === true ) {
				return $view_key;
			}
		}

		// If it reaches this point no view was found
		return null;
	}


	/**
	 * Generates an array containing currently available views based on active plugins
	 *
	 * @return array Containing available views and their properties
	 */
	public static function get_available_views() {
		$output = array();

		foreach ( self::$supported_views as $view_key => $view ) {
			if ( ! class_exists( $view['class'] ) ) {
				continue;
			}

			$output[ $view_key ] = $view;
		}

		return $output;
	}
}