<?php
/**
 * Plugin Name: Abandoned Contact Form 7
 * Plugin URL: https://wordpress.org/plugins/abandoned-contact-form-7/
 * Description: Abandoned Contact Form 7 provides an ability to track the data from Contact Form 7 even if the user does not submit the form.
 * Version: 2.7
 * Requires at least: 6.2
 * Requires PHP: 7.0
 * Requires Plugins: contact-form-7
 * Author: ZealousWeb
 * Author URI: https://www.zealousweb.com
 * Developer: The Zealousweb Team
 * Developer E-Mail: opensource@zealousweb.com
 * Text Domain: abandoned-contact-form-7
 * Domain Path: /languages
 *
 * Copyright: © 2009-2020 Plugin author name.
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Basic plugin definitions
 *
 * @package Abandoned Contact Form 7
 * @since 2.7
 */

if ( !defined( 'CF7AF_VERSION' ) ) {
	define( 'CF7AF_VERSION', '2.7' ); // Version of plugin
}

if ( !defined( 'CF7AF_FILE' ) ) {
	define( 'CF7AF_FILE', __FILE__ ); // Plugin File
}

if ( !defined( 'CF7AF_DIR' ) ) {
	define( 'CF7AF_DIR', dirname( __FILE__ ) ); // Plugin dir
}

if ( !defined( 'CF7AF_URL' ) ) {
	define( 'CF7AF_URL', plugin_dir_url( __FILE__ ) ); // Plugin url
}

if ( !defined( 'CF7AF_PLUGIN_BASENAME' ) ) {
	define( 'CF7AF_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); // Plugin base name
}

if ( !defined( 'CF7AF_META_PREFIX' ) ) {
	define( 'CF7AF_META_PREFIX', 'cf7af_' ); // Plugin metabox prefix
}

if ( !defined( 'CF7AF_PREFIX' ) ) {
	define( 'CF7AF_PREFIX', 'cf7af' ); // Plugin prefix
}

if( !defined( 'CF7AF_POST_TYPE' ) ) {
	define( 'CF7AF_POST_TYPE', 'cf7af_data' );
}

if ( ! defined( 'CF7AF_ADMIN_STYLE_HANDLE' ) ) {
	define( 'CF7AF_ADMIN_STYLE_HANDLE', 'cf7af-admin-style' );
}

if ( ! defined( 'CF7AF_ADMIN_SCRIPT_HANDLE' ) ) {
	define( 'CF7AF_ADMIN_SCRIPT_HANDLE', 'cf7af-admin' );
}

if ( ! defined( 'CF7AF_FRONT_SCRIPT_HANDLE' ) ) {
	define( 'CF7AF_FRONT_SCRIPT_HANDLE', 'cf7af-front' );
}

if ( ! defined( 'CF7AF_OPTION_MAIL_NOTIFY' ) ) {
	define( 'CF7AF_OPTION_MAIL_NOTIFY', 'cf7af_mail_notify_option' );
}

if ( ! defined( 'CF7AF_OPTION_POST_DATA_SYNCED' ) ) {
	define( 'CF7AF_OPTION_POST_DATA_SYNCED', 'cf7af_post_data_synced' );
}

/**
 * Initialize the main class
 */
if ( !function_exists( 'CF7AF' ) ) {

	require_once CF7AF_DIR . '/inc/lib/class.cf7af.helpers.php';

	if ( is_admin() ) {
		require_once( CF7AF_DIR . '/inc/admin/class.' . CF7AF_PREFIX . '.admin.php' );
		require_once( CF7AF_DIR . '/inc/admin/class.' . CF7AF_PREFIX . '.admin.action.php' );
		require_once( CF7AF_DIR . '/inc/admin/class.' . CF7AF_PREFIX . '.admin.filter.php' );
	} else {
		require_once( CF7AF_DIR . '/inc/front/class.' . CF7AF_PREFIX . '.front.php' );
		require_once( CF7AF_DIR . '/inc/front/class.' . CF7AF_PREFIX . '.front.action.php' );
	}

	//Initialize all the things.
	require_once( CF7AF_DIR . '/inc/class.' . CF7AF_PREFIX . '.php' );
}
