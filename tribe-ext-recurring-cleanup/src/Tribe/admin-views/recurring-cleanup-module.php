<div class="card" id="tribe-recurring-cleanup">
	<h2 class="title"><?php _e( 'The Events Calendar PRO: Cleanup Recurring Events', 'tribe-extension' ); ?></h2>
	<p><?php _e( 'If your site is loading slow due to a lot of event recurrences this tool can help clean them up with great speed.', 'tribe-extension' ); ?></p>
	<ol>
		<li><?php _e( 'To start <a href="https://codex.wordpress.org/WordPress_Backups">make a backup of your database</a>, just to be safe.', 'tribe-extension' ); ?></li>
		<li><?php _e( 'Double-check your <a href="edit.php?post_type=tribe_events&page=tribe-common">Event Settings</a>. Can you adjust the "Create recurring events in advance for" or "Clean up recurring events after" to smaller numbers?', 'tribe-extension' ); ?></li>
		<li><?php _e( 'Obtain the ID for the event series you wish to cleanup. The table below will show which event IDs have the most recurrences. Events with more recurrences have a larger performance impact on your site.', 'tribe-extension' ); ?></li>
		<li><?php _e( 'Try editing the event and adjusting it\'s recurrence pattern. Can you set it to end after fewer recurrences? If so hit save and and a progress bar will show up gradually deleting those recurrences.', 'tribe-extension' ); ?></li>
		<li><?php _e( 'If editing the event does not work or is too slow for your needs, the following form can be used to delete <em>all</em> recurrences for that event.', 'tribe-extension' ); ?></li>
	</ol>
	<hr />
	<h4><?php _e( 'List of events with recurrences', 'tribe-extension' ); ?></h4>
	<?php echo $recurrences_table; ?>
	<hr />
	<h4><?php _e( 'Quickly delete all recurrences for an event', 'tribe-extension' ); ?></h4>
	<p><?php _e( 'This form will bypass the usual WordPress delete function and directly remove recurrences from the database. This is many times faster than WordPress\' delete function. However, it might cause issues with third party plugins that rely on that function or in other uncommon circumstances. Use this form as a last resort. Always try editing the event first and adjusting its recurrence pattern in the edit screen.', 'tribe-extension' ); ?></p>
	<?php if ( isset( $notifications ) ) : ?>
		<p><strong style="color:#F00000"><?php echo $notifications ; ?></strong></p>
	<?php endif; ?>
	<form action="#tribe-recurring-cleanup" method="post">
		<p>
			<label for="tribe-recurring-cleanup-eventid"><?php _e( 'Event ID', 'tribe-extension' ); ?></label><br />
			<input name="tribe-recurring-cleanup-eventid" id="tribe-recurring-cleanup-eventid" type="text" />
		</p>
		<p>
			<input name="tribe-recurring-cleanup-backup-confirmation" id="tribe-recurring-cleanup-backup-confirmation" type="checkbox" />
			<label for="tribe-recurring-cleanup-backup-confirmation"><?php _e( 'I have made a backup and am prepared to restore it if needed.', 'tribe-extension' ); ?></label>
		</p>
		<p>
			<?php wp_nonce_field( 'tribe-recurring-cleanup' ); ?>
			<input type="submit" name="tribe-recurring-cleanup-submit" id="tribe-recurring-cleanup-submit" value="<?php _e( 'Delete Recurrences', 'tribe-extension' ); ?>" />
		</p>
	</form>
</div>

