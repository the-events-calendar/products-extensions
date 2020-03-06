<?php
/**
 * Plugin Name:       Filter Bar Extension: Custom Filter
 * Plugin URI:        https://theeventscalendar.com/extensions/---the-extension-article-url---/
 * GitHub Plugin URI: https://github.com/mt-support/tribe-ext-custom-filter
 * Description:       [Extension Description]
 * Version:           1.0.0
 * Extension Class:   Tribe\Extensions\Custom_Filter\Main
 * Author:            Modern Tribe, Inc.
 * Author URI:        http://m.tri.be/1971
 * License:           GPL version 3 or any later version
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       tribe-ext-custom-filter
 *
 *     This plugin is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     any later version.
 *
 *     This plugin is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *     GNU General Public License for more details.
 */

namespace Tribe\Extensions\Custom_Filter;

use Tribe__Autoloader;
use Tribe__Dependency;
use Tribe__Extension;

/**
 * Define Constants
 */

if ( ! defined( __NAMESPACE__ . '\NS' ) ) {
	define( __NAMESPACE__ . '\NS', __NAMESPACE__ . '\\' );
}

if ( ! defined( NS . 'PLUGIN_TEXT_DOMAIN' ) ) {
	// `Tribe\Extensions\Custom_Filter\PLUGIN_TEXT_DOMAIN` is defined
	define( NS . 'PLUGIN_TEXT_DOMAIN', 'tribe-ext-custom-filter' );
}

