<?php

// Do not load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
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
		$is_page = ( get_query_var( 'post_type' ) === Tribe__Events__Main::ORGANIZER_POST_TYPE);
		return apply_filters( 'tribe_is_organizer_page', $is_page );
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
		$is_page = ( get_query_var( 'post_type' ) === Tribe__Events__Main::VENUE_POST_TYPE);
		return apply_filters( 'tribe_is_organizer_page', $is_page );
	}
}