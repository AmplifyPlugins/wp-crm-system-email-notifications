<?php
/*
    Plugin Name: WP-CRM System Email Notifications
    Plugin URL: https://www.wp-crm.com
    Description: Send notifications from WP-CRM to the assigned user's email address.
    Version: 2.0.8
    Author: Scott DeLuzio
    Author URI: https://www.wp-crm.com
    Text Domain: wp-crm-system-email-notifications
*/

	/*  Copyright 2015  Scott DeLuzio (email : support (at) wp-crm.com)	*/

if( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
define( 'WPCRM_EMAIL_NOTIFICATIONS', __FILE__ );
define( 'WPCRM_EMAIL_NOTIFICATIONS_VERSION', '2.0.8' );
define( 'WPCRM_EMAIL_NOTIFICATIONS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPCRM_EMAIL_NOTIFICATIONS_PLUGIN_DIR', dirname( __FILE__ ) );

/**
 * Includes
 */
include( WPCRM_EMAIL_NOTIFICATIONS_PLUGIN_DIR . '/includes/admin-notices.php' );
include( WPCRM_EMAIL_NOTIFICATIONS_PLUGIN_DIR . '/includes/admin-settings.php' );
include( WPCRM_EMAIL_NOTIFICATIONS_PLUGIN_DIR . '/includes/opportunity-notifications.php' );
include( WPCRM_EMAIL_NOTIFICATIONS_PLUGIN_DIR . '/includes/project-notifications.php' );
include( WPCRM_EMAIL_NOTIFICATIONS_PLUGIN_DIR . '/includes/task-notifications.php' );
/* Start Updater */
if(!defined( 'WPCRM_BASE_STORE_URL' ) ){
	define( 'WPCRM_BASE_STORE_URL', 'http://wp-crm.com' );
}
// the name of your product. This should match the download name in EDD exactly
define( 'WPCRM_EMAIL_NOTIFICATIONS_NAME', 'Email Notifications' ); // you should use your own CONSTANT name, and be sure to replace it throughout this file

if( !class_exists( 'WPCRM_SYSTEM_SL_Plugin_Updater' ) ) {
	// load our custom updater
	include( dirname( WPCRM_EMAIL_NOTIFICATIONS ) . '/EDD_SL_Plugin_Updater.php' );
}

function wpcrm_email_notifications_updater() {

	// retrieve our license key from the DB
	$license_key = trim( get_option( 'wpcrm_email_notifications_license_key' ) );

	// setup the updater
	$edd_updater = new WPCRM_SYSTEM_SL_Plugin_Updater( WPCRM_BASE_STORE_URL, WPCRM_EMAIL_NOTIFICATIONS, array(
			'version' 	=> WPCRM_EMAIL_NOTIFICATIONS_VERSION,	// current version number
			'license' 	=> $license_key,						// license key (used get_option above to retrieve from DB)
			'item_name' => WPCRM_EMAIL_NOTIFICATIONS_NAME,		// name of this plugin
			'author' 	=> 'Scott DeLuzio'						// author of this plugin
		)
	);

}
add_action( 'admin_init', 'wpcrm_email_notifications_updater', 0 );

function wpcrm_email_notifications_register_option() {
	// creates our settings in the options table
	register_setting( 'wpcrm_license_group', 'wpcrm_email_notifications_license_key', 'wpcrm_email_notifications_sanitize_license' );
}
add_action( 'admin_init', 'wpcrm_email_notifications_register_option' );

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
add_action( 'admin_init', 'wpcrm_email_notifications_activate_license' );

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
add_action( 'admin_init', 'wpcrm_email_notifications_deactivate_license' );
/* End Updater */

/* Load Text Domain */
add_action( 'plugins_loaded', 'wp_crm_email_notifications_plugin_init' );
function wp_crm_email_notifications_plugin_init() {
	load_plugin_textdomain( 'wp-crm-system-email-notifications', false, dirname( WPCRM_EMAIL_NOTIFICATIONS_PLUGIN_BASENAME ) . '/lang' );
}

//Register Settings
register_activation_hook( WPCRM_EMAIL_NOTIFICATIONS, 'activate_wpcrm_email_system_settings' );
register_uninstall_hook( WPCRM_EMAIL_NOTIFICATIONS, 'deactivate_wpcrm_email_system_settings' );
add_action( 'admin_init', 'register_wpcrm_email_system_settings' );
function activate_wpcrm_email_system_settings() {
	$email_fields = array( 'enable_email_notification', 'enable_html_email', 'email_task_message', 'email_opportunity_message', 'email_project_message' );
	include ( WP_PLUGIN_DIR . '/wp-crm-system/includes/wp-crm-system-vars.php' );
	foreach ( $email_fields as $email ) {
		add_option( $prefix . $email, '' );
	}
}
function deactivate_wpcrm_email_system_settings() {
	$email_fields = array( 'enable_email_notification', 'enable_html_email', 'email_task_message', 'email_opportunity_message', 'email_project_message' );
	include ( WP_PLUGIN_DIR . '/wp-crm-system/includes/wp-crm-system-vars.php' );
	foreach ( $email_fields as $email ) {
		delete_option( $prefix . $email );
	}
}
function register_wpcrm_email_system_settings() {
	$email_fields = array( 'enable_email_notification', 'enable_html_email', 'email_task_message', 'email_opportunity_message', 'email_project_message' );
	include ( WP_PLUGIN_DIR . '/wp-crm-system/includes/wp-crm-system-vars.php' );
	foreach ( $email_fields as $email ) {
		register_setting( 'wpcrm-email-notifications', $prefix . $email );
	}
}

// Add license key settings field
function wpcrm_email_license_field() {
	include( plugin_dir_path( WPCRM_EMAIL_NOTIFICATIONS ) . 'license.php' );
}
add_action( 'wpcrm_system_license_key_field', 'wpcrm_email_license_field' );

// Add license key status to Dashboard
function wpcrm_system_email_notifications_dashboard_license( $plugins ) {
	// the $plugins parameter is an array of all plugins

	$extra_plugins = array(
		'email-notifications'	=> 'wpcrm_email_notifications_license_status'
	);

	// combine the two arrays
	$plugins = array_merge( $extra_plugins, $plugins );

	return $plugins;
}
add_filter( 'wpcrm_system_dashboard_extensions', 'wpcrm_system_email_notifications_dashboard_license' );