// Do not load unless Tribe Common is fully loaded and our class does not yet exist.
if (
	class_exists( 'Tribe__Extension' )
	&& ! class_exists( NS . 'Main' )
) {
	/**
	 * Extension main class, class begins loading on init() function.
	 */
	class Main extends Tribe__Extension {

		/**
		 * @var Tribe__Autoloader
		 */
		private $class_loader;

		/**
		 * Custom options prefix (without trailing underscore).
		 *
		 * Should leave blank unless you want to set it to something custom, such as if migrated from old extension.
		 */
		private $opts_prefix = '';

		/**
		 * Is Events Calendar PRO active. If yes, we will add some extra functionality.
		 *
		 * @return bool
		 */
		public $ecp_active = false;

		/**
		 * Setup the Extension's properties.
		 *
		 * This always executes even if the required plugins are not present.
		 */
		public function construct() {
			// Dependency requirements and class properties can be defined here.

			/**
			 * Examples:
			 * All these version numbers are the ones on or after November 16, 2016, but you could remove the version
			 * number, as it's an optional parameter. Know that your extension code will not run at all (we won't even
			 * get this far) if you are not running The Events Calendar 4.3.3+ or Event Tickets 4.3.3+, as that is where
			 * the Tribe__Extension class exists, which is what we are extending.
			 *
			 * If using `tribe()`, such as with `Tribe__Dependency`, require TEC/ET version 4.4+ (January 9, 2017).
			 */
			// $this->add_required_plugin( 'Tribe__Tickets__Main', '4.4' );
			// $this->add_required_plugin( 'Tribe__Tickets_Plus__Main', '4.3.3' );
			// $this->add_required_plugin( 'Tribe__Events__Main', '4.4' );
			// $this->add_required_plugin( 'Tribe__Events__Pro__Main', '4.3.3' );
			// $this->add_required_plugin( 'Tribe__Events__Community__Main', '4.3.2' );
			// $this->add_required_plugin( 'Tribe__Events__Community__Tickets__Main', '4.3.2' );
			$this->add_required_plugin( 'Tribe__Events__Filterbar__View' );
			// $this->add_required_plugin( 'Tribe__Events__Tickets__Eventbrite__Main', '4.3.2' );
			// $this->add_required_plugin( 'Tribe_APM', '4.4' );

			// Conditionally-require Events Calendar PRO. If it is active, run an extra bit of code.
			// add_action( 'tribe_plugins_loaded', [ $this, 'detect_tec_pro' ], 0 );
		}

		/**
		 * Check required plugins after all Tribe plugins have loaded.
		 *
		 * Useful for conditionally-requiring a Tribe plugin, whether to add extra functionality
		 * or require a certain version but only if it is active.
		 */
		public function detect_tec_pro() {
			/** @var Tribe__Dependency $dep */
			$dep = tribe( Tribe__Dependency::class );

			if ( $dep->is_plugin_active( 'Tribe__Events__Pro__Main' ) ) {
				$this->add_required_plugin( 'Tribe__Events__Pro__Main' );
				$this->ecp_active = true;
			}
		}

		/**
		 * Extension initialization and hooks.
		 */
		public function init() {
			// Load plugin textdomain
			// Don't forget to generate the 'languages/tribe-ext-custom-filter.pot' file
			load_plugin_textdomain( PLUGIN_TEXT_DOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages/' );

			if ( ! $this->php_version_check() ) {
				return;
			}

			$this->class_loader();

			// Make it work in v1.
			add_action( 'tribe_events_filters_create_filters', [ $this, 'tec_kb_create_filter' ] );
			// Make it work in v2.
			add_filter( 'tribe_context_locations', [ $this, 'tec_kb_filter_context_locations' ] );
			add_filter( 'tribe_events_filter_bar_context_to_filter_map', [ $this, 'tec_kb_filter_map' ] );

		}

		/**
		 * Check if we have a sufficient version of PHP. Admin notice if we don't and user should see it.
		 *
		 * @link https://theeventscalendar.com/knowledgebase/php-version-requirement-changes/ All extensions require PHP 5.6+.
		 *
		 * Delete this paragraph and the non-applicable comments below.
		 * Make sure to match the readme.txt header.
		 *
		 * Note that older version syntax errors may still throw fatals even
		 * if you implement this PHP version checking so QA it at least once.
		 *
		 * @link https://secure.php.net/manual/en/migration56.new-features.php
		 * 5.6: Variadic Functions, Argument Unpacking, and Constant Expressions
		 *
		 * @link https://secure.php.net/manual/en/migration70.new-features.php
		 * 7.0: Return Types, Scalar Type Hints, Spaceship Operator, Constant Arrays Using define(), Anonymous Classes, intdiv(), and preg_replace_callback_array()
		 *
		 * @link https://secure.php.net/manual/en/migration71.new-features.php
		 * 7.1: Class Constant Visibility, Nullable Types, Multiple Exceptions per Catch Block, `iterable` Pseudo-Type, and Negative String Offsets
		 *
		 * @link https://secure.php.net/manual/en/migration72.new-features.php
		 * 7.2: `object` Parameter and Covariant Return Typing, Abstract Function Override, and Allow Trailing Comma for Grouped Namespaces
		 *
		 * @return bool
		 */
		private function php_version_check() {
			$php_required_version = '5.6';

			if ( version_compare( PHP_VERSION, $php_required_version, '<' ) ) {
				if (
					is_admin()
					&& current_user_can( 'activate_plugins' )
				) {
					$message = '<p>';

					$message .= sprintf( __( '%s requires PHP version %s or newer to work. Please contact your website host and inquire about updating PHP.', PLUGIN_TEXT_DOMAIN ), $this->get_name(), $php_required_version );

					$message .= sprintf( ' <a href="%1$s">%1$s</a>', 'https://wordpress.org/about/requirements/' );

					$message .= '</p>';

					tribe_notice( PLUGIN_TEXT_DOMAIN . '-php-version', $message, [ 'type' => 'error' ] );
				}

				return false;
			}

			return true;
		}

		/**
		 * Use Tribe Autoloader for all class files within this namespace in the 'src' directory.
		 *
		 * @return Tribe__Autoloader
		 */
		public function class_loader() {
			if ( empty( $this->class_loader ) ) {
				$this->class_loader = new Tribe__Autoloader;
				$this->class_loader->set_dir_separator( '\\' );
				$this->class_loader->register_prefix(
					NS,
					__DIR__ . DIRECTORY_SEPARATOR . 'src'
				);
			}

			$this->class_loader->register_autoloader();

			return $this->class_loader;
		}

		/**
		 * Filters the map of filters available on the front-end to include the custom one.
		 *
		 * @param array<string,string> $map A map relating the filter slugs to their respective classes.
		 *
		 * @return array<string,string> The filtered slug to filter class map.
		 */
		function tec_kb_filter_map( array $map ) {
		  if ( ! class_exists( 'Tribe__Events__Filterbar__Filter' ) ) {
			// This would not make much sense, but let's be cautious.
			return $map;
		  }

		  include_once __DIR__ . '/src/Time_Of_Day_Custom.php';

		  $map['timeofdaycustom'] = 'Time_Of_Day_Custom';

		  return $map;
		}

		/**
		 * Filters the Context locations to let the Context know how to fetch the value of the filter from a request.
		 *
		 * Here we add the `timeofdaycustom` as a read-only Context location: we'll not need to write it.
		 *
		 * @param array<string,array> $locations A map of the locations the Context supports and is able to read from and write
		 *                                       to.
		 *
		 * @return array<string,array> The filtered map of Context locations, with the one required from the filter added to it.
		 */
		function tec_kb_filter_context_locations( array $locations ) {
		  // Read the filter selected values, if any, from the URL request vars.
		  $locations['timeofdaycustom'] = [ 'read' => [ \Tribe__Context::REQUEST_VAR => 'timeofdaycustom' ], ];

		  return $locations;
		}

		/**
		 * Includes the custom filter class and creates an instance of it.
		 */
		function tec_kb_create_filter() {
			if ( ! class_exists( 'Tribe__Events__Filterbar__Filter' ) ) {
			return;
			}

			include_once __DIR__ . '/src/Time_Of_Day_Custom.php';

			new \Time_Of_Day_Custom(
			__( 'Time Custom', 'tribe-events-filter-view' ),
			'timeofdaycustom'
			);
		}

	} // end class
} // end if class_exists check
