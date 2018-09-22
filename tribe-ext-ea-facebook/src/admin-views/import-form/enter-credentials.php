<tr class="tribe-dependent tribe-credential-row" data-depends="#tribe-ea-field-origin" data-condition="<?php echo esc_attr( $origin ); ?>">
	<td colspan="2" class="<?php echo esc_attr( $is_token_valid ? 'enter-credentials' : 'has-credentials' ); ?>">
		<input type="hidden" name="has-credentials" id="tribe-has-<?php echo esc_attr( $origin ); ?>-credentials" value="0">
		<div class="tribe-message tribe-credentials-prompt">
			<p>
				<span class="dashicons dashicons-warning"></span>
				<?php echo esc_html( $credentials_text ); ?>
			</p>
			<a class="tribe-ea-<?php echo esc_attr( $origin ); ?>-button"
				href="<?php echo esc_url( $auth_url ); ?>">
				<?php echo esc_html( $credentials_button ); ?>
			</a>
		</div>
	</td>
</tr>
