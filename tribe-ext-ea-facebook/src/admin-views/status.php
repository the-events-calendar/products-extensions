<tr>
    <td class="label">
        <img src="<?php echo esc_url( $origin_logo ); ?>" /> <span><?php echo esc_html( $origin_label ); ?></span>
    </td>
    <td class="indicator <?php echo esc_attr( $indicator ); ?>">
		<span class="dashicons dashicons-<?php echo esc_attr( $indicator_icons[ $indicator ] ); ?>"></span></td>
    <td><?php echo esc_html( $text ); ?></td>
    <td><?php echo $notes; // Escaped above already. ?></td>
</tr>
