<?php

defined( 'WPINC' ) or die;

if ( ! function_exists( 'tribe_call_func' ) ) {
	/**
	 * Calls a function if it exists, useful for short snippets that won't throw notices
	 * @TODO Remove? This feels hacky and might not be necessary now that snippets can specify which other plugins must also be active
	 *
	 * @param mixed $function_name String containing name of the function, or array for the class/method
	 * @param array $args          Optional array of function arguments passed to call_user_func_array()
	 *
	 * @return mixed Returns the function result, or an unthrown Exception if the function is not set
	 */
	function tribe_call_func( $function_name, $args = array() ) {

		if ( function_exists( $function_name ) ) {
			return call_user_func_array( $function_name, $args );
		} else {
			return new Exception( 'Function does not exist: ' . $function_name );
		}

	}
}

if ( ! function_exists( 'tribe_is_list' ) ) {
	/**
	 * Checks if this is the List view
	 *
	 * @return bool
	 */
	function tribe_is_list() {
		$is_page = ( get_query_var( 'eventDisplay' ) === 'list' );

		return apply_filters( 'tribe_is_list', $is_page );
	}
}

if ( ! function_exists( 'tribe_is_single_event' ) ) {
	/**
	 * Checks if this is the Single Event view
	 *
	 * @return bool
	 */
	function tribe_is_single_event() {
		$is_page = ( get_query_var( 'eventDisplay' ) === 'single-event' );

		return apply_filters( 'tribe_is_single_event', $is_page );
	}
}

if ( ! function_exists( 'tribe_is_recurring_all' ) ) {
	/**
	 * Checks if this is the /all/ page created for recurring events
	 *
	 * @return bool
	 */
	function tribe_is_recurring_all() {
		$is_page = ( get_query_var( 'eventDisplay' ) === 'all' );

		return apply_filters( 'tribe_is_recurring_all', $is_page );
	}
}

if ( ! function_exists( 'tribe_is_organizer_page' ) ) {
	/**
	 * Checks if this is an organizer page
	 * @TODO This functionality could be merged into tribe_is_organizer() and be a much better fit in our API
	 * @TODO If merged with tribe_is_organizer() make that function not error out on WP Admin pages by checking if $post->ID is set
	 *
	 * @return bool
	 */
	function tribe_is_organizer_page() {
		return ( get_query_var( 'post_type' ) === Tribe__Events__Main::ORGANIZER_POST_TYPE);
	}
}

if ( ! function_exists( 'tribe_is_venue_page' ) ) {
	/**
	 * Checks if this is a venue page
	 * @TODO This functionality could be merged into tribe_is_venue() and be a much better fit in our API
	 *
	 * @return bool
	 */
	function tribe_is_venue_page() {
		return ( get_query_var( 'post_type' ) === Tribe__Events__Main::VENUE_POST_TYPE);
	}
}

if ( ! function_exists( 'tribe_is_ajax' ) ) {
	/**
	 * Checks if this is an ajax request
	 *
	 * @return bool
	 */
	function tribe_is_ajax() {
		return ( defined( 'DOING_AJAX' ) && DOING_AJAX );
	}
}

