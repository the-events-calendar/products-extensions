<?php
/**
 * Plugin Name:     The Events Calendar PRO Extension: Cleanup Recurring Events
 * Description:     Adds a recurring event cleanup tool to WP Admin > Tools > Available tools.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Recurring_Cleanup
 * Author:          Modern Tribe, Inc.
 * Author URI:      http://m.tri.be/1971
 * License:         GPLv2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
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
class Tribe__Extension__Recurring_Cleanup extends Tribe__Extension {

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main' );
		$this->add_required_plugin( 'Tribe__Events__Pro__Main' );

		$this->set_url( 'https://theeventscalendar.com/extensions/the-events-calendar-pro-cleanup-recurring-events/' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		add_action( 'tool_box', array( $this, 'output_cleanup_box' ) );
	}

	/**
	 * Echoes the admin UI.
	 */
	public function output_cleanup_box() {

		// Process delete event form.
		if (
			isset( $_POST['tribe-recurring-cleanup-eventid'] ) &&
			isset( $_POST['tribe-recurring-cleanup-backup-confirmation'] ) &&
			isset( $_POST['tribe-recurring-cleanup-submit'] ) &&
			check_ajax_referer( 'tribe-recurring-cleanup' )
		) {
			$event_id = intval( $_POST['tribe-recurring-cleanup-eventid'] );
			$this->delete_recurrence( intval( $_POST['tribe-recurring-cleanup-eventid'] ) );
			$notifications = esc_html__( 'Recurrences deleted for event ID ', 'tribe-extension' ) . $event_id;
		}

		$recurrence_table_headings = array(
			esc_html__( 'Event', 'tribe-extension' ),
			esc_html__( 'ID', 'tribe-extension' ),
			esc_html__( '# of recurrences', 'tribe-extension' ),
		);
		$recurrence_table_cells = $this->get_recurring_event_table_cells();
		$simple_table = new Tribe__Simple_Table( $recurrence_table_cells, $recurrence_table_headings );
		$simple_table->table_attributes = array( 'cellspacing' => '0', 'cellpadding' => '5' );
		$simple_table->html_escape_td_values = false;

		$recurrences_table = $simple_table->output_table();

		require_once dirname( __FILE__ ) . '/src/Tribe/admin-views/recurring-cleanup-module.php';
	}

	/**
	 * Returns query containing events with the most recurrences.
	 *
	 * Shows how many recurrences each recurring event has.
	 * Includes events from all statuses including in the trash.
	 *
	 * @return array|null|object
	 */
	protected function get_recurring_event_list() {
		global $wpdb;

		$count_recurrences_sql = "
		SELECT 
			`post_title` as title,
			`post_parent` as event_id, 
			COUNT(*) As recurrences 
		FROM 
			{$wpdb->posts}
		WHERE 
			`post_parent` <> 0 
			AND `post_type` = 'tribe_events' 
		GROUP BY 
			`post_parent` 
		ORDER BY 
			`recurrences` DESC";

		return $wpdb->get_results( $count_recurrences_sql, ARRAY_A );
	}

	/**
	 * Gets a list of events with many recurrences.
	 *
	 * @return array Each one an HTML link element to an event series.
	 */
	protected function get_recurring_event_table_cells() {
		$recurrence_count_results = $this->get_recurring_event_list();
		$recurrence_table_cells = array();

		foreach ( $recurrence_count_results as $row ) {
			$row_output = array();

			$event_edit_url = get_edit_post_link( $row['event_id'] );

			foreach ( $row as $cell ) {

				$row_output[] = sprintf(
					'<a href="%s">%s</a>',
					$event_edit_url,
					$cell
				);

			}

			$recurrence_table_cells[] = $row_output;
		}

		return $recurrence_table_cells;
	}

	/**
	 * Deletes all recurrences for a series of events.
	 *
	 * @param string $event_id The parent event ID for the series.
	 *
	 * @return array|null|object The query result
	 */
	protected function delete_recurrence( $event_id ) {
		global $wpdb;

		$delete_query = $wpdb->prepare(
			"
			delete a,b,c,d
			FROM {$wpdb->posts} a
			LEFT JOIN {$wpdb->term_relationships} b ON ( a.ID = b.object_id )
			LEFT JOIN {$wpdb->postmeta} c ON ( a.ID = c.post_id )
			LEFT JOIN {$wpdb->term_taxonomy} d ON ( d.term_taxonomy_id = b.term_taxonomy_id )
			LEFT JOIN {$wpdb->terms} e ON ( e.term_id = d.term_id )
			WHERE a.post_parent = %s",
			$event_id
		);

		return $wpdb->get_results( $delete_query );
	}
}
