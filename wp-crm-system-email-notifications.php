<?php
/*
    Plugin Name: WP-CRM System Email Notifications
    Plugin URL: https://www.wp-crm.com
    Description: Send notifications from WP-CRM to the assigned user's email address.
    Version: 2.0.5
    Author: Scott DeLuzio
    Author URI: https://www.wp-crm.com
    Text Domain: wp-crm-system-email-notifications
*/

	/*  Copyright 2015  Scott DeLuzio (email : support (at) wp-crm.com)	*/

if( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
define('WPCRM_EMAIL_NOTIFICATIONS',__FILE__);
/* Start Updater */
if(!defined('WPCRM_BASE_STORE_URL')){
	define( 'WPCRM_BASE_STORE_URL', 'http://wp-crm.com' );
}
// the name of your product. This should match the download name in EDD exactly
define( 'WPCRM_EMAIL_NOTIFICATIONS_NAME', 'Email Notifications' ); // you should use your own CONSTANT name, and be sure to replace it throughout this file

if( !class_exists( 'WPCRM_SYSTEM_SL_Plugin_Updater' ) ) {
	// load our custom updater
	include( dirname( __FILE__ ) . '/EDD_SL_Plugin_Updater.php' );
}

function wpcrm_email_notifications_updater() {

	// retrieve our license key from the DB
	$license_key = trim( get_option( 'wpcrm_email_notifications_license_key' ) );

	// setup the updater
	$edd_updater = new WPCRM_SYSTEM_SL_Plugin_Updater( WPCRM_BASE_STORE_URL, __FILE__, array(
			'version' 	=> '2.0.5', 				// current version number
			'license' 	=> $license_key, 		// license key (used get_option above to retrieve from DB)
			'item_name' => WPCRM_EMAIL_NOTIFICATIONS_NAME, 	// name of this plugin
			'author' 	=> 'Scott DeLuzio'  // author of this plugin
		)
	);

}
add_action( 'admin_init', 'wpcrm_email_notifications_updater', 0 );

function wpcrm_email_notifications_register_option() {
	// creates our settings in the options table
	register_setting('wpcrm_license_group', 'wpcrm_email_notifications_license_key', 'wpcrm_email_notifications_sanitize_license' );
}
add_action('admin_init', 'wpcrm_email_notifications_register_option');

function wpcrm_email_notifications_sanitize_license( $new ) {
	$old = get_option( 'wpcrm_email_notifications_license_key' );
	if( $old && $old != $new ) {
		delete_option( 'wpcrm_email_notifications_license_status' ); // new license has been entered, so must reactivate
	}
	return $new;
}
function wpcrm_email_notifications_activate_license() {

	// listen for our activate button to be clicked
	if( isset( $_POST['wpcrm_email_notifications_activate'] ) ) {

		// run a quick security check
	 	if( ! check_admin_referer( 'wpcrm_plugin_license_nonce', 'wpcrm_plugin_license_nonce' ) )
			return; // get out if we didn't click the Activate button

		// retrieve the license from the database
		$license = trim( get_option( 'wpcrm_email_notifications_license_key' ) );


		// data to send in our API request
		$api_params = array(
			'edd_action'=> 'activate_license',
			'license' 	=> $license,
			'item_name' => urlencode( WPCRM_EMAIL_NOTIFICATIONS_NAME ), // the name of our product in EDD
			'url'       => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( WPCRM_BASE_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		// make sure the response came back okay
		if( is_wp_error( $response ) )
			return false;

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "valid" or "invalid"

		update_option( 'wpcrm_email_notifications_license_status', $license_data->license );

	}
}
add_action('admin_init', 'wpcrm_email_notifications_activate_license');

function wpcrm_email_notifications_deactivate_license() {

	// listen for our activate button to be clicked
	if( isset( $_POST['wpcrm_email_notifications_deactivate'] ) ) {

		// run a quick security check
	 	if( ! check_admin_referer( 'wpcrm_plugin_license_nonce', 'wpcrm_plugin_license_nonce' ) )
			return; // get out if we didn't click the Activate button

		// retrieve the license from the database
		$license = trim( get_option( 'wpcrm_email_notifications_license_key' ) );


		// data to send in our API request
		$api_params = array(
			'edd_action'=> 'deactivate_license',
			'license' 	=> $license,
			'item_name' => urlencode( WPCRM_EMAIL_NOTIFICATIONS_NAME ), // the name of our product in EDD
			'url'       => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( WPCRM_BASE_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		// make sure the response came back okay
		if( is_wp_error( $response ) )
			return false;

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "deactivated" or "failed"
		if( $license_data->license == 'deactivated' )
			delete_option( 'wpcrm_email_notifications_license_status' );

	}
}
add_action('admin_init', 'wpcrm_email_notifications_deactivate_license');
/* End Updater */

/* Load Text Domain */
add_action('plugins_loaded', 'wp_crm_email_notifications_plugin_init');
function wp_crm_email_notifications_plugin_init() {
	load_plugin_textdomain( 'wp-crm-system-email-notifications', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
}
add_action( 'admin_init', 'email_notifications_check_has_wpcrm' );
function email_notifications_check_has_wpcrm() {
    if( is_admin() && current_user_can( 'activate_plugins' ) &&  (!is_plugin_active( 'wp-crm-system/wp-crm-system.php' ) ) ) {
        add_action( 'admin_notices', 'wpcrm_email_notice' );

        deactivate_plugins( plugin_basename( __FILE__ ) );

        if( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }
}
function wpcrm_email_notice(){
    ?><div class="error"><p><?php _e('Sorry, but WP-CRM System Email Notifications requires WP-CRM to be installed and active.','wp-crm-system-email-notifications'); ?></p></div><?php
}
//Register Settings
register_activation_hook(__FILE__, 'activate_wpcrm_email_system_settings');
register_uninstall_hook(__FILE__, 'deactivate_wpcrm_email_system_settings');
add_action('admin_init', 'register_wpcrm_email_system_settings');
function activate_wpcrm_email_system_settings() {
	$email_fields = array('enable_email_notification','enable_html_email','email_task_message','email_opportunity_message','email_project_message');
	include (WP_PLUGIN_DIR.'/wp-crm-system/includes/wp-crm-system-vars.php');
	foreach ($email_fields as $email) {
		add_option($prefix.$email, '');
	}
}
function deactivate_wpcrm_email_system_settings() {
	$email_fields = array('enable_email_notification','enable_html_email','email_task_message','email_opportunity_message','email_project_message');
	include (WP_PLUGIN_DIR.'/wp-crm-system/includes/wp-crm-system-vars.php');
	foreach ($email_fields as $email) {
		delete_option($prefix.$email);
	}
}
function register_wpcrm_email_system_settings() {
	$email_fields = array('enable_email_notification','enable_html_email','email_task_message','email_opportunity_message','email_project_message');
	include (WP_PLUGIN_DIR.'/wp-crm-system/includes/wp-crm-system-vars.php');
	foreach ($email_fields as $email) {
		register_setting( 'wpcrm-email-notifications', $prefix.$email);
	}
}
// Send Email notifications for Tasks
function wpcrm_notify_email_tasks( $ID, $post ){
	include (WP_PLUGIN_DIR.'/wp-crm-system/includes/wp-crm-system-vars.php');
	$enable_email = '';
	$taskassigned = '';
	$taskmessage = '';
	if(get_option($prefix.'enable_email_notification') == 'yes') {
		$enable_email = get_option($prefix.'enable_email_notification' );
	}
	if( '' != trim(get_option($prefix.'email_task_message'))) {
		$taskmessage = get_option($prefix.'email_task_message');
	}
	if(isset($_POST[$prefix.'task-assignment']) && $_POST[$prefix.'task-assignment'] != '') {
		$taskassigned = $_POST[$prefix.'task-assignment'];
	}
    if( '' == $enable_email || '' == $taskassigned || '' == $taskmessage ){
        return;
    }

	//Make sure post meta is updated before we use it
	if(isset($_POST[$prefix.'task-assignment']) && $_POST[$prefix.'task-assignment'] != '') {
		update_post_meta($ID,$prefix.'task-assignment',$_POST[$prefix.'task-assignment']);
	}
	if(isset($_POST[$prefix.'task-attach-to-organization']) && $_POST[$prefix.'task-attach-to-organization'] != '') {
		update_post_meta($ID,$prefix.'task-attach-to-organization',$_POST[$prefix.'task-attach-to-organization']);
	}
	if(isset($_POST[$prefix.'task-attach-to-contact']) && $_POST[$prefix.'task-attach-to-contact'] != '') {
		update_post_meta($ID,$prefix.'task-attach-to-contact',$_POST[$prefix.'task-attach-to-contact']);
	}
	if(isset($_POST[$prefix.'task-due-date']) && $_POST[$prefix.'task-due-date'] != '') {
		update_post_meta($ID,$prefix.'task-due-date',$_POST[$prefix.'task-due-date']);
	}
	if(isset($_POST[$prefix.'task-start-date']) && $_POST[$prefix.'task-start-date'] != '') {
		update_post_meta($ID,$prefix.'task-start-date',$_POST[$prefix.'task-start-date']);
	}
	if(isset($_POST[$prefix.'task-progress']) && $_POST[$prefix.'task-progress'] != '') {
		update_post_meta($ID,$prefix.'task-progress',$_POST[$prefix.'task-progress']);
	}
	if(isset($_POST[$prefix.'task-priority']) && $_POST[$prefix.'task-priority'] != '') {
		update_post_meta($ID,$prefix.'task-priority',$_POST[$prefix.'task-priority']);
	}
	if(isset($_POST[$prefix.'task-status']) && $_POST[$prefix.'task-status'] != '') {
		update_post_meta($ID,$prefix.'task-status',$_POST[$prefix.'task-status']);
	}

	//Specific data to send in email.
	$title = $post->post_title;
	$edit = get_edit_post_link( $ID, '' );

	$user = get_post_meta( $ID, $prefix.'task-assignment', true );
	if($user != ''){
		$assignedUsername = get_user_by('login',$user);
		$assigned = $assignedUsername->display_name;
		$email = $assignedUsername->user_email;
		$name = $assignedUsername->first_name . ' ' . $assignedUsername->last_name;
	} else {
		$assigned = __('Not assigned','wp-crm-system-email-notifications');
	}
	$org = get_post_meta( $ID, $prefix.'task-attach-to-organization', true );
	if($org == '') {
		$org = __('Not set','wp-crm-system-email-notifications');
	} else {
		$org = get_the_title($org);
	}
	$contact = get_post_meta( $ID, $prefix.'task-attach-to-contact', true );
	if($contact == '') {
		$contact = __('Not set','wp-crm-system-email-notifications');
	} else {
		$contact = get_the_title($contact);
	}
	$due = get_post_meta( $ID, $prefix.'task-due-date', true );
	if($due != '') {
		$duedate = $due;
	} else {
		$duedate = __('No due date set','wp-crm-system-email-notifications');
	}
	$start = get_post_meta( $ID, $prefix.'task-start-date', true );
	if($start != '') {
		$startdate = $start;
	} else {
		$startdate = __('No start date set','wp-crm-system-email-notifications');
	}

	$progress = get_post_meta( $ID, $prefix.'task-progress', true ) . '%';

	$priority = get_post_meta( $ID, $prefix.'task-priority', true );
	$priorityargs = array(''=>__('Not set', 'wp-crm-system-email-notifications'),'low'=>_x('Low','Not of great importance','wp-crm-system-email-notifications'),'medium'=>_x('Medium','Average priority','wp-crm-system-email-notifications'),'high'=>_x('High','Greatest importance','wp-crm-system-email-notifications'));
	if(isset($priorityargs[$priority])) {
		$sendpriority = $priorityargs[$priority];
	}
	$status = get_post_meta( $ID, $prefix.'task-status', true );
	$statusargs = array('not-started'=>_x('Not Started','Work has not yet begun.','wp-crm-system-email-notifications'),'in-progress'=>_x('In Progress','Work has begun but is not complete.','wp-crm-system-email-notifications'),'complete'=>_x('Complete','All tasks are finished. No further work is needed.','wp-crm-system-email-notifications'),'on-hold'=>_x('On Hold','Work may be in various stages of completion, but has been stopped for one reason or another.','wp-crm-system-email-notifications'));
	if(isset($statusargs[$status])) {
		$sendstatus = $statusargs[$status];
	}
	$vars = array(
		'{title}' 			=> $title,
		'{url}'				=> $edit,
		'{titlelink}'		=> "<a href='" . $edit . "'>" . $title . "</a>",
		'{assigned}'		=> $assigned,
		'{organization}'	=> $org,
		'{contact}'			=> $contact,
		'{due}'				=> $duedate,
		'{start}'			=> $startdate,
		'{progress}'		=> $progress,
		'{priority}'		=> $sendpriority,
		'{status}'			=> $sendstatus,
	);
	// Setup wp_mail
	$to = sprintf( '%s <%s>', $name, $email );
	$subject = $title . ' Update';
    $message = strtr($taskmessage,$vars);
    if(get_option($prefix . 'enable_html_email') == 'yes') {
		$headers = 'Content-type: text/html';
	} else {
		$headers = '';
	}
	wp_mail( $to, $subject, $message, $headers );
    return;
}
add_action( 'publish_wpcrm-task', 'wpcrm_notify_email_tasks', 10, 2 );

// Send Email notifications for Opportunities
function wpcrm_notify_email_opportunities( $ID, $post ){
	include (WP_PLUGIN_DIR.'/wp-crm-system/includes/wp-crm-system-vars.php');
	$enable_email = '';
	$opportunityassigned = '';
	$opportunitymessage = '';
	if(get_option($prefix.'enable_email_notification') == 'yes') {
		$enable_email = get_option($prefix.'enable_email_notification' );
	}
	if( '' != trim(get_option($prefix.'email_opportunity_message'))) {
		$opportunitymessage = get_option($prefix.'email_opportunity_message');
	}
	if(isset($_POST[$prefix.'opportunity-assigned']) && $_POST[$prefix.'opportunity-assigned'] != '') {
		$opportunityassigned = $_POST[$prefix.'opportunity-assigned'];
	}
    if( '' == $enable_email || '' == $opportunityassigned || '' == $opportunitymessage ) {
        return;
    }

	//Make sure post meta is available and updated before we use it
	if(isset($_POST[$prefix.'opportunity-assigned']) && $_POST[$prefix.'opportunity-assigned'] != '') {
		update_post_meta($ID,$prefix.'opportunity-assigned',$_POST[$prefix.'opportunity-assigned']);
	}
	if(isset($_POST[$prefix.'opportunity-attach-to-organization']) && $_POST[$prefix.'opportunity-attach-to-organization'] != '') {
		update_post_meta($ID,$prefix.'opportunity-attach-to-organization',$_POST[$prefix.'opportunity-attach-to-organization']);
	}
	if(isset($_POST[$prefix.'opportunity-attach-to-contact']) && $_POST[$prefix.'opportunity-attach-to-contact'] != '') {
		update_post_meta($ID,$prefix.'opportunity-attach-to-contact',$_POST[$prefix.'opportunity-attach-to-contact']);
	}
	if(isset($_POST[$prefix.'opportunity-probability']) && $_POST[$prefix.'opportunity-probability'] != '') {
		update_post_meta($ID,$prefix.'opportunity-probability',$_POST[$prefix.'opportunity-probability']);
	}
	if(isset($_POST[$prefix.'opportunity-closedate']) && $_POST[$prefix.'opportunity-closedate'] != '') {
		update_post_meta($ID,$prefix.'opportunity-closedate',$_POST[$prefix.'opportunity-closedate']);
	}
	if(isset($_POST[$prefix.'opportunity-value']) && $_POST[$prefix.'opportunity-value'] != '') {
		update_post_meta($ID,$prefix.'opportunity-value',$_POST[$prefix.'opportunity-value']);
	}
	if(isset($_POST[$prefix.'opportunity-wonlost']) && $_POST[$prefix.'opportunity-wonlost'] != '') {
		update_post_meta($ID,$prefix.'opportunity-wonlost',$_POST[$prefix.'opportunity-wonlost']);
	}


	//Specific data to send in email.
	$title = $post->post_title;
	$edit = get_edit_post_link( $ID, '' );

	$user = get_post_meta( $ID, $prefix.'opportunity-assigned', true );
	if($user != ''){
		$assignedUsername = get_user_by('login',$user);
		$assigned = $assignedUsername->display_name;
		$email = $assignedUsername->user_email;
		$name = $assignedUsername->first_name . ' ' . $assignedUsername->last_name;
	} else {
		$assigned = __('Not assigned','wp-crm-system-email-notifications');
	}
	$org = get_post_meta( $ID, $prefix.'opportunity-attach-to-organization', true );
	if($org == '') {
		$org = __('Not set','wp-crm-system-email-notifications');
	} else {
		$org = get_the_title($org);
	}
	$contact = get_post_meta( $ID, $prefix.'opportunity-attach-to-contact', true );
	if($contact == '') {
		$contact = __('Not set','wp-crm-system-email-notifications');
	} else {
		$contact = get_the_title($contact);
	}
	$probability = get_post_meta( $ID, $prefix.'opportunity-probability', true );
	if($probability == '') {
		$probability = __('Not set','wp-crm-system-email-notifications');
	} else {
		$probability = $probability.'%';
	}
	$close = get_post_meta( $ID, $prefix.'opportunity-closedate', true );
	if($close != '') {
		$closedate = $close;
	} else {
		$closedate = __('No close date set','wp-crm-system-email-notifications');
	}
	$value = get_post_meta( $ID, $prefix.'opportunity-value', true );
	if($value == '') {
		$value = __('Not set','wp-crm-system-email-notifications');
	} else {
		$currency = get_option('wpcrm_system_default_currency');
		$value = strtoupper($currency) . ' ' . number_format( $value,get_option('wpcrm_system_report_currency_decimals'),get_option('wpcrm_system_report_currency_decimal_point'),get_option('wpcrm_system_report_currency_thousand_separator'));
	}
	$status = get_post_meta( $ID, $prefix.'opportunity-wonlost', true );
	$statusargs = array('not-set'=>__('Select an option', 'wp-crm-system'),'won'=>_x('Won','Successful, a winner.','wp-crm-system'),'lost'=>_x('Lost','Unsuccessful, a loser.','wp-crm-system'),'suspended'=>_x('Suspended','Temporarily ended, but may resume again.','wp-crm-system'),'abandoned'=>_x('Abandoned','No longer actively working on.','wp-crm-system'));
	if(isset($statusargs[$status])) {
		$sendstatus = $statusargs[$status];
	}

	$vars = array(
		'{title}' 			=> $title,
		'{url}'				=> $edit,
		'{titlelink}'		=> "<a href='" . $edit . "'>" . $title . "</a>",
		'{assigned}'		=> $assigned,
		'{organization}'	=> $org,
		'{contact}'			=> $contact,
		'{close}'			=> $closedate,
		'{probability}'		=> $probability,
		'{value}'			=> $value,
		'{status}'			=> $sendstatus,
	);

	// Setup wp_mail
	$to = sprintf( '%s <%s>', $name, $email );
	$subject = $title . ' Update';
    $message = strtr($opportunitymessage,$vars);
	if(get_option($prefix . 'enable_html_email') == 'yes') {
		$headers = 'Content-type: text/html';
	} else {
		$headers = '';
	}
	wp_mail( $to, $subject, $message, $headers );
    return;
}
add_action( 'publish_wpcrm-opportunity', 'wpcrm_notify_email_opportunities', 10, 2 );

// Send Email notifications for Projects
function wpcrm_notify_email_projects( $ID, $post ){
	include (WP_PLUGIN_DIR.'/wp-crm-system/includes/wp-crm-system-vars.php');
	$enable_email = '';
	$projectassigned = '';
	$projectmessage = '';
	if(get_option($prefix.'enable_email_notification') == 'yes') {
		$enable_email   = get_option($prefix.'enable_email_notification' );
	}
	if( '' != trim(get_option($prefix.'email_project_message'))) {
		$projectmessage = get_option($prefix.'email_project_message');
	}
	if(isset($_POST[$prefix.'project-assigned']) && $_POST[$prefix.'project-assigned'] != '') {
		$projectassigned = $_POST[$prefix.'project-assigned'];
	}
    if( '' == $enable_email || '' == $projectassigned || '' == $projectmessage ){
        return;
    }

	//Make sure post meta is updated before we use it
	if(isset($_POST[$prefix.'project-assigned']) && $_POST[$prefix.'project-assigned'] != '') {
		update_post_meta($ID,$prefix.'project-assigned',$_POST[$prefix.'project-assigned']);
	}
	if(isset($_POST[$prefix.'project-attach-to-organization']) && $_POST[$prefix.'project-attach-to-organization'] != '') {
		update_post_meta($ID,$prefix.'project-attach-to-organization',$_POST[$prefix.'project-attach-to-organization']);
	}
	if(isset($_POST[$prefix.'project-attach-to-contact']) && $_POST[$prefix.'project-attach-to-contact'] != '') {
		update_post_meta($ID,$prefix.'project-attach-to-contact',$_POST[$prefix.'project-attach-to-contact']);
	}
	if(isset($_POST[$prefix.'project-progress']) && $_POST[$prefix.'project-progress'] != '') {
		update_post_meta($ID,$prefix.'project-progress',$_POST[$prefix.'project-progress']);
	}
	if(isset($_POST[$prefix.'project-closedate']) && $_POST[$prefix.'project-closedate'] != '') {
		update_post_meta($ID,$prefix.'project-closedate',$_POST[$prefix.'project-closedate']);
	}
	if(isset($_POST[$prefix.'project-value']) && $_POST[$prefix.'project-value'] != '') {
		update_post_meta($ID,$prefix.'project-value',$_POST[$prefix.'project-value']);
	}
	if(isset($_POST[$prefix.'project-status']) && $_POST[$prefix.'project-status'] != '') {
		update_post_meta($ID,$prefix.'project-status',$_POST[$prefix.'project-status']);
	}


	//Specific data to send in email.
	$title = $post->post_title;
	$edit = get_edit_post_link( $ID, '' );

	$user = get_post_meta( $ID, $prefix.'project-assigned', true );
	if($user != ''){
		$assignedUsername = get_user_by('login',$user);
		$assigned = $assignedUsername->display_name;
		$email = $assignedUsername->user_email;
		$name = $assignedUsername->first_name . ' ' . $assignedUsername->last_name;
	} else {
		$assigned = __('Not assigned','wp-crm-system-email-notifications');
	}
	$org = get_post_meta( $ID, $prefix.'project-attach-to-organization', true );
	if($org == '') {
		$org = __('Not set','wp-crm-system-email-notifications');
	} else {
		$org = get_the_title($org);
	}
	$contact = get_post_meta( $ID, $prefix.'project-attach-to-contact', true );
	if($contact == '') {
		$contact = __('Not set','wp-crm-system-email-notifications');
	} else {
		$contact = get_the_title($contact);
	}

	$progress = get_post_meta( $ID, $prefix . 'project-progress', true ) . '%';

	$close = get_post_meta( $ID, $prefix.'project-closedate', true );
	if($close != '') {
		$closedate = $close;
	} else {
		$closedate = __('No close date set','wp-crm-system-email-notifications');
	}
	$value = get_post_meta( $ID, $prefix.'project-value', true );
	if($value == '') {
		$value = __('Not set','wp-crm-system-email-notifications');
	} else {
		$currency = get_option('wpcrm_system_default_currency');
		$value = strtoupper($currency) . ' ' . number_format( $value,get_option('wpcrm_system_report_currency_decimals'),get_option('wpcrm_system_report_currency_decimal_point'),get_option('wpcrm_system_report_currency_thousand_separator'));
	}
	$status = get_post_meta( $ID, $prefix.'project-status', true );
	$statusargs = array('not-started'=>_x('Not Started','Work has not yet begun.','wp-crm-system-email-notifications'),'in-progress'=>_x('In Progress','Work has begun but is not complete.','wp-crm-system-email-notifications'),'complete'=>_x('Complete','All tasks are finished. No further work is needed.','wp-crm-system-email-notifications'),'on-hold'=>_x('On Hold','Work may be in various stages of completion, but has been stopped for one reason or another.','wp-crm-system-email-notifications'));
	if(isset($statusargs[$status])) {
		$sendstatus = $statusargs[$status];
	}

	$vars = array(
		'{title}' 			=> $title,
		'{url}'				=> $edit,
		'{titlelink}'		=> "<a href='" . $edit . "'>" . $title . "</a>",
		'{assigned}'		=> $assigned,
		'{organization}'	=> $org,
		'{contact}'			=> $contact,
		'{close}'			=> $closedate,
		'{progress}'		=> $progress,
		'{value}'			=> $value,
		'{status}'			=> $sendstatus,
	);

    // Setup wp_mail
	$to = sprintf( '%s <%s>', $name, $email );
	$subject = $title . ' Update';
    $message = strtr($projectmessage,$vars);
	if(get_option($prefix . 'enable_html_email') == 'yes') {
		$headers = 'Content-type: text/html';
	} else {
		$headers = '';
	}
	wp_mail( $to, $subject, $message, $headers );
    return;
}
add_action( 'publish_wpcrm-project', 'wpcrm_notify_email_projects', 10, 2 );

// Add Email Notification Settings
function wpcrm_system_email_setting_tab() {
	global $wpcrm_active_tab; ?>
	<a class="nav-tab <?php echo $wpcrm_active_tab == 'email-notifications' ? 'nav-tab-active' : ''; ?>" href="?page=wpcrm-settings&tab=email-notifications"><?php _e('Email', 'wp-crm-system') ?></a>
<?php }
add_action( 'wpcrm_system_settings_tab', 'wpcrm_system_email_setting_tab' );

function wpcrm_email_settings_content() {
	global $wpcrm_active_tab;
	if ($wpcrm_active_tab == 'email-notifications') {
		include( plugin_dir_path( __FILE__ ) . 'settings.php' );
	}
}
add_action( 'wpcrm_system_settings_content', 'wpcrm_email_settings_content' );

// Add license key settings field
function wpcrm_email_license_field() {
	include( plugin_dir_path( __FILE__ ) . 'license.php' );
}
add_action( 'wpcrm_system_license_key_field', 'wpcrm_email_license_field' );

// Add license key status to Dashboard
function wpcrm_system_email_notifications_dashboard_license($plugins) {
	// the $plugins parameter is an array of all plugins

	$extra_plugins = array(
		'email-notifications'	=> 'wpcrm_email_notifications_license_status'
	);

	// combine the two arrays
	$plugins = array_merge($extra_plugins, $plugins);

	return $plugins;
}
add_filter('wpcrm_system_dashboard_extensions', 'wpcrm_system_email_notifications_dashboard_license');
