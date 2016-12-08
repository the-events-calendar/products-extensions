<?php

defined( 'WPINC' ) || die;

if ( class_exists( 'Tribe__Settings_Helper' ) ) {
	return;
}

/**
 * Helper for inserting/removing fields on the WP Admin Tribe Settings pages
 */
class Tribe__Settings_Helper {

	/**
	 * Fields inserted into misc section
	 *
	 * @var array
	 */
	private $insert_fields_misc = array();

	/**
	 * Fields that will be inserted above a specified field
	 *
	 * @var array
	 */
	private $insert_fields_above = array();

	/**
	 * Fields that will be inserted below a specified field
	 *
	 * @var array
	 */
	private $insert_fields_below = array();

	/**
	 * Array of settings being added to a Tribe Settings tab
	 *
	 * @var array
	 */
	private $remove_fields = array();


	/**
	 * Setup the helper
	 *
	 * @param int $priority Priority at which this hooks into 'tribe_settings_tab_fields'.
	 */
	public function __construct( $priority = 100 ) {
		add_filter( 'tribe_settings_tab_fields', array( $this, 'filter_options' ), $priority, 2 );
	}


	/**
	 * Add a field to a Tribe Settings tab
	 *
	 * @param string $field_key         Option key for your setting. Example: 'fancyOptionName'.
	 * @param array $field_args         See Tribe__Field() for available args.
	 *                                  Example: array( 'type' => 'checkbox_bool, 'label' => ... )
	 * @param string $setting_tab       Settings tab where this will be added. Example: 'display'.
	 * @param string $neighboring_field (optional) The field key/HTML name="" attribute to insert this under.
	 * @param bool   $above             (optional) Insert above or below its neighbor.
	 */
	public function add_field( $field_key, $field_args, $setting_tab, $neighboring_field = null, $above = true ) {
		// Our settings walker needs 'key' => arg pairs.
		$field = array( $field_key => $field_args );

		$this->add_fields( $field, $setting_tab, $neighboring_field, $above );
	}

	/**
	 * Add multiple fields to a Tribe Settings tab
	 *
	 * @param array  $fields            Fields that will be added, expects 'fieldname' => (array) args.
	 * @param string $setting_tab       Settings tab where this will be added. Example: 'display'.
	 * @param string $neighboring_field (optional) The field key/HTML name="" attribute to insert this under.
	 * @param bool   $above             (optional) Insert above or below its neighbor.
	 */
	public function add_fields( $fields, $setting_tab, $neighboring_field = null, $above = false ) {
		if ( ! is_string( $neighboring_field ) ) {
			// If neighbor is not specified, add this to misc section.
			$this->insert_fields_misc = array_replace_recursive(
				$this->insert_fields_misc,
				array( $setting_tab => $fields )
			);
		} elseif ( true === $above ) {
			// Add to above fields list with neighbor specified.
			$this->insert_fields_above = array_replace_recursive(
				$this->insert_fields_above,
				array( $setting_tab => array( $neighboring_field => $fields ) )
			);
		} else {
			// Add to below fields list with neighbor specified.
			$this->insert_fields_below = array_replace_recursive(
				$this->insert_fields_below,
				array( $setting_tab => array( $neighboring_field => $fields ) )
			);
		}

	}


	/**
	 * Remove a field from one of the tabs in WP Admin > Events > Settings
	 *
	 * @param string $field_key   Option key for your setting. Example: 'fancyOptionName'.
	 * @param string $setting_tab Settings tab where this will be added. Example: 'display'.
	 */
	public function remove_field( $field_key, $setting_tab ) {
		$this->remove_fields[ $setting_tab ][] = $field_key;
	}


	/**
	 * Attached to 'tribe_settings_tab_fields' to add/remove this class' fields on Tribe Settings pages.
	 *
	 * @param array $fields The fields within tribe settings page.
	 * @param string $tab   The settings tab key.
	 *
	 * @return array $fields The fields within tribe settings page
	 */
	public function filter_options( $fields, $tab ) {

		// Fields appended to misc section.
		if ( array_key_exists( $tab , $this->insert_fields_misc ) ) {

			// Add a misc heading if none exists.
			if ( ! array_key_exists( 'tribeMiscSettings', $fields ) ) {
				$misc_heading = array(
					'tribeMiscSettings' => array(
						'type' => 'html',
						'html' => '<h3>' . esc_html__( 'Miscellaneous Settings', 'tribe-common' ) . '</h3>',
					),
				);
				$fields = Tribe__Main::array_insert_before_key( 'tribe-form-content-end', $fields, $misc_heading );
			}

			// Insert these settings under misc heading.
			$fields = Tribe__Main::array_insert_after_key( 'tribeMiscSettings', $fields, $this->insert_fields_misc[ $tab ] );
		}

		// Fields inserted above a neighboring field.
		if ( array_key_exists( $tab , $this->insert_fields_above ) ) {

			foreach ( $this->insert_fields_above[ $tab ] as $insert_after => $new_field ) {
				$fields = Tribe__Main::array_insert_before_key( $insert_after, $fields, $new_field );
			}
		}

		// Fields inserted below a neighboring field.
		if ( array_key_exists( $tab , $this->insert_fields_below ) ) {

			foreach ( $this->insert_fields_below[ $tab ] as $insert_after => $new_field ) {
				$fields = Tribe__Main::array_insert_after_key( $insert_after, $fields, $new_field );
			}
		}

		// Fields that will be removed.
		if ( array_key_exists( $tab , $this->remove_fields ) ) {

			foreach ( $this->remove_fields[ $tab ] as $remove_field ) {
				if ( array_key_exists( $remove_field, $fields ) ) {
					unset( $fields[ $remove_field ] );
				}
			}
		}

		return $fields;
	}

}

