<fieldset id="tribe-field-<?php echo esc_attr( $origin ); ?>_token" class="tribe-field tribe-field-text tribe-size-medium">
	<legend class="tribe-field-label"><?php echo esc_html( $origin_label ); ?></legend>
	<div class="tribe-field-wrap">
		<?php if ( ! $is_token_valid ) : ?>
			<p>
				<?php echo esc_html( $invalid_text ); ?>
			</p>
		<?php endif; ?>

		<a href="<?php echo esc_url( $auth_url ); ?>"
			target="_blank" class="tribe-ea-<?php echo esc_attr( $origin ); ?>-button tribe-ea-connect-button">
			<?php echo esc_html( $button_label ); ?>
		</a>

		<?php if ( $is_token_valid ) : ?>
			<a href="<?php echo esc_url( $disconnect_url ); ?>"
				class="tribe-ea-<?php echo esc_attr( $origin ); ?>-disconnect tribe-ea-disconnect-button">
				<?php echo esc_html( $disconnect_label ); ?>
			</a>
		<?php endif; ?>
	</div>
</fieldset>

<style type="text/css">
<?php include dirname( __DIR__ ) . '/resources/css/addon-fields.css'; ?>
</style>
