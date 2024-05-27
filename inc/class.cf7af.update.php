<?php
/**
 * CF7AF_Update Class
 *
 * Handles the update functionality.
 *
 * @package WordPress
 * @subpackage Abandoned Contact Form 7
 * @since 1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CF7AF_Update' ) ) {

	class CF7AF_Update {
		/**
		 * The plugin current version
		 * @var string
		 */
		private $current_version;

		/**
		 * The plugin remote update path
		 * @var string
		 */
		private $update_path = 'https://www.zealousweb.com/wp-json/updates/v1/plugin-name';

		/**
		 * Plugin Slug (plugin_directory/plugin_file.php)
		 * @var string
		 */
		private $plugin_slug;

		/**
		 * Abandoned Contact Form 7 (plugin_file)
		 * @var string
		 */
		private $slug;

		/**
		 * License User
		 * @var string
		 */
		private $license_user;

		/**
		 * License Key
		 * @var string
		 */
		private $license_key;

		/**
		 * Initialize a new instance of the WordPress Auto-Update class
		 * @param string $current_version
		 * @param string $update_path
		 * @param string $plugin_slug
		 */
		public function __construct( $current_version, $plugin_slug, $license_user = '', $license_key = '' ) {
			// Set the class public variables
			$this->current_version = $current_version;

			// Set the License
			$this->license_user = $license_user;
			$this->license_key = $license_key;

			// Set the Plugin Slug
			$this->plugin_slug = $plugin_slug;
			list ($t1, $t2) = explode( '/', $plugin_slug );
			$this->slug = str_replace( '.php', '', $t2 );

			// set_site_transient('update_plugins', null);

			// define the alternative API for updating checking
			add_filter( 'pre_set_site_transient_update_plugins', array( &$this, 'check_update' ) );

			// Define the alternative response for information checking
			add_filter( 'plugins_api',               array( &$this, 'check_info' ), 10, 3 );
			add_action( 'upgrader_process_complete', array( &$this, 'action__upgrader_process_complete' ), 10, 2 );

			$hook = 'in_plugin_update_message-' . CF7AF_PLUGIN_BASENAME;
			add_action( $hook , array( &$this, 'action__in_plugin_update_message' ), 10, 2 );
		}

		/**
		 * Add our self-hosted autoupdate plugin to the filter transient
		 *
		 * @param $transient
		 * @return object $ transient
		 */
		public function check_update( $transient ) {
			if ( empty( $transient->checked ) ) {
				return $transient;
			}

			// Get the remote version
			$remote_version = $this->getRemote( 'version' );

			// If a newer version is available, add the update
			if ( isset( $remote_version->new_version ) && version_compare( $this->current_version, $remote_version->new_version, '<' ) ) {
				$obj = new stdClass();


				$obj->slug = $this->slug;
				$obj->new_version = $remote_version->new_version;
				$obj->url = $remote_version->url;
				$obj->plugin = $this->plugin_slug;
				$obj->package = $remote_version->package;
				$obj->tested = $remote_version->tested;
				$transient->response[$this->plugin_slug] = $obj;
			}

			return $transient;
		}

		/**
		 * Add our self-hosted description to the filter
		 *
		 * @param boolean $false
		 * @param array $action
		 * @param object $arg
		 * @return bool|object
		 */
		public function check_info( $obj, $action, $arg ) {
			if (
				(
					$action == 'query_plugins'
					|| $action == 'plugin_information'
				)
				&& isset( $arg->slug )
				&& $arg->slug === $this->slug
			) {
				return $this->getRemote('info');
			}

			return $obj;
		}

		/**
		 * Return the remote version
		 *
		 * @return string $remote_version
		 */
		public function getRemote( $action = '' ) {
			$params = array(
				'body' => array(
					'action'       => $action,
					'license_user' => $this->license_user,
					'license_key'  => $this->license_key,
				),
			);

			// Make the POST request
			$request = wp_remote_post( $this->update_path, $params );

			// Check if response is valid
			if (
				!is_wp_error( $request )
				|| wp_remote_retrieve_response_code( $request ) === 200
			) {
				return @unserialize( $request['body'] );
			}

			return false;
		}

		function action__upgrader_process_complete() {
			set_site_transient('update_plugins', null);
		}

		function action__in_plugin_update_message( $plugin_info_array, $plugin_info_object ) {
			if ( empty( $plugin_info_array['package'] ) ) {
				echo ' Please <a href="' . esc_url( admin_url( 'admin.php?post_type='.CF7AF_POST_TYPE.'&page="cf7af-license-activation"' ) ) . '">add your license</a> to update.';
			}
		}
	}
}


