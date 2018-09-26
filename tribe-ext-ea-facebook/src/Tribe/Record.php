<?php
// Don't load directly
defined( 'WPINC' ) or die;

class Tribe__Extension__Facebook_Dev_Origin__Record extends \Tribe__Events__Aggregator__Record__Abstract {

	/**
	 * {@inheritdoc}
	 */
	public $origin = 'facebook-dev';

	/**
	 * {@inheritdoc}
	 */
	public function get_label() {

		return __( 'Facebook Dev', 'the-events-calendar-facebook-events' );

	}

	/**
	 * {@inheritdoc}
	 */
	public function queue_import( $args = array() ) {

		$defaults = array(
			'site' => urlencode( site_url() ),
		);

		$args = wp_parse_args( $args, $defaults );

		return parent::queue_import( $args );

	}

	/**
	 * Filters the event to ensure that a proper URL is in the EventURL.
	 *
	 * @param array                                       $event  Event data
	 * @param Tribe__Events__Aggregator__Record__Abstract $record Aggregator Import Record
	 *
	 * @return array
	 */
	public static function filter_event_to_force_url( $event, $record ) {

		if ( Tribe__Extension__Facebook_Dev_Origin::get_origin() !== $record->origin ) {
			return $event;
		}

		if ( ! empty( $event['EventURL'] ) ) {
			return $event;
		}

		$event['EventURL'] = $record->meta['source'];

		return $event;

	}

	/**
	 * Filters the event to ensure that fields are preserved that are not otherwise supported by this origin.
	 *
	 * @param array                                       $event  Event data
	 * @param Tribe__Events__Aggregator__Record__Abstract $record Aggregator Import Record
	 *
	 * @return array
	 */
	public static function filter_event_to_preserve_fields( $event, $record ) {

		if ( Tribe__Extension__Facebook_Dev_Origin::get_origin() !== $record->origin ) {
			return $event;
		}

		return self::preserve_event_option_fields( $event );

	}

	/**
	 * Add Site URL for EA requests.
	 *
	 * @param array                                       $args   EA REST arguments
	 * @param Tribe__Events__Aggregator__Record__Abstract $record Aggregator Import Record
	 *
	 * @return mixed
	 */
	public static function filter_add_site_get_import_data( $args, $record ) {

		if ( Tribe__Extension__Facebook_Dev_Origin::get_origin() !== $record->origin ) {
			return $args;
		}

		$args['site'] = urlencode( site_url() );

		return $args;

	}

}
