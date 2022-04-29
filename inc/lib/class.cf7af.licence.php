<?php
if ( !class_exists( 'CF7AF_Licence' ) ) {

	class CF7AF_Licence {

		static $status = null;

		private static $_instance = null;
		static $activation_menuname = 'License Activation',
		    $licence_status = 'cf7af_addon_license_status',
		    $activation_redirect = 'cf7af_addon_activation_redirect',
		    $licence_nonce = 'cf7af_addon_nonce' ,
		    $activation_action = 'cf7af_addon_license_activate' ,
		    $zw_deactivation_action = 'cf7af_addon_license_deactivate',
		    $valid_url = 'https://www.zealousweb.com/wp-json/activator/v1/activate/',
		    $item_name = 'Abandoned Contact Form 7' ,
		    $license_page = 'cf7af-license-activation',
		    $item_id = '14608'; // WooCommerce product ID 13667

		const cf7af_licence_key = 'cf7af_addon_license_key',
		      cf7af_licence_email = 'cf7af_addon_license_email';

		public static function instance() {
			return self::$status;
		}

		function __construct() {

			self::$status = get_option( self::$licence_status );

			register_activation_hook( CF7AF_FILE, array( $this, 'zw_licence_extension' ) );
			add_action( 'setup_theme',   array( $this, 'action__setup_theme' ) );
			add_action( 'rest_api_init', array( $this, 'action__rest_api_init' ) );
			add_action( 'admin_init',    array( $this, 'zw_licence_check_activation' ) );
			add_action( 'admin_init',    array( $this, 'zw_licence_activate_license' ) );
			add_action( 'admin_init',    array( $this, 'zw_licence_deactivate_license' ) );
			add_action( 'admin_menu',    array( $this, 'zw_licence_menu' ),999 );
			add_action( 'admin_notices', array( $this, 'zw_licence_admin_notices') );
			register_deactivation_hook( CF7AF_FILE, array( $this, 'zw_licence_extension_deactivation' ) );
		}

		/*
		   ###     ######  ######## ####  #######  ##    ##  ######
		  ## ##   ##    ##    ##     ##  ##     ## ###   ## ##    ##
		 ##   ##  ##          ##     ##  ##     ## ####  ## ##
		##     ## ##          ##     ##  ##     ## ## ## ##  ######
		######### ##          ##     ##  ##     ## ##  ####       ##
		##     ## ##    ##    ##     ##  ##     ## ##   ### ##    ##
		##     ##  ######     ##    ####  #######  ##    ##  ######
		*/

		function action__setup_theme() {
			if ( !empty( self::$status ) ) {

				if ( is_admin() ) {
					CF7AF()->admin = new CF7AF_Admin;
					CF7AF()->admin->action = new CF7AF_Admin_Action;
					CF7AF()->admin->filter = new CF7AF_Admin_Filter;
				} else {
					CF7AF()->front = new CF7AF_Front;
					CF7AF()->front->action = new CF7AF_Front_Action;
					CF7AF()->front->filter = new CF7AF_Front_Filter;
				}
			}
		}

		function action__rest_api_init() {
			register_rest_route(
				'licences',
				'/removed',
				array(
					'callback' =>  array( $this, 'api__removed' ),
				)
			);
		}

		function zw_licence_extension() {
			update_option( self::$activation_redirect, 'yes' );
			flush_rewrite_rules();
		}

		function zw_licence_check_activation() {

			if ( class_exists('WPCF7') ) { // Based on dependencies

				if ( 'yes' === get_option( self::$activation_redirect, 'no' ) ) {

					update_option( self::$activation_redirect, 'no' );

					if ( ! isset( $_GET['activate-multi'] ) ) {
						wp_redirect( admin_url( 'edit.php?post_type='.CF7AF_POST_TYPE.'&page=' . self::$license_page ) );
					}
				}
			}
		}

		function zw_licence_menu() {

			add_submenu_page(
				'edit.php?post_type='.CF7AF_POST_TYPE, // page name
				self::$activation_menuname,
				self::$activation_menuname,
				'manage_options',
				self::$license_page,
				array( __CLASS__, 'zw_license_page' )
			);
		}

		public static function zw_licence_activate_license() {

			// listen for our activate button to be clicked
			if ( isset( $_POST[ self::$activation_action ] ) ) {

				// run a quick security check
				if ( ! check_admin_referer( self::$licence_nonce, self::$licence_nonce ) ) {
					return;
				} // get out if we didn't click the Activate button

				// retrieve the license from the database
				$license = trim( get_option( self::cf7af_licence_key ) );
				$license_email = trim( get_option( self::cf7af_licence_email ) );

				// Save license key
				$license = sanitize_text_field($_POST['cf7af_license_key']);
				$license_email = sanitize_email($_POST['cf7af_license_email']);

				$license_data = array();

				// data to send in our API request
				$api_params = array(
					'action' => 'activate_license',
					'key'    => $license,
					'email'  => $license_email,
					'id'     => self::$item_id,
					'host'   => home_url()
				);

				// Call the custom API.
				$response = wp_remote_get( self::$valid_url, array(
					'timeout'   => 15,
					'sslverify' => false,
					'body'	  => $api_params
				) );

				$message = '';

				// make sure the response came back okay
				if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

					if ( is_wp_error( $response ) ) {
						$message = $response->get_error_message();
					} else {
						$message = __( 'An error occurred, please try again.' );
					}

				} else {

					$license_data = json_decode( wp_remote_retrieve_body( $response ), true );

					if ( is_array($license_data) && array_key_exists( 'success', $license_data ) && empty(  $license_data['success'] ) ) {

						switch( $license_data['error'] ) {

							case 'expired' :

								$message = sprintf(
									__( 'Your license key expired.', 'cf7-abandoned-form' )
								);
								break;

							case 'revoked' :

								$message = __( 'Your license key has been disabled.', 'cf7-abandoned-form' );
								break;

							case 'missing' :
								$message = __( 'Invalid license.', 'cf7-abandoned-form' );
								break;

							case 'invalid' :
							case 'site_inactive' :

								$message = __( 'Your license is not active for this URL.', 'cf7-abandoned-form' );
								break;

							case 'item_name_mismatch' :

								$message = sprintf( __( 'This appears to be an invalid license key for %s.' ), self::$item_name , 'cf7-abandoned-form' );
								break;

							case 'no_activations_left':

								$message = __( 'Your license key has reached its activation limit.' , 'cf7-abandoned-form' );
								break;

							default :

								$message = __( 'An error occurred, please try again.' , 'cf7-abandoned-form' );
								break;
						}
					}
				}

				// Check if anything passed on a message constituting a failure
				if ( ! empty( $message ) ) {
					$base_url = admin_url( 'edit.php?post_type='.CF7AF_POST_TYPE.'&page=' . self::$license_page );
					$redirect = add_query_arg( array('zw_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

					wp_redirect( $redirect );
					exit();
				}

				update_option( self::cf7af_licence_key, $license );
				update_option( self::cf7af_licence_email, $license_email );
				update_option( self::$licence_status, $license_data['license'] );

				$base_url = admin_url( 'edit.php?post_type='.CF7AF_POST_TYPE.'&page=' . self::$license_page );
				$redirect = add_query_arg( array('zw_activation' => 'true', 'message' => urlencode( 'success' ) ), $base_url );
				wp_redirect( $redirect );
				exit();
			}
		}

		public static function zw_licence_deactivate_license() {

			// listen for our activate button to be clicked
			if ( isset( $_POST[ self::$zw_deactivation_action ] ) ) {

				// run a quick security check
				if ( ! check_admin_referer( self::$licence_nonce, self::$licence_nonce ) ) {
					return;
				} // get out if we didn't click the Activate button

				// retrieve the license from the database
				$license_status = trim( get_option( self::$licence_status ) );
				$license = trim( get_option( self::cf7af_licence_key ) );
				$license_email = trim( get_option( self::cf7af_licence_email ) );

				// data to send in our API request
				$api_params = array(
					'action' => 'deactivate_license',
					'key'    => $license_status,
					'email'  => $license_email,
					'id'     => urlencode( self::$item_id ), // the name of our product in uo
					'host'   => home_url()
				);

				// Call the custom API.
				$response = wp_remote_get( self::$valid_url, array(
					'timeout'   => 15,
					'sslverify' => false,
					'body'      => $api_params
				) );

				// make sure the response came back okay
				if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

					if ( is_wp_error( $response ) ) {
						$message = $response->get_error_message();
					} else {
						$message = __( 'An error occurred, please try again.' , 'cf7-abandoned-form' );
					}

					$base_url = admin_url( 'edit.php?post_type='.CF7AF_POST_TYPE.'&page=' . self::$license_page );
					$redirect = add_query_arg( array( 'zw_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

					wp_redirect( $redirect );
					exit();
				}

				// decode the license data
				$license_data = json_decode( wp_remote_retrieve_body( $response ), true );

				// $license_data->license will be either "deactivated" or "failed"
				if ( $license_data['license'] == 'deactivated' || $license_data['license'] == 'failed' ) {
					delete_option( self::cf7af_licence_key );
					delete_option( self::cf7af_licence_email );
					delete_option( self::$licence_status );

					// Unschedules the events attached
					wp_clear_scheduled_hook( 'cf7af_send_notify_event' );
				}

				$base_url = admin_url( 'edit.php?post_type='.CF7AF_POST_TYPE.'&page=' . self::$license_page );
				$redirect = add_query_arg( array('zw_activation' => 'false', 'message' => urlencode( 'Successfully Deactivated!' ) ), $base_url );
				wp_redirect( $redirect );
				exit();
			}
		}

		function zw_licence_admin_notices() {
			if (
				isset( $_GET[ 'page' ] )
				&& self::$license_page == $_GET['page']
			) {

				if (
					isset( $_GET['zw_activation'] )
					&& isset( $_GET['zw_activation'] )
					&& ! empty( $_GET['message'] )
				) {

					switch( $_GET['zw_activation'] ) {

						case 'false':
							$message = urldecode( sanitize_text_field($_GET['message']) );
							?>
							<div class="error">
								<p><?php echo $message; ?></p>
							</div>
							<?php
							break;

						case 'true':
						default:
							?>
							<div class="updated">
								<p><?php _e( 'License Activation Successfully!', 'cf7-abandoned-form' ); ?></p>
							</div>
							<?php
							break;

					}
				}
			}
		}

		function zw_licence_extension_deactivation() {

			// retrieve the license from the database
			$license_status = trim( get_option( self::$licence_status ) );
			$license = trim( get_option( self::cf7af_licence_key ) );
			$license_email = trim( get_option( self::cf7af_licence_email ) );

			// data to send in our API request
			$api_params = array(
				'action' => 'deactivate_license',
				'key'    => $license_status,
				'email'  => $license_email,
				'id'     => urlencode( self::$item_id ), // the name of our product in uo
				'host'   => home_url()
			);

			// Call the custom API.
			$response = wp_remote_get( self::$valid_url, array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params
			) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) || (
					200 !== wp_remote_retrieve_response_code( $response )
					&& 400 !== wp_remote_retrieve_response_code( $response )
				) ) {

				if ( is_wp_error( $response ) ) {
					$message = $response->get_error_message();
				} else {
					$message = __( 'An error occurred, please try again.' );
				}

				$base_url = admin_url( 'edit.php?post_type='.CF7AF_POST_TYPE.'&page=' . self::$license_page );
				$redirect = add_query_arg( array( 'zw_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

				wp_redirect( $redirect );
				exit();
			}

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ), true );

			// $license_data->license will be either "deactivated" or "failed"
			if ( isset( $license_data['license'] ) ) {
				if ( $license_data['license'] == 'deactivated' || $license_data['license'] == 'failed' ) {
					delete_option( self::cf7af_licence_key );
					delete_option( self::cf7af_licence_email );
					delete_option( self::$licence_status );

					// Unschedules the events attached
					wp_clear_scheduled_hook( 'cf7af_send_notify_event' );
				}
			}
		}

		/*
		######## ##     ## ##    ##  ######  ######## ####  #######  ##    ##  ######
		##       ##     ## ###   ## ##    ##    ##     ##  ##     ## ###   ## ##    ##
		##       ##     ## ####  ## ##          ##     ##  ##     ## ####  ## ##
		######   ##     ## ## ## ## ##          ##     ##  ##     ## ## ## ##  ######
		##       ##     ## ##  #### ##          ##     ##  ##     ## ##  ####       ##
		##       ##     ## ##   ### ##    ##    ##     ##  ##     ## ##   ### ##    ##
		##        #######  ##    ##  ######     ##    ####  #######  ##    ##  ######
		*/

		public static function zw_license_page() {

			$license = get_option( self::cf7af_licence_key );
			$license_email = get_option( self::cf7af_licence_email );
			$status  = get_option( self::$licence_status );
			$error   = '';
			?>

			<div class="wrap">
				<h2 class=""><?php echo self::$activation_menuname; ?></h2>
				<form method="post" action="options.php">
					<?php settings_fields( 'cf7af_license' ); ?>

					<table class="form-table">
						<tbody>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php _e( 'Email Address' , 'cf7-abandoned-form' ); ?>
							</th>
							<td>
								<input
									id="cf7af_license_email"
									name="cf7af_license_email"
									type="email"
									class="regular-text"
									value="<?php esc_attr_e( $license_email ); ?>" <?php if ( !empty( $status ) ) { echo 'disabled'; } ?> required
								/>
								<label class="description" for="cf7af_license_email">
									<?php _e( 'Enter your email which used for purchase license', 'cf7-abandoned-form' ); ?>
								</label>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php _e( 'License Key' , 'cf7-abandoned-form' ); ?>
							</th>
							<td>
								<input
									id="cf7af_license_key"
									name="cf7af_license_key"
									type="text"
									class="regular-text"
									value="<?php esc_attr_e( $license ); ?>" <?php if ( !empty( $status )  ) { echo 'disabled'; }?> required
								/>
								<label class="description" for="cf7af_license_key">
									<?php _e( 'Enter your license key', 'cf7-abandoned-form' ); ?>
								</label>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php _e( 'Activate License' ); ?>
							</th>
							<td>
								<?php if ( !empty( $status ) ) { ?>
									<span style="color: #29c129; font-weight:bold; line-height: 27px;padding-right: 20px;"><?php _e( 'Your License is active.', 'cf7-abandoned-form' ); ?> </span>
									<?php wp_nonce_field( self::$licence_nonce, self::$licence_nonce ); ?>
									<input
										type="submit"
										class="button-secondary"
										name="<?php echo self::$zw_deactivation_action; ?>"
										value="<?php _e( 'Deactivate License', 'cf7-abandoned-form' ); ?>
											"/>
								<?php } else {
									wp_nonce_field( self::$licence_nonce, self::$licence_nonce ); ?>
									<input
										type="submit"
										class="button-secondary"
										name="<?php echo self::$activation_action; ?>"
										value="<?php _e( 'Activate License', 'cf7-abandoned-form' ); ?>"
										style="background: #29c129; border-color: #29c129!important; text-decoration: none; color: white; font-size: 17px; padding: 8px 0; width: 170px; line-height: 0;"
									/>
								<?php } ?>
							</td>
						</tr>
						</tbody>
					</table>
				</form>
			</div>
			<?php
		}

		function api__removed() {
			delete_option( self::cf7af_licence_key );
			delete_option( self::cf7af_licence_email );
			delete_option( self::$licence_status );

			return true;
		}
	}
}