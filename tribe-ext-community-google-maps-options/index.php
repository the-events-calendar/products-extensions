<?php
/**
 * Plugin Name:     The Events Calendar: Community Events: Add Google Map Display and Link Options
 * Description:     Adds the Google Map Display and Google Map Link options in the venue section of Community Event's frontend editor.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Show_Google_Maps
 * Author:          Modern Tribe, Inc.
 * Author URI:      http://m.tri.be/1971
 * License:         GPLv2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 */

// Do not load unless Tribe Common is fully loaded.
if ( ! class_exists( 'Tribe__Extension' ) ) {
	return;
}

/**
 * Extension main class, class begins loading on init() function.
 */
class Tribe__Extension__Show_Google_Maps extends Tribe__Extension {

	/**
	 * Setup the Extension's properties.
	 *
	 * This always executes even if the required plugins are not present.
	 */
	public function construct() {

		// Only work after version 4.5 as previous versions included the field
		$this->add_required_plugin( 'Tribe__Events__Community__Main', '4.5' );

		$this->set_url( 'https://theeventscalendar.com/extensions/add-google-maps-display-and-link-options/' );
	}

	/**
	 * Extension initialization and hooks.
	 */
	public function init() {

		add_action( 'tribe_events_community_section_after_venue', array( $this, 'display' ) );

	}

	/**
	 * Show Google Map Display and Link Checkboxes for Community Events Editor
	 */
	public function display() {
		global $post;
		$google_map_toggle = false;
		?>
		<table class="tribe-section-content">
			<colgroup>
				<col class="tribe-colgroup tribe-colgroup-label">
				<col class="tribe-colgroup tribe-colgroup-field">
			</colgroup>
			<?php
			if ( tribe_get_option( 'embedGoogleMaps', true ) ) { // Only show if embed option selected

				$google_map_toggle = true;
				if ( $post->ID ) {
					$google_map_toggle = get_post_meta( $post->ID, '_EventShowMap', true );
				}

				?>
				<tr id="google_map_toggle" class="remain-visible">
					<td class='tribe-table-field-label'><?php esc_html_e( 'Show Google Map:', 'the-events-calendar' ); ?></td>
					<td>
						<input
								tabindex="<?php tribe_events_tab_index(); ?>"
								type="checkbox"
								id="EventShowMap"
								name="venue[EventShowMap][]"
								value="1"
							<?php checked( $google_map_toggle ); ?>
						/>
					</td>
				</tr>
				<?php
			}

			$google_map_link_toggle = true;
			if ( $post->ID ) {
				$google_map_link_toggle = get_post_meta( $post->ID, '_EventShowMapLink', true );
			}

			?>
			<tr id="google_map_link_toggle" class="remain-visible">
				<td class='tribe-table-field-label'><?php esc_html_e( 'Show Google Maps Link:', 'the-events-calendar' ); ?></td>
				<td>
					<input
							tabindex="<?php tribe_events_tab_index(); ?>"
							type="checkbox"
							id="EventShowMapLink"
							name="venue[EventShowMapLink][]"
							value="1"
						<?php checked( $google_map_link_toggle ); ?>
					/>
				</td>
			</tr>
		</table>
		<?php

	}

}