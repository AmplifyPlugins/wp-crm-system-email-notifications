<?php
$wpcrm_email_notifications_key = get_option( 'wpcrm_email_notifications_license_key' );
$wpcrm_email_notifications_status = get_option( 'wpcrm_email_notifications_license_status' );
?>

<tr valign="top">
	<th scope="row" valign="top">
		<?php _e('Email Notifications License Key','wp-crm-system-email-notifications'); ?>
	</th>
	<td>
		<input id="wpcrm_email_notifications_license_key" name="wpcrm_email_notifications_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $wpcrm_email_notifications_key ); ?>" />
		<label class="description" for="wpcrm_email_notifications_license_key"><?php _e('Enter your license key','wp-crm-system-contact-user'); ?></label>
	</td>
</tr>
<?php if( false !== $wpcrm_email_notifications_key ) { ?>
	<tr valign="top">
		<th scope="row" valign="top">
		</th>
		<td>
			<?php if( $wpcrm_email_notifications_status !== false && $wpcrm_email_notifications_status == 'valid' ) { ?>
				<span style="color:green;"><?php _e('active'); ?></span>
				<?php wp_nonce_field( 'wpcrm_plugin_license_nonce', 'wpcrm_plugin_license_nonce' ); ?>
				<input type="submit" class="button-secondary" name="wpcrm_email_notifications_deactivate" value="<?php _e('Deactivate License','wp-crm-system-contact-user'); ?>"/>
			<?php } else {
				wp_nonce_field( 'wpcrm_plugin_license_nonce', 'wpcrm_plugin_license_nonce' ); ?>
				<input type="submit" class="button-secondary" name="wpcrm_email_notifications_activate" value="<?php _e('Activate License','wp-crm-system-contact-user'); ?>"/>
			<?php } ?>
		</td>
	</tr>
<?php } ?>