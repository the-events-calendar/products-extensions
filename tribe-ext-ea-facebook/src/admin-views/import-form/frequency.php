<?php
	$selected_frequency = empty( $record->meta['frequency'] ) ? 'daily' : $record->meta['frequency'];
?>
<tr class="tribe-dependent" data-depends="<?php echo esc_attr( $data_depends ); ?>" data-condition="<?php echo esc_attr( $data_condition ); ?>">
	<th scope="row">
		<label for="tribe-ea-field-<?php echo esc_attr( $origin ); ?>_import_type">
			<?php echo esc_html( $field->label ); ?>
		</label>
	</th>
	<td>
		<input type="hidden" name="has-credentials" id="tribe-has-<?php echo esc_attr( $origin ); ?>-credentials" value="<?php echo absint( ! $is_token_valid ); ?>">
		<?php if ( 'edit' === $aggregator_action ) : ?>
			<input type="hidden" name="aggregator[<?php echo esc_attr( $origin ); ?>][import_type]" id="tribe-ea-field-<?php echo esc_attr( $origin ); ?>_import_type" value="schedule" />
			<strong class="tribe-ea-field-readonly"><?php echo esc_html__( 'Scheduled Import', 'the-events-calendar' ); ?></strong>
		<?php else : ?>
			<select
				name="aggregator[<?php echo esc_attr( $origin ); ?>][import_type]"
				id="tribe-ea-field-<?php echo esc_attr( $origin ); ?>_import_type"
				class="tribe-ea-field tribe-ea-dropdown tribe-ea-size-large tribe-import-type"
				placeholder="<?php echo esc_attr( $field->placeholder ); ?>"
				data-hide-search
				data-prevent-clear
			>
				<option value=""></option>
				<option value="manual">
					<?php echo esc_html__( 'One-Time Import', 'the-events-calendar' ); ?>
				</option>
				<option value="schedule">
					<?php echo esc_html__( 'Scheduled Import', 'the-events-calendar' ); ?>
				</option>
			</select>
		<?php endif; ?>

		<select
			name="aggregator[<?php echo esc_attr( $origin ); ?>][import_frequency]"
			id="tribe-ea-field-<?php echo esc_attr( $origin ); ?>_import_frequency"
			class="tribe-ea-field tribe-ea-dropdown tribe-ea-size-large tribe-dependent"
			placeholder="<?php echo esc_attr( $frequency->placeholder ); ?>"
			data-hide-search
			data-depends="#tribe-ea-field-<?php echo esc_attr( $origin ); ?>_import_type"
			data-condition="schedule"
			data-prevent-clear
		>
			<option value=""></option>
			<?php foreach ( $frequencies as $frequency_object ) : ?>
				<option value="<?php echo esc_attr( $frequency_object->id ); ?>"
					<?php selected( $selected_frequency, $frequency_object->id ); ?>>
					<?php echo esc_html( $frequency_object->text ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<span
			class="tribe-bumpdown-trigger tribe-bumpdown-permanent tribe-bumpdown-nohover tribe-ea-help dashicons dashicons-editor-help tribe-dependent"
			data-bumpdown="<?php echo esc_attr( $field->help ); ?>"
			data-depends="#tribe-ea-field-<?php echo esc_attr( $origin ); ?>_import_type"
			data-condition-not="schedule"
			data-condition-empty
			data-width-rule="all-triggers"
		></span>
		<span
			class="tribe-bumpdown-trigger tribe-bumpdown-permanent tribe-bumpdown-nohover tribe-ea-help dashicons dashicons-editor-help tribe-dependent"
			data-bumpdown="<?php echo esc_attr( $frequency->help ); ?>"
			data-depends="#tribe-ea-field-<?php echo esc_attr( $origin ); ?>_import_type"
			data-condition="schedule"
			data-width-rule="all-triggers"
		></span>
	</td>
</tr>
