<tr
	class="tribe-dependent eb-url-row"
	data-depends="#tribe-ea-field-<?php echo esc_attr( $origin ); ?>_import_source"
	data-condition="source_type_url">
	<th scope="row">
		<label for="tribe-ea-field-<?php echo esc_attr( $origin ); ?>_source_type_url" class="tribe-ea-hidden">
			<input
				name="aggregator[<?php echo esc_attr( $origin ); ?>][source_type]"
				type="radio"
				id="tribe-ea-field-<?php echo esc_attr( $origin ); ?>_source_type_url"
				value=""
				checked="checked"
			>

			<?php echo esc_html( $field->label ); ?>
		</label>
	</th>
	<td>
		<input
			name="aggregator[<?php echo esc_attr( $origin ); ?>][source]"
			type="text"
			id="tribe-ea-field-<?php echo esc_attr( $origin ); ?>_source"
			class="tribe-ea-field tribe-ea-size-xlarge"
			placeholder="<?php echo esc_attr( $field->placeholder ); ?>"
			value="<?php echo esc_attr( empty( $record->meta['source'] ) ? '' : $record->meta['source'] ); ?>"
			data-validation-match-regexp="<?php echo esc_attr( $origin_regex ); ?>"
			data-validation-error="<?php esc_attr_e( 'Invalid URL', 'the-events-calendar' ); ?>"
		>
	</td>
</tr>