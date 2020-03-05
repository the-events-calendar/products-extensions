<?php

use Tribe\Events\Filterbar\Views\V2\Filters\Context_Filter;

class Time_Of_Day_Custom extends \Tribe__Events__Filterbar__Filter {
	// Use the trait required for filters to correctly work with Views V2 code.
	use Context_Filter;

	public $type = 'checkbox';

	// THese are needed to make the join aliases unique
	protected $alias = '';
	protected $tod_start_alias = '';
	protected $tod_duration_alias = '';

	protected function get_values() {
		// The time-of-day filter.
		$time_of_day_array = array(
			'allday' => __( 'All Day', 'tribe-events-filter-view' ),
			'13-14' => __( '1:00-2:00 pm', 'tribe-events-filter-view' ),
			'14-15' => __( '2:00-3:00 pm', 'tribe-events-filter-view' ),
			'15-16' => __( '3:00-4:00 pm', 'tribe-events-filter-view' ),
			'16-17' => __( '4:00-5:00 pm', 'tribe-events-filter-view' ),
			'17-18' => __( '5:00-6:00 pm', 'tribe-events-filter-view' ),
			'18-19' => __( '6:00-7:00 pm', 'tribe-events-filter-view' ),
			'19-20' => __( '7:00-8:00 pm', 'tribe-events-filter-view' ),
			'20-21' => __( '8:00-9:00 pm', 'tribe-events-filter-view' ),
			'21-22' => __( '9:00-10:00 pm', 'tribe-events-filter-view' ),
			'22-23' => __( '10:00-11:00 pm', 'tribe-events-filter-view' ),
			'23-24' => __( '11:00-12:00 pm', 'tribe-events-filter-view' ),
		);

		$time_of_day_values = array();
		foreach ( $time_of_day_array as $value => $name ) {
			$time_of_day_values[] = array(
				'name' => $name,
				'value' => $value,
			);
		}
		return $time_of_day_values;
	}

	protected function setup_join_clause() {
		add_filter( 'posts_join', array( 'Tribe__Events__Query', 'posts_join' ), 10, 2 );
		global $wpdb;
		$values = $this->currentValue;

		$all_day_index = array_search( 'allday', $values );
		if ( $all_day_index !== false ) {
			unset( $values[ $all_day_index ] );
		}

		$this->alias = 'custom_all_day_' . uniqid();
		$this->tod_start_alias = 'tod_start_date_' . uniqid();
		$this->tod_duration_alias = 'tod_duration_' . uniqid();

		$joinType = empty( $all_day_index ) ? 'LEFT' : 'INNER';

		$this->joinClause .= " {$joinType} JOIN {$wpdb->postmeta} AS {$this->alias} ON ({$wpdb->posts}.ID = {$this->alias}.post_id AND {$this->alias}.meta_key = '_EventAllDay')";

		if ( ! empty( $values ) ) { // values other than allday
			$this->joinClause .= " INNER JOIN {$wpdb->postmeta} AS {$this->tod_start_alias} ON ({$wpdb->posts}.ID = {$this->tod_start_alias}.post_id AND {$this->tod_start_alias}.meta_key = '_EventStartDate')";
			$this->joinClause .= " INNER JOIN {$wpdb->postmeta} AS {$this->tod_duration_alias} ON ({$wpdb->posts}.ID = {$this->tod_duration_alias}.post_id AND {$this->tod_duration_alias}.meta_key = '_EventDuration')";
		}
	}

	/**
	 * Sets up the filter WHERE clause(s).
	 *
	 * This will be added to the running events query to apply the matching criteria, time-of-day, handled by the
	 * custom filer.
	 *
	 * @throws Exception
	 */
	protected function setup_where_clause() {
		global $wpdb;
		$clauses = [];

		if ( in_array( 'allday', $this->currentValue, true ) ) {
			$clauses[] = "( {$this->alias}.meta_value = 'yes' )";
		} else {
			$this->whereClause = " AND ( {$this->alias}.meta_id IS NULL ) ";
		}

		foreach ( $this->currentValue as $value ) {
		if ( $value === 'allday' ) {
			// Handled earlier.
			continue;
		}

		list( $start_hour, $end_hour ) = explode( '-', $value );
		$start     = $start_hour . ':00:00';
		$end       = $end_hour . ':00:00';
		$clauses[] = $wpdb->prepare( "(
			TIME( CAST( {$this->tod_start_alias}.meta_value as DATETIME ) ) >= %s
			AND TIME( CAST({$this->tod_start_alias}.meta_value as DATETIME)) < %s
		)",
			$start, $end );
		}

		$this->whereClause .= ' AND (' . implode( ' OR ', $clauses ) . ')';
	}
}
