<?php
if( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function wpcrm_email_notice(){
    ?><div class="error"><p><?php _e( 'Sorry, but WP-CRM System Email Notifications requires WP-CRM to be installed and active.', 'wp-crm-system-email-notifications' ); ?></p></div><?php
}

add_action( 'admin_init', 'email_notifications_check_has_wpcrm' );
function email_notifications_check_has_wpcrm() {
    if( is_admin() && current_user_can( 'activate_plugins' ) &&  (!is_plugin_active( 'wp-crm-system/wp-crm-system.php' ) ) ) {
        add_action( 'admin_notices', 'wpcrm_email_notice' );

        deactivate_plugins( WPCRM_EMAIL_NOTIFICATIONS_PLUGIN_BASENAME );

        if( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }
}