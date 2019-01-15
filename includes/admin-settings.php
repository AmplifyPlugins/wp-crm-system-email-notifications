<?php
if( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Add Email Notification Settings
function wpcrm_system_email_setting_tab() {
	global $wpcrm_active_tab; ?>
	<a class="nav-tab <?php echo $wpcrm_active_tab == 'email-notifications' ? 'nav-tab-active' : ''; ?>" href="?page=wpcrm-settings&tab=email-notifications"><?php _e( 'Email', 'wp-crm-system' ) ?></a>
<?php }
add_action( 'wpcrm_system_settings_tab', 'wpcrm_system_email_setting_tab' );

function wpcrm_email_settings_content() {
	global $wpcrm_active_tab;
	if ( $wpcrm_active_tab == 'email-notifications' ) {
		include( plugin_dir_path( WPCRM_EMAIL_NOTIFICATIONS ) . 'settings.php' );
	}
}
add_action( 'wpcrm_system_settings_content', 'wpcrm_email_settings_content' );
