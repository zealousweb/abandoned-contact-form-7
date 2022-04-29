<?php
/**
 * CF7AF Class
 *
 * Handles the plugin functionality.
 *
 * @package WordPress
 * @package Abandoned Contact Form 7
 * @since 1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CF7AF' ) ) {

	include_once( CF7AF_DIR . '/inc/lib/class.' . CF7AF_PREFIX . '.licence.php' );

	/**
	 * The main CF7AF class
	 */
	class CF7AF {

		private static $_instance = null;
		private static $private_data = null;
		public $cf7af_smtp_opt,$cf7af_mail_notify_opt;

		var $admin = null,
		    $front = null,
		    $lib   = null;

		public static function instance() {

			if ( is_null( self::$_instance ) )
				self::$_instance = new self();

			return self::$_instance;
		}

		function __construct() {
			self::$private_data = new CF7AF_Licence();
			$this->cf7af_smtp_opt = get_option( 'cf7af_smtp_option' );
			$this->cf7af_mail_notify_opt = get_option( 'cf7af_mail_notify_option' );

			# Register plugin activation hook
			register_activation_hook( CF7AF_FILE, array( $this, 'action__plugin_activation' ) );

			add_action( 'plugins_loaded', array( $this, 'action__plugins_loaded' ), 1 );
			add_action( 'wp_ajax_wpcf7forms_abandoned', array( $this, 'action__wpcf7forms_abandoned' ) );
			add_action( 'wp_ajax_nopriv_wpcf7forms_abandoned', array( $this, 'action__wpcf7forms_abandoned' ) );
			add_action( 'wp_ajax_remove_abandoned', array( $this, 'action__remove_abandoned' ) );
			add_action( 'wp_ajax_nopriv_remove_abandoned', array( $this, 'action__remove_abandoned' ) );
			add_filter( 'cron_schedules', array( $this, 'filter__cf7af_cron_schedules' ) );
			add_action( 'cf7af_send_notify_event',    array( $this, 'action__cf7af_send_notify_event' ), 999 );
			add_filter( 'plugin_action_links',array( $this,'action__cf7af_plugin_action_links'), 10, 2 );

			if( $this->cf7af_mail_notify_opt ) {
				if( isset( $this->cf7af_mail_notify_opt['cf7af_mailer_type'] ) && $this->cf7af_mail_notify_opt['cf7af_mailer_type'] == 'smtp' ) {
					add_action( 'phpmailer_init', array( $this, 'action__init_smtp_mailer' ), 9999 );
				}
			}
		}

		/**
		 * Filter: cron_schedules
		 *
		 * - Used to add custom cron schedules.
		 *
		 * @method filter__cf7af_cron_schedules
		 *
		 * @param  array $schedules
		 *
		 * @return array
		 */
		function filter__cf7af_cron_schedules( $schedules ) {

			if( !isset( $schedules["cf7af_hourly"] ) ) {
				$schedules['cf7af_hourly'] = array(
					'interval' =>  3600, //that's how many seconds in a hour, for the unix timestamp 3600
					'display' => __( 'Hourly', 'cf7-abandoned-form' )
				);
			}

			if( !isset( $schedules["cf7af_daily"] ) ) {
				$schedules['cf7af_daily'] = array(
					'interval' => 86400, //that's how many seconds in a week, for the unix timestamp 86400
					'display' => __( 'Daily', 'cf7-abandoned-form' )
				);
			}

			if( !isset( $schedules["cf7af_weekly"] ) ) {
				$schedules['cf7af_weekly'] = array(
					'interval' => 604800, //that's how many seconds in a week, for the unix timestamp 604800
					'display' => __( 'Weekly', 'cf7-abandoned-form' )
				);
			}

			if( !isset( $schedules["cf7af_monthly"] ) ) {
				$schedules['cf7af_monthly'] = array(
					'interval' => 259200, //that's how many seconds in a month (30 days - 24*60*60*30 ), for the unix timestamp
					'display' => __( 'Monthly', 'cf7-abandoned-form' )
				);
			}
			return $schedules;
		}

		/**
		 * Action: cf7af_send_notify_event
		 *
		 * - Set cron job for mail notification.
		 *
		 * @method action__cf7af_send_notify_event
		 *
		 */
		function action__cf7af_send_notify_event() {

			/* Get Abandoned Forms Data */
			$args = array(
				'post_type' => CF7AF_POST_TYPE,
				'post_status'   => 'publish',
				'posts_per_page' => -1,
			);

			$cf7af_data = get_posts( $args );
			if ( !empty( $cf7af_data ) ) {

				foreach ( $cf7af_data as $entry ) {

					$cf7af_email =  get_post_meta( $entry->ID, 'cf7af_email', true );
					$number_sentmail =  get_post_meta( $entry->ID, 'number_sentmail', true );
					$cf7af_mail_status =  get_post_meta( $entry->ID, 'cf7af_mail_status', true );
					$cf7af_form_id =  get_post_meta( $entry->ID, 'cf7af_form_id', true );
					$cf7af_page_url =  get_post_meta( $entry->ID, 'cf7af_page_url', true );

					if( ( $cf7af_mail_status=='no' || $cf7af_mail_status == 0 ) &&
						filter_var( $cf7af_email, FILTER_VALIDATE_EMAIL )
					) {
						// Send Mail
						$cf7af_mail_notify_opt = $this->cf7af_mail_notify_opt;

						if( !empty($cf7af_mail_notify_opt) ) {
							$cf7af_email_body = $this->cf7af_mail_notify_opt['cf7af_email_body'];
							$cf7af_nums_email = $this->cf7af_mail_notify_opt['cf7af_nums_email'];
							$cf7af_subject = $this->cf7af_mail_notify_opt['cf7af_subject'];

							if( $number_sentmail < $cf7af_nums_email ) {

								$to = $cf7af_email;
								$subject = $cf7af_subject;
								$body = stripslashes( nl2br( $cf7af_email_body ) );

								$form_title = get_the_title( $cf7af_form_id );
								$body = str_replace("{email}", $to, $body);
								$body = str_replace("{contact_form}", $form_title, $body);

								if( $cf7af_page_url != '' ) {
									if( strpos($cf7af_page_url, '/?') !== false ) {
										$body = str_replace("{link}", $cf7af_page_url.'&recover='.$entry->ID, $body);
									} else {
										$body = str_replace("{link}", $cf7af_page_url.'?recover='.$entry->ID, $body);
									}
								}
								else {
									$body = str_replace("{link}", '', $body);
								}

								$headers = array( 'Content-Type: text/html; charset=UTF-8' );
								$wp_sent = wp_mail( $to, $subject, $body, $headers );

								if( $wp_sent )  {
									$number_sentmail = $number_sentmail + 1;
									update_post_meta( $entry->ID, 'number_sentmail', $number_sentmail );
								} else {
									$number_fail_count =  get_post_meta(  $entry->ID, 'number_fail_count', true );
									$number_fail_count = $number_fail_count + 1;
									update_post_meta(  $entry->ID, 'number_fail_count', $number_fail_count );
								}
							}
						}
					}
				}
			}
		}

		/**
		 * Action: wp_ajax_remove_abandoned
		 *
		 * Remove post if submitted succesfully
		 *
		 */
		function action__remove_abandoned() {

			session_start();
			$cf7_id = isset( $_POST['cf7_id'] ) ? sanitize_text_field($_POST['cf7_id']) : '';

			if( $cf7_id  && isset( $_SESSION['wp_cf7form_id_'.$cf7_id.''] ) )  {
				$post_id = sanitize_text_field($_SESSION['wp_cf7form_id_'.$cf7_id.'']);
				wp_delete_post( $post_id, true );

				unset(  $_SESSION['wp_cf7form_id_'.$cf7_id.''] );
			}
		}

		/**
		 * Action: plugin_action_links
		 *
		 * Add License link after active links.
		 *
		 * @method action__cf7af_plugin_action_links
		 *
		 * @param  array  $links
		 * @param  path	  $file
		 *
		 * @return links
		 */
		function action__cf7af_plugin_action_links( $links, $file ) {
			if ( $file != CF7AF_PLUGIN_BASENAME ) {
				return $links;
			}
			if ( is_plugin_active( 'abandoned-forms-contact-form-7/abandoned-forms-contact-form-7.php' ) )
			{
				$licence_instance = self::$private_data;
				new CF7AF_Update( CF7AF_VERSION, CF7AF_PLUGIN_BASENAME, get_option( $licence_instance::cf7af_licence_email, '' ), get_option( $licence_instance::cf7af_licence_key, '' ) );

				$licence_link =  '<a href="'.admin_url('edit.php?post_type='.CF7AF_POST_TYPE.'&page=cf7af-license-activation', $scheme = 'admin' ).'">'.__( 'License', 'cf7-abandoned-form' ).'</a>';

				$support_link = '<a href="https://zealousweb.com/support/" target="_blank">' .__( 'Support', 'cf7-abandoned-form' ). '</a>';

				$document_link = '<a href="https://www.zealousweb.com/documentation/wordpress-plugins-documentation/abandoned-contact-form-7/" target="_blank">' .__( 'Document', 'cf7-abandoned-form' ). '</a>';

				array_unshift( $links, $licence_link );
				array_unshift( $links, $support_link );
				array_unshift( $links, $document_link );
			}
			return $links;
		}


		/**
		 * Action: phpmailer_init
		 *
		 * Set SMTP parameters if SMTP mailer.
		 *
		 * @method action__init_smtp_mailer
		 *
		 * @param  mailer object  $phpmailer
		 *
		 */
		function action__init_smtp_mailer(  $phpmailer ) {

			$phpmailer->IsSMTP();

			$from_email = $this->cf7af_smtp_opt['cf7af_from_email'];
			$from_name = $this->cf7af_smtp_opt['cf7af_from_name'];

			$phpmailer->From     = $from_email;
			$phpmailer->FromName = $from_name;
			$phpmailer->SetFrom( $phpmailer->From, $phpmailer->FromName );

			/* Set the SMTP Secure value */
			if ( 'none' !== $this->cf7af_smtp_opt['cf7af_smtp_ency_type'] ) {
				$phpmailer->SMTPSecure = $this->cf7af_smtp_opt['cf7af_smtp_ency_type'];
			}

			/* Set the other options */
			$phpmailer->Host = $this->cf7af_smtp_opt['cf7af_smtp_host'];
			$phpmailer->Port = $this->cf7af_smtp_opt['cf7af_smtp_port'];

			/* If we're using smtp auth, set the username & password */
			if ( 'yes' == $this->cf7af_smtp_opt['cf7af_smtp_auth'] ) {
				$phpmailer->SMTPAuth = true;
				$phpmailer->Username = $this->cf7af_smtp_opt['cf7af_smtp_username'];
				$phpmailer->Password = $this->cf7af_smtp_opt['cf7af_smtp_password'];
			}
			//PHPMailer 5.2.10 introduced this option. However, this might cause issues if the server is advertising TLS with an invalid certificate.
			$phpmailer->SMTPAutoTLS = false;

			//set reasonable timeout
			$phpmailer->Timeout = 10;
			$phpmailer->CharSet  = "utf-8";
		}

		/**
		 * Action: wp_ajax_wpcf7forms_abandoned
		 *
		 * Keep abandoned entry on every change of contact form 7.
		 *
		 * @method action__wpcf7forms_abandoned
		 *
		 */
		function action__wpcf7forms_abandoned() {

			session_start();

			$cf7af_forms =  isset( $_POST['forms'] ) ? sanitize_text_field($_POST['forms']) : '';
			$cf7af_page_url =  isset( $_POST['page_url'] ) ? esc_url_raw($_POST['page_url']) : '';
			$recover_id =  isset( $_POST['recover'] ) ? sanitize_text_field($_POST['recover']) : '';
			$cf7af_enable_abandoned = $cf7af_abandoned_email  = '';

			if( $cf7af_forms ) {
				$cf7af_form_data = array();

				foreach( $cf7af_forms as $cf7af_form ) {

					if( $cf7af_form['name'] == '_wpcf7' ) {
						$cf7af_form_id = $cf7af_form ['value'];

						$contact_form = WPCF7_ContactForm::get_instance($cf7af_form_id);
						$form_fields = $contact_form->scan_form_tags();
						foreach ( $form_fields as $form_field ) {
							if( $form_field->name != '' || $form_field->type != 'submit' ) {
								if($form_field->type != 'file') {
									$cf7af_form_data[ $form_field->name ] = '';
								}
							}
						}

						$cf7af_enable_abandoned = get_post_meta( $cf7af_form_id, 'cf7af_enable_abandoned' , true);
						$cf7af_abandoned_email = get_post_meta( $cf7af_form_id, 'cf7af_abandoned_email' , true);
					}

					if( $cf7af_form['name'] != '_wpcf7' && $cf7af_form['name'] != '_wpcf7_version' &&
						$cf7af_form['name'] != '_wpcf7_locale' && $cf7af_form['name'] != '_wpcf7_unit_tag' &&
						$cf7af_form['name'] != '_wpcf7_container_post'
					) {

						$contact_form = WPCF7_ContactForm::get_instance($cf7af_form_id);
						$form_fields = $contact_form->scan_form_tags();
						if( $form_fields ) {

							foreach ( $form_fields as $form_field ) {
								if( $form_field->name != '' || $form_field->type != 'submit' ) {
									if( $form_field->name != 'file' ) {

										$cf7af_form['name'] = str_replace( "[]", "" , $cf7af_form['name'] );
										if( $form_field->name == $cf7af_form['name'] ) {
											if( $cf7af_form_data[ $form_field->name ] != '' ) {
												$cf7af_form_data[ $form_field->name ] = $cf7af_form_data[ $form_field->name ] .", ".$cf7af_form['value'];
											} else {
												$cf7af_form_data[ $form_field->name ] = $cf7af_form['value'];
											}
										}
									}
								}
							}
						}
					}

					if( $cf7af_form ['name'] == $cf7af_abandoned_email ) {
						$abandoned_cf7_data_email = trim( $cf7af_form ['value'] );
					}

					//whether ip is from share internet
					if (!empty($_SERVER['HTTP_CLIENT_IP']))  {
						$ip_address = $_SERVER['HTTP_CLIENT_IP'];
					}
					//whether ip is from proxy
					elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))  {
						$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
					}
					//whether ip is from remote address
					else {
						$ip_address = $_SERVER['REMOTE_ADDR'];
					}
				}

				if( !isset($_SESSION['wp_cf7af_key']) ) {
					$_SESSION['wp_cf7af_key'] = time();
				} else {
					$_SESSION['wp_cf7af_key'];
				}

				if( ( $cf7af_enable_abandoned ) &&
					$cf7af_form_id
				) {

				$abandoned_post_id = isset( $_SESSION['wp_cf7form_id_'.$cf7af_form_id.''] ) ? sanitize_text_field($_SESSION['wp_cf7form_id_'.$cf7af_form_id.'']) : '';

				if( $recover_id ) {
					$_SESSION['wp_cf7form_id_'.$cf7af_form_id.''] = $recover_id;
				}

				if( $abandoned_post_id ) {
					$abandoned_post = get_post_status( $abandoned_post_id );
					if( $abandoned_post != 'publish' ) unset( $_SESSION['wp_cf7form_id_'.$cf7af_form_id.''] );
				}

					if( !isset( $_SESSION['wp_cf7form_id_'.$cf7af_form_id.'']) ) {

						$_SESSION['wp_cf7af_key'] = time();

						// Gather post data.
						$abandoned_post = array(
							'post_title'    => 'Abandoned Entry',
							'post_status'   => 'publish',
							'post_type'		=> CF7AF_POST_TYPE,
							'comment_status' => 'closed',
							'ping_status'    => 'closed',
						);

						// Insert the post into the database.
						$post_id = wp_insert_post( $abandoned_post );

						$update_abandoned_post = array(
							'ID'           => $post_id,
							'post_title'   => 'Abandoned Entry #'.$post_id,
							'post_status'  => 'publish',
							'post_type'	   => CF7AF_POST_TYPE
						);

						// Set Session Dyncmic key for particular form
						$_SESSION['wp_cf7form_id_'.$cf7af_form_id.''] = $post_id;

						// Update the post into the database
						wp_update_post( $update_abandoned_post );

						update_post_meta( $post_id, 'cf7af_form_id', $cf7af_form_id );
						update_post_meta( $post_id, 'cf7af_email', $abandoned_cf7_data_email );
						update_post_meta( $post_id, 'cf7af_ip_address', $ip_address );
						update_post_meta( $post_id, 'cf7af_form_data', $cf7af_form_data );
						update_post_meta( $post_id, 'number_sentmail', 0 );
						update_post_meta( $post_id, 'cf7af_mail_status', 0 );
						update_post_meta( $post_id, 'number_fail_count', 0 );
						update_post_meta( $post_id, 'cf7af_page_url', $cf7af_page_url );

					} else {
						$post_id = $_SESSION['wp_cf7form_id_'.$cf7af_form_id.''];

						if( filter_var( $abandoned_cf7_data_email, FILTER_VALIDATE_EMAIL) ) {

							update_post_meta( $post_id, 'cf7af_email', $abandoned_cf7_data_email );
						}
						update_post_meta( $post_id, 'cf7af_form_data', $cf7af_form_data );
					}
				}
			}
			exit;
		}

		/**
		 * Action: plugins_loaded
		 *
		 * - Plugin load function
		 *
		 * @method action__plugins_loaded
		 *
		 * @return [type] [description]
		*/

		function action__plugins_loaded() {

			# Load Paypal SDK on int action

			# Load plugin update file
			require_once ( CF7AF_DIR . '/inc/class.' . CF7AF_PREFIX . '.update.php' );

			$licence_instance = self::$private_data;
			new CF7AF_Update( CF7AF_VERSION, CF7AF_PLUGIN_BASENAME, get_option( $licence_instance::cf7af_licence_email, '' ), get_option( $licence_instance::cf7af_licence_key, '' ) );

			add_action( 'init', array( $this, 'action__init' ) );
			add_action( 'admin_init', array( $this, 'action__check_plugin_state' ) );

			if ( !empty( self::$private_data->instance() ) ) {

				# Action to load custom post type

				global $wp_version;

				# Set filter for plugin's languages directory
				$CF7AF_lang_dir = dirname( CF7AF_PLUGIN_BASENAME ) . '/languages/';
				$CF7AF_lang_dir = apply_filters( 'CF7AF_languages_directory', $CF7AF_lang_dir );

				# Traditional WordPress plugin locale filter.
				$get_locale = get_locale();

				if ( $wp_version >= 4.7 ) {
					$get_locale = get_user_locale();
				}

				# Traditional WordPress plugin locale filter
				$locale = apply_filters( 'plugin_locale',  $get_locale, 'cf7-abandoned-form' );
				$mofile = sprintf( '%1$s-%2$s.mo', 'cf7-abandoned-form' , $locale );

				# Setup paths to current locale file
				$mofile_global = WP_LANG_DIR . '/plugins/' . basename( CF7AF_DIR ) . '/' . $mofile;

				if ( file_exists( $mofile_global ) ) {
					# Look in global /wp-content/languages/plugin-name folder
					load_textdomain( 'cf7-abandoned-form', $mofile_global );
				} else {
					# Load the default language files
					load_plugin_textdomain( 'cf7-abandoned-form', false, $CF7AF_lang_dir );
				}
			}
		}

		/**
		 * Action: admin_init
		 *
		 * Check plugin state (activate or deactivate).
		 *
		 * @method action__check_plugin_state
		 *
		 */
		function action__check_plugin_state()
		{
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			if ( !is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) )
			{
				add_action( 'admin_notices', array( $this, 'action__notice_cf7af_deactive' ) );
				deactivate_plugins( CF7AF_PLUGIN_BASENAME );
				if ( isset( $_GET['activate'] ) ) {
					unset( $_GET['activate'] );
				}
			}
		}

		/**
		 * Action: init
		 *
		 * - If license found then action run
		 *
		 * @method action__init
		 *
		 */
		function action__init() {

			flush_rewrite_rules();

			/**
			 * Post Type: CF7 Abandoned Addon Pro.
			 */

			$labels = array(
				'name' => __( 'Abandoned Users', 'cf7-abandoned-form' ),
				'singular_name' => __( 'Abandoned User Detail', 'cf7-abandoned-form' ),
				'all_items' => __( 'All Abandoned Users', 'cf7-abandoned-form' ),
				'edit_item' => __( 'Edit Abandoned User', 'cf7-abandoned-form' ),
				'search_items' => __( 'Search Abandoned User', 'cf7-abandoned-form' ),
				'view_item' => __( 'View Abandoned User', 'cf7-abandoned-form' ),
				'not_found' => __( 'No Abandoned User found', 'cf7-abandoned-form' ),
				'not_found_in_trash' => __( 'No Abandoned User found in Trash', 'cf7-abandoned-form' ),
			);

			$args = array(
				'label' => __( 'Abandoned Users', 'cf7-abandoned-form' ),
				'labels' => $labels,
				'description' => '',
				'public' => false,
				'publicly_queryable' => false,
				'show_ui' => true,
				'delete_with_user' => false,
				'show_in_rest' => false,
				'rest_base' => '',
				'has_archive' => false,
				'show_in_nav_menus' => false,
				'menu_icon' => 'dashicons-pressthis',
				'exclude_from_search' => true,
				'capability_type' => 'post',
				'capabilities' => array(
					'read' => true,
					'create_posts'  => false,
					'publish_posts' => false,
				),
				'map_meta_cap' => true,
				'hierarchical' => false,
				'rewrite' => false,
				'query_var' => false,
				'supports' => array( 'title' ),
			);

			register_post_type( CF7AF_POST_TYPE , $args );

			$license_status = trim( get_option( CF7AF_META_PREFIX.'addon_license_status' ) );

			if( !$license_status &&
				( isset( $_GET['post_type'] ) && $_GET['post_type']== CF7AF_POST_TYPE ) &&
				( !isset( $_GET['page'] ) && $_GET['page']!='cf7af-license-activation' ) )
			{
				$base_url = admin_url( 'edit.php?post_type='.CF7AF_POST_TYPE.'&page=cf7af-license-activation' );
				$redirect = add_query_arg( array('zw_activation' => 'false', 'message' =>'' ), $base_url );

				wp_redirect( $redirect );
				exit;
			}

			# Post Type: Here you add your post type
		}


		/**
		 * Action: register_activation_hook
		 *
		 * When plugin is active.
		 *
		 * @method action__plugin_activation
		 *
		 */
		function action__plugin_activation() {

			if( empty( $this->cf7af_mail_notify_opt ) ) {
				$cf7af_mail_notify_option['cf7af_mailer_type'] = 'none';
				$cf7af_mail_notify_option['cf7af_notification_time'] = 'cf7af_hourly';
				$cf7af_mail_notify_option['cf7af_nums_email'] = '5';
				$cf7af_mail_notify_option['cf7af_subject'] = __( 'You are so close!', 'cf7-abandoned-form' );

				$str = __( 'Hello', 'cf7-abandoned-form' ). ' {email} <br>';
				$str .= __( 'Contact into:', 'cf7-abandoned-form' )  . ' {contact_form}<br><br>';
				$str .= __( 'We noticed you left something behind.', 'cf7-abandoned-form' ) . '<br>';
				$str .= __( 'No need to worry, you can still visit the page from where you left accidentally.', 'cf7-abandoned-form' ) . '<br><br>';
				$str .= __( 'Use the following link to make submissions.', 'cf7-abandoned-form' ) . '<br> ';
				$str .= '{link}<br><br>';
				$str .= __( 'Thanks!', 'cf7-abandoned-form' );

				$cf7af_mail_notify_option['cf7af_email_body'] = $str;
				update_option( 'cf7af_mail_notify_option', $cf7af_mail_notify_option );
			}
		}

		/**
		 *
		 * Action: admin_notices
		 *
		 * Admin notice of activate pugin.
		 */
		function action__notice_cf7af_deactive() {
		?>
			<div class="error">
				<p><?php _e( '<b>Abandoned Contact Form 7 :</b> Contact Form 7 is not active! Please install <a target="_blank" href="https://wordpress.org/plugins/contact-form-7/">Contact Form 7</a>.', 'cf7-abandoned-form' ); ?></p>
			</div>
		<?php
		}
	}
}

function CF7AF() {
	return CF7AF::instance();
}

CF7AF();