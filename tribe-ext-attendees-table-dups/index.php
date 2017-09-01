<?php
/**
* Plugin Name: Event Tickets Plus Extension: Show/Hide Duplicates in Attendees Table
* Description: Makes it easy to identify tickets from duplicate order numbers (e.g. 4 WooCommerce tickets purchased in a single transaction) at each event's Attendees Table view. Displays the count of total tickets per order and adds a button to show/hide duplicates. Note: the "Check in" button still applies per-ticket and not per-order.
* Version: 1.0.0
* Extension Class: Tribe__Extension__Attendees_Table_Duplicates
* Author: Modern Tribe, Inc.
* Author URI: http://m.tri.be/1971
* License: GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
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
class Tribe__Extension__Attendees_Table_Duplicates extends Tribe__Extension {

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {
		// Each plugin required by this extension
		$this->add_required_plugin( 'Tribe__Tickets_Plus__Main', '4.3' ); // does not work with RSVP tickets and was really only tested with WooCommerce tickets

		// Set the extension's TEC URL
		$this->set_url( 'https://theeventscalendar.com/extensions/attendees-table-show-hide-duplicates/' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {
		add_action( 'tribe_events_page_tickets-attendees', array( $this, 'output' ) );
	}

	/**
	 * Logic for the output
	 */
	public function output() {
		wp_enqueue_script( 'jquery' );
		?>
		<script type="text/javascript" id="tribe-extension-attendees-table-duplicates">
		jQuery( document ).ready( function( $ ) {
			const first_color = "#ffe2e7"; // green
			const dups_color = "#b4ffbe"; // pink

			const orders = $( "table.attendees tbody#the-list tr td.status > a[href*='view=view-order-details']" )
				.add( "table.attendees tbody#the-list tr td.status > a[href*='post=']" );

			$( orders ).each( function() {
				let order_id = $( this ).html().replace( /[^0-9]/, '' ); // get the innerHTML (like "#4637 – Complete") and remove all but numbers
				let order_id_class = 'order_id-' + order_id; // e.g. order_id-1234
				$( this ).parents( 'tr' ).addClass( order_id_class );
				order_id = '';
				order_id_class = '';
			});

			const orders_rows = $( "table.attendees tbody#the-list tr[class*='order_id']" );

			let order_id_classes = [];

			$( "table.attendees tbody#the-list" ).find( "tr[class*='order_id']" ).each( function() {
				order_id_classes.push( this.className );
			});

			// change "processing order_id-1234" array value to 2 separate array values: "processing" and "order_id-1234"
			order_id_classes = $.map( order_id_classes, function( value ) {
				return ( value.split( " " ) );
			});

			// remove all array items NOT starting with "order_id-" (credit: http://stackoverflow.com/a/3596096/893907)
			order_id_classes = $.grep( order_id_classes, function( value ) {
				return ( value.match( "^order_id-" ) );
			});

			// from order_id_classes, create an object of all classes and its count and create an array of the order ID classes that appear more than once
			// adapted from http://stackoverflow.com/a/24968449/893907
			const count = order_id_classes =>
				order_id_classes.reduce( ( a, b ) =>
					Object.assign( a, { [b]: ( a[b] || 0 ) + 1 } ), {} );

			const duplicates = dict =>
				Object.keys( dict ).filter( ( a ) => dict[a] > 1 );


			const dup_order_ids = duplicates( count( order_id_classes ) ); // array will exist and be an empty if no dups

			const dups_button_text = "<span class='dup_show_hide' style='display: none;'><?php esc_html_e( 'Show', 'tribe-extension' ); ?></span><span class='dup_show_hide'><?php esc_html_e( 'Hide', 'tribe-extension' ); ?></span> <span class='dups_count'>" + dup_order_ids.length + "</span> Dupl. Orders";

			const hide_dups_button = "<button type='button' class='hide_dup_orders button action' style='float: left; margin: 1px 8px 0 0;'>" + dups_button_text + "</button>";

			$( hide_dups_button ).insertAfter( "div.tablenav div.attendees-actions a:last-child" );

			// do stuff if we actually have duplicates
			if ( 0 < dup_order_ids.length ) {

				// color the duplicates and display number of duplicates per order in the Status column
				$( dup_order_ids ).each( function( index, value ) {
					let tr_this = $( "tr." + this );
					let total_count = tr_this.length;
					tr_this.not( ":last" ).css({ "background-color": first_color });

					if ( 1 < total_count ) {
						$( "tr." + this + ":last td.status" ).prepend( "<span class='total_of_same' style='font-weight: bold; display: block;'><?php esc_html_e( 'Total', 'tribe-extension' ); ?>: " + total_count + "</span>" );
						$( "tr." + this + ":last" ).css({ "background-color": dups_color });
					}

				});

				let collapsed = false;
				let hide_dup_orders = $( ".hide_dup_orders" );
				hide_dup_orders.css({ "background-color": dups_color });

				// click handler
				hide_dup_orders.click( function() {

					if ( false === collapsed ) {
						collapsed = true;
					} else {
						collapsed = false;
					}

					$( "span.dup_show_hide" ).toggle(); // exchange "Show" and "Hide" text in the action button

					$( dup_order_ids ).each( function( index, value ) {
						// oldest/first generated ticket is last in the list
						$( "tr." + this ).not( ":last" ).fadeToggle();

						if ( true === collapsed ) {
							$( "table.striped" ).removeClass( "striped" ); // alternating gray-and-white row colors is no longer accruate since some rows were removed
							$( ".hide_dup_orders" ).css({ "background-color": first_color });
						} else {
							$( "table" ).addClass( "striped" );
							$( ".hide_dup_orders" ).css({ "background-color": dups_color });
						}
					});
				});
			} else {
				$( ".hide_dup_orders" ).attr( "disabled", "disabled" ).text( "<?php esc_html_e( 'Zero Dupl. Orders', 'tribe-extension' ); ?>" );
			}
		});
		</script>
	<?php
	}
}