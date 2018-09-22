<tr class="tribe-dependent" data-depends="#tribe-ea-field-<?php echo esc_attr( $origin ); ?>_import_type" data-condition-not-empty>
	<th scope="row">
		<label for="tribe-ea-field-<?php echo esc_attr( $origin ); ?>_import_source"><?php echo esc_html( $field->label ); ?></label>
	</th>
	<td>
		<input
			type="hidden"
			name="aggregator[<?php echo esc_attr( $origin ); ?>][source_type]"
			id="tribe-ea-field-<?php echo esc_attr( $origin ); ?>_import_source"
			class="tribe-ea-field tribe-dropdown tribe-ea-size-xlarge"
			data-hide-search
			data-prevent-clear
			data-options="<?php echo esc_attr( json_encode( $field->options ) ); ?>"
			value="<?php echo esc_attr( $default_eb_source ); ?>"
		/>
		<span class="tribe-bumpdown-trigger tribe-bumpdown-permanent tribe-bumpdown-nohover tribe-ea-help dashicons dashicons-editor-help" data-bumpdown="<?php echo esc_attr( $field->help ); ?>" data-width-rule="all-triggers"></span>
	</td>
</tr>