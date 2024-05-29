<?php
/**
 * Plugin Name: Abandoned Contact Form 7
 * Plugin URL: https://wordpress.org/plugins/abandoned-contact-form-7/
 * Description: Abandoned Contact Form 7 provides an ability to track the data from Contact Form 7 even if the user does not submit the form.
 * Version: 1.7
 * Author: ZealousWeb
 * Author URI: https://www.zealousweb.com
 * Developer: The Zealousweb Team
 * Developer E-Mail: opensource@zealousweb.com
 * Text Domain: cf7-abandoned-form
 * Domain Path: /languages
 *
 * Copyright: © 2009-2020 Plugin author name.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Basic plugin definitions
 *
 * @package Abandoned Contact Form 7
 * @since 1.0
 */

if ( !defined( 'CF7AF_VERSION' ) ) {
	define( 'CF7AF_VERSION', '1.7' ); // Version of plugin
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
	define( 'CF7AF_POST_TYPE', 'cf7af_data' ); // Plugin registered post type name
}

/**
 * Initialize the main class
 */
if ( !function_exists( 'CF7AF' ) ) {

	if ( is_admin() ) {
		require_once( CF7AF_DIR . '/inc/admin/class.' . CF7AF_PREFIX . '.admin.php' );
		require_once( CF7AF_DIR . '/inc/admin/class.' . CF7AF_PREFIX . '.admin.action.php' );
		require_once( CF7AF_DIR . '/inc/admin/class.' . CF7AF_PREFIX . '.admin.filter.php' );
	} else {
		require_once( CF7AF_DIR . '/inc/front/class.' . CF7AF_PREFIX . '.front.php' );
		require_once( CF7AF_DIR . '/inc/front/class.' . CF7AF_PREFIX . '.front.action.php' );
		require_once( CF7AF_DIR . '/inc/front/class.' . CF7AF_PREFIX . '.front.filter.php' );
	}

	//Initialize all the things.
	require_once( CF7AF_DIR . '/inc/class.' . CF7AF_PREFIX . '.php' );
}