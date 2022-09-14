<?php
if ( !class_exists( 'CF7AF_Subscription' ) ) {

	class CF7AF_Subscription {

		static $status = null;

		private static $_instance = null;
		static $activation_menuname = 'Subscription Activation',
		    $subscription_status = 'cf7af_addon_subscription_status',
		    $activation_redirect = 'cf7af_addon_activation_redirect',
		    $subscription_nonce = 'cf7af_addon_nonce' ,
		    $activation_action = 'cf7af_addon_subscription_activate' ,
		    $zw_deactivation_action = 'cf7af_addon_subscription_deactivate',
		    $valid_url = 'https://www.zealousweb.com/magentostore/webapi/v1/license/verify',
		    $item_name = 'Abandoned Contact Form 7' ,
		    $subscription_page = 'cf7af-subscription-activation',
		    $item_id = CF7AF_PLUGIN_SKU; // WooCommerce product ID 13667

		const cf7af_subscription_key = 'cf7af_addon_subscription_key',
		      cf7af_subscription_email = 'cf7af_addon_subscription_email',
		  	  cf7af_subscription_error_msg = 'cf7af_error_msg',
		      cf7af_Subscription_due_date = 'cf7af_Subscription_due_date';

		public static function instance() {
			return self::$status;
		}

		function __construct() {

			self::$status = get_option( self::$subscription_status );

			register_activation_hook( CF7AF_FILE, array( $this, 'zw_subscription_extension' ) );
			add_action( 'setup_theme',   array( $this, 'action__setup_theme' ) );
			add_action( 'rest_api_init', array( $this, 'action__rest_api_init' ) );
			add_action( 'admin_init',    array( $this, 'zw_subscription_check_activation' ) );
			add_action( 'admin_init',    array( $this, 'zw_subscription_activate_subscription' ) );
			add_action( 'admin_init',    array( $this, 'zw_subscription_deactivate_subscription' ) );
			add_action( 'admin_menu',    array( $this, 'zw_subscription_menu' ),999 );
			add_action( 'admin_notices', array( $this, 'zw_subscription_admin_notices') );
			register_deactivation_hook( CF7AF_FILE, array( $this, 'zw_subscription_extension_deactivation' ) );
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
			//if ( !empty( self::$status ) ) {

				if ( is_admin() ) {
					CF7AF()->admin = new CF7AF_Admin;
					CF7AF()->admin->action = new CF7AF_Admin_Action;
					CF7AF()->admin->filter = new CF7AF_Admin_Filter;
				} else {
					CF7AF()->front = new CF7AF_Front;
					CF7AF()->front->action = new CF7AF_Front_Action;
					CF7AF()->front->filter = new CF7AF_Front_Filter;
				}
			//}
		}

		function action__rest_api_init() {
			register_rest_route(
				'subscriptions',
				'/removed',
				array(
					'callback' =>  array( $this, 'api__removed' ),
				)
			);
		}

		function zw_subscription_extension() {
			update_option( self::$activation_redirect, 'yes' );
			flush_rewrite_rules();
		}

		function zw_subscription_check_activation() {

			if ( class_exists('WPCF7') ) { // Based on dependencies

				if ( 'yes' === get_option( self::$activation_redirect, 'no' ) ) {

					update_option( self::$activation_redirect, 'no' );

					if ( ! isset( $_GET['activate-multi'] ) ) {
						wp_redirect( admin_url( 'edit.php?post_type='.CF7AF_POST_TYPE.'&page=' . self::$subscription_page ) );
					}
				}
			}
		}

		function zw_subscription_menu() {

			add_submenu_page(
				'edit.php?post_type='.CF7AF_POST_TYPE, // page name
				self::$activation_menuname,
				self::$activation_menuname,
				'manage_options',
				self::$subscription_page,
				array( __CLASS__, 'zw_subscription_page' )
			);
		}

		public static function zw_subscription_activate_subscription() {

			// listen for our activate button to be clicked
			if ( isset( $_POST[ self::$activation_action ] ) ) {

				// run a quick security check
				if ( ! check_admin_referer( self::$subscription_nonce, self::$subscription_nonce ) ) {
					return;
				} // get out if we didn't click the Activate button

				// retrieve the subscription from the database
				$subscription = trim( get_option( self::cf7af_subscription_key ) );
				$subscription_email = trim( get_option( self::cf7af_subscription_email ) );
				$subscription_due_date = trim(get_option(self::cf7af_Subscription_due_date));

				// Save subscription key
				$subscription = $_POST['cf7af_subscription_key'];
				$subscription_email = $_POST['cf7af_subscription_email'];

				$subscription_data = array();

				// data to send in our API request
				$api_params = array(
					'api_key'	=> $subscription,
					'sku'		=> self::$item_id
				);

				// Call the custom API.
				$response = wp_remote_post( self::$valid_url, array(
					'timeout'	=> 15,
					'sslverify'	=> false,
					'headers' 	=> array('X-Requested-With' => 'XMLHttpRequest'),
					'body'		=> $api_params
				) );

				$message = '';

				// make sure the response came back okay
				if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
					if ( is_wp_error( $response ) ) {
						$message = $response->get_error_message();
					} else {
						$message = __( 'Invalid Subscription Key.', 'accept-sagepay-payments-using-contact-form-7' );
					}
				} else {
					//response decode
					$subscription_data = unserialize( $response['body'] );
					if($subscription_data->success == 1 && $subscription_data->error == 'valid'){
						$subscription_status = 1;
					} else {
						//Subscription Key expired msg
						$message = sprintf(__( 'Your Subscription key expired.', 'accept-sagepay-payments-using-contact-form-7' ));
						$subscription_status = '';
					}
				}

				// Check if anything passed on a message constituting a failure
				if ( ! empty( $message ) ) {
					$base_url = admin_url( 'edit.php?post_type='.CF7AF_POST_TYPE.'&page=' . self::$subscription_page );
					$redirect = add_query_arg( array('zw_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

					wp_redirect( $redirect );
					exit();
				}

				update_option( self::cf7af_subscription_key, $subscription );
				update_option( self::cf7af_subscription_email, $subscription_email );
				update_option( self::cf7af_Subscription_due_date, date('F j, Y', strtotime($subscription_data->exp_date)));
				update_option( self::$subscription_status, $subscription_status );

				$base_url = admin_url( 'edit.php?post_type='.CF7AF_POST_TYPE.'&page=' . self::$subscription_page );
				$redirect = add_query_arg( array('zw_activation' => 'true', 'message' => urlencode( 'success' ) ), $base_url );
				wp_redirect( $redirect );
				exit();
			}
		}

		public static function zw_subscription_deactivate_subscription() {

			// listen for our activate button to be clicked
			if ( isset( $_POST[ self::$zw_deactivation_action ] ) ) {

				// run a quick security check
				if ( ! check_admin_referer( self::$subscription_nonce, self::$subscription_nonce ) ) {
					return;
				} // get out if we didn't click the Activate button

				// retrieve the subscription from the database
				$subscription_status = trim( get_option( self::$subscription_status ) );
				$subscription = trim( get_option( self::cf7af_subscription_key ) );
				$subscription_email = trim( get_option( self::cf7af_subscription_email ) );
				$subscription_due_date = trim(get_option(self::cf7af_Subscription_due_date));

				// data to send in our API request
				/* $api_params = array(
					'action' => 'deactivate_subscription',
					'key'    => $subscription_status,
					'email'  => $subscription_email,
					'id'     => urlencode( self::$item_id ), // the name of our product in uo
					'host'   => home_url()
				);

				// Call the custom API.
				$response = wp_remote_get( self::$valid_url, array(
					'timeout'   => 15,
					'sslverify' => false,
					'body'      => $api_params
				) ); */

				// make sure the response came back okay
				/* if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

					if ( is_wp_error( $response ) ) {
						$message = $response->get_error_message();
					} else {
						$message = __( 'An error occurred, please try again.' , 'cf7-abandoned-form' );
					}

					$base_url = admin_url( 'edit.php?post_type='.CF7AF_POST_TYPE.'&page=' . self::$subscription_page );
					$redirect = add_query_arg( array( 'zw_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

					wp_redirect( $redirect );
					exit();
				} */

				// decode the subscription data
				/* $subscription_data = json_decode( wp_remote_retrieve_body( $response ), true );

				// $subscription_data->subscription will be either "deactivated" or "failed"
				if ( $subscription_data['subscription'] == 'deactivated' || $subscription_data['subscription'] == 'failed' ) { */
					delete_option( self::cf7af_subscription_key );
					delete_option( self::cf7af_subscription_email );
					delete_option( self::cf7af_Subscription_due_date);
					delete_option( self::$subscription_status );

					// Unschedules the events attached
					/* wp_clear_scheduled_hook( 'cf7af_send_notify_event' );
				} */

				$base_url = admin_url( 'edit.php?post_type='.CF7AF_POST_TYPE.'&page=' . self::$subscription_page );
				$redirect = add_query_arg( array('zw_activation' => 'false', 'message' => urlencode( 'Successfully Deactivated!' ) ), $base_url );
				wp_redirect( $redirect );
				exit();
			}
		}

		function zw_subscription_admin_notices() {
			if (
				isset( $_GET[ 'page' ] )
				&& self::$subscription_page == $_GET['page']
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
								<p><?php _e( 'Subscription Activation Successfully!', 'cf7-abandoned-form' ); ?></p>
							</div>
							<?php
							break;

					}
				}
			}
		}

		function zw_subscription_extension_deactivation() {

			// retrieve the subscription from the database
			$subscription_status = trim( get_option( self::$subscription_status ) );
			$subscription = trim( get_option( self::cf7af_subscription_key ) );
			$subscription_email = trim( get_option( self::cf7af_subscription_email ) );

			// data to send in our API request
			/* $api_params = array(
				'action' => 'deactivate_subscription',
				'key'    => $subscription_status,
				'email'  => $subscription_email,
				'id'     => urlencode( self::$item_id ), // the name of our product in uo
				'host'   => home_url()
			);

			// Call the custom API.
			$response = wp_remote_get( self::$valid_url, array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params
			) ); */

			// make sure the response came back okay
			/* if ( is_wp_error( $response ) || (
					200 !== wp_remote_retrieve_response_code( $response )
					&& 400 !== wp_remote_retrieve_response_code( $response )
				) ) {

				if ( is_wp_error( $response ) ) {
					$message = $response->get_error_message();
				} else {
					$message = __( 'An error occurred, please try again.' );
				}

				$base_url = admin_url( 'edit.php?post_type='.CF7AF_POST_TYPE.'&page=' . self::$subscription_page );
				$redirect = add_query_arg( array( 'zw_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

				wp_redirect( $redirect );
				exit();
			} */

			// decode the subscription data
			/* $subscription_data = json_decode( wp_remote_retrieve_body( $response ), true );

			// $subscription_data->subscription will be either "deactivated" or "failed"
			if ( isset( $subscription_data['subscription'] ) ) {
				if ( $subscription_data['subscription'] == 'deactivated' || $subscription_data['subscription'] == 'failed' ) { */
					delete_option( self::cf7af_subscription_key );
					delete_option( self::cf7af_subscription_email );
					delete_option( self::cf7af_Subscription_due_date);
					delete_option( self::$subscription_status );

					// Unschedules the events attached
					/* wp_clear_scheduled_hook( 'cf7af_send_notify_event' );
				}
			} */
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

		public static function zw_subscription_page() {

			$subscription = get_option( self::cf7af_subscription_key );
			$subscription_email = get_option( self::cf7af_subscription_email );
			$subscription_due_date = get_option( self::cf7af_Subscription_due_date );
			$status  = get_option( self::$subscription_status );
			$error   = '';
			?>

			<div class="wrap">
				<h2 class=""><?php echo self::$activation_menuname; ?></h2>
				<form method="post" action="options.php">
					<?php settings_fields( 'cf7af_subscription' ); ?>

					<table class="form-table">
						<tbody>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php _e( 'Email Address' , 'cf7-abandoned-form' ); ?>
							</th>
							<td>
								<input
									id="cf7af_subscription_email"
									name="cf7af_subscription_email"
									type="email"
									class="regular-text"
									value="<?php esc_attr_e( $subscription_email ); ?>" <?php if ( !empty( $status ) ) { echo 'disabled'; } ?> required
								/>
								<label class="description" for="cf7af_subscription_email">
									<?php _e( 'Enter your email which used for purchase subscription', 'cf7-abandoned-form' ); ?>
								</label>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php _e( 'Subscription Key' , 'cf7-abandoned-form' ); ?>
							</th>
							<td>
								<input
									id="cf7af_subscription_key"
									name="cf7af_subscription_key"
									type="text"
									class="regular-text"
									value="<?php esc_attr_e( $subscription ); ?>" <?php if ( !empty( $status )  ) { echo 'disabled'; }?> required
								/>
								<label class="description" for="cf7af_subscription_key">
									<?php _e( 'Enter your Subscription key', 'cf7-abandoned-form' ); ?>
								</label>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php _e('Subscription Due Date'); ?>
							</th>
							<td>
								<div><strong><?php esc_attr_e($subscription_due_date); ?></strong></div>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php _e( 'Activate Subscription' ); ?>
							</th>
							<td>
								<?php if ( !empty( $status ) ) { ?>
									<span style="color: #29c129; font-weight:bold; line-height: 27px;padding-right: 20px;"><?php _e( 'Your Subscription is active.', 'cf7-abandoned-form' ); ?> </span>
									<?php wp_nonce_field( self::$subscription_nonce, self::$subscription_nonce ); ?>
									<input
										type="submit"
										class="button-secondary"
										name="<?php echo self::$zw_deactivation_action; ?>"
										value="<?php _e( 'Deactivate Subscription', 'cf7-abandoned-form' ); ?>
											"/>
								<?php } else {
									wp_nonce_field( self::$subscription_nonce, self::$subscription_nonce ); ?>
									<input
										type="submit"
										class="button-secondary"
										name="<?php echo self::$activation_action; ?>"
										value="<?php _e( 'Activate Subscription', 'cf7-abandoned-form' ); ?>"
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
			delete_option( self::cf7af_subscription_key );
			delete_option( self::cf7af_subscription_email );
			delete_option( self::$subscription_status );

			return true;
		}
	}
}
