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

	/**
	 * The main CF7AF class
	 */
	class CF7AF {

		private static $_instance = null;
		public $cf7af_mail_notify_opt;

		var $admin = null,
		    $front = null;

		public static function instance() {

			if ( is_null( self::$_instance ) )
				self::$_instance = new self();

			return self::$_instance;
		}

		function __construct() {

			$this->cf7af_mail_notify_opt = get_option( 'cf7af_mail_notify_option' );

			# Register plugin activation hook
			register_activation_hook( CF7AF_FILE, array( $this, 'action__plugin_activation' ) );

			add_action( 'plugins_loaded', array( $this, 'action__plugins_loaded' ), 1 );
			add_action( 'wp_ajax_wpcf7forms_abandoned', array( $this, 'action__wpcf7forms_abandoned' ) );
			add_action( 'wp_ajax_nopriv_wpcf7forms_abandoned', array( $this, 'action__wpcf7forms_abandoned' ) );
			add_action( 'wp_ajax_remove_abandoned', array( $this, 'action__remove_abandoned' ) );
			add_action( 'wp_ajax_nopriv_remove_abandoned', array( $this, 'action__remove_abandoned' ) );

		}


		/**
		 * Action: wp_ajax_remove_abandoned
		 *
		 * Remove post if submitted succesfully
		 *
		 */
		function action__remove_abandoned() {

			check_ajax_referer( 'cf7af_remove_abandoned', 'cf7af_remove_nonce' );

			session_start();

			$cf7_id     = isset( $_POST['cf7_id'] ) ? absint( wp_unslash( $_POST['cf7_id'] ) ) : 0;
			$recover_id = isset( $_POST['recover_id'] ) ? absint( wp_unslash( $_POST['recover_id'] ) ) : 0;

			if ( ! empty( $recover_id ) ) {
				wp_delete_post( $recover_id, true );
			}

			if ( $cf7_id && isset( $_SESSION[ 'wp_cf7form_id_' . $cf7_id ] ) ) {
				$post_id = absint( $_SESSION[ 'wp_cf7form_id_' . $cf7_id ] );
				wp_delete_post( $post_id, true );

				unset( $_SESSION[ 'wp_cf7form_id_' . $cf7_id ] );
			}
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

			check_ajax_referer( 'cf7af_abandoned_track', 'cf7af_abandoned_nonce' );

			session_start();

			$cf7af_forms = array();
			if ( isset( $_POST['forms'] ) && is_array( $_POST['forms'] ) ) {
				$cf7af_forms = CF7AF_Helpers::sanitize_abandoned_forms(
					map_deep( wp_unslash( $_POST['forms'] ), 'sanitize_text_field' )
				);
			}
			$cf7af_page_url = isset( $_POST['page_url'] )
				? esc_url_raw( wp_unslash( $_POST['page_url'] ) )
				: '';
			$recover_id = isset( $_POST['recover'] )
				? absint( wp_unslash( $_POST['recover'] ) )
				: 0;
			$cf7af_enable_abandoned = $cf7af_abandoned_email  = '';
			$cf7af_abandoned_specific_field=array();
			$ip_address = CF7AF_Helpers::get_client_ip_address();

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
						$cf7af_abandoned_specific_field = get_post_meta( $cf7af_form_id, 'cf7af_abandoned_specific_field' , false);
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
				}

				if ( ! isset( $_SESSION['wp_cf7af_key'] ) ) {
					$_SESSION['wp_cf7af_key'] = time();
				}

				if( ( $cf7af_enable_abandoned ) &&
					$cf7af_form_id
				) {

				$abandoned_post_id = isset( $_SESSION[ 'wp_cf7form_id_' . $cf7af_form_id ] )
					? absint( $_SESSION[ 'wp_cf7form_id_' . $cf7af_form_id ] )
					: 0;
				if( $recover_id ) {
					$_SESSION[ 'wp_cf7form_id_' . $cf7af_form_id ] = $recover_id;
					CF7AF_Helpers::sync_abandoned_entry_post_fields( $recover_id, $cf7af_form_id );
				}

				if( $abandoned_post_id ) {
					$abandoned_post = get_post_status( $abandoned_post_id );
					if( $abandoned_post != 'publish' ) {
						unset( $_SESSION[ 'wp_cf7form_id_' . $cf7af_form_id ] );
					}
				}

					if( ! isset( $_SESSION[ 'wp_cf7form_id_' . $cf7af_form_id ] ) ) {
						$_SESSION['wp_cf7af_key'] = time();
						// Gather post data.
						$abandoned_post = array(
							'post_title'     => 'Abandoned Entry',
							'post_status'    => 'publish',
							'post_type'      => CF7AF_POST_TYPE,
							'post_parent'    => absint( $cf7af_form_id ),
							'post_excerpt'   => isset( $abandoned_cf7_data_email ) ? sanitize_text_field( $abandoned_cf7_data_email ) : '',
							'comment_status' => 'closed',
							'ping_status'    => 'closed',
						);

						// Insert the post into the database.
						$post_id = wp_insert_post( $abandoned_post );

						$update_abandoned_post = array(
							'ID'           => $post_id,
							'post_title'   => 'Abandoned Entry #'.$post_id,
							'post_status'  => 'publish',
							'post_type'    => CF7AF_POST_TYPE,
							'post_parent'  => absint( $cf7af_form_id ),
						);

						// Set Session Dyncmic key for particular form
						$_SESSION[ 'wp_cf7form_id_' . $cf7af_form_id ] = $post_id;
						// Update the post into the database
						wp_update_post( $update_abandoned_post );

						update_post_meta( $post_id, 'cf7af_form_id', $cf7af_form_id );
						update_post_meta( $post_id, 'cf7af_email', $abandoned_cf7_data_email );
						update_post_meta( $post_id, 'cf7af_ip_address', $ip_address );
						update_post_meta( $post_id, 'cf7af_form_data', $cf7af_form_data );
						update_post_meta( $post_id, 'number_sentmail', 0 );
						update_post_meta( $post_id, 'number_fail_count', 0 );
						update_post_meta( $post_id, 'cf7af_page_url', $cf7af_page_url );

					} else {
						$post_id = absint( $_SESSION[ 'wp_cf7form_id_' . $cf7af_form_id ] );
						if ( filter_var( $abandoned_cf7_data_email, FILTER_VALIDATE_EMAIL ) ) {
							update_post_meta( $post_id, 'cf7af_email', $abandoned_cf7_data_email );
						}
						update_post_meta( $post_id, 'cf7af_form_data', $cf7af_form_data );
						CF7AF_Helpers::sync_abandoned_entry_post_fields(
							$post_id,
							$cf7af_form_id,
							isset( $abandoned_cf7_data_email ) ? $abandoned_cf7_data_email : ''
						);
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

			add_action( 'init', array( $this, 'action__init' ) );
			add_action( 'admin_init', array( $this, 'action__check_plugin_state' ) );
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
				if ( current_user_can( 'activate_plugins' ) && filter_input( INPUT_GET, 'activate', FILTER_VALIDATE_BOOLEAN ) ) {
					unset( $_GET['activate'] );
				}
			}
		}

		/**
		 * Action: init
		 *
		 * - If Register post type
		 *
		 * @method action__init
		 *
		 */
		function action__init() {

			CF7AF_Helpers::maybe_sync_abandoned_post_data();

			flush_rewrite_rules();
			/**
			 * Post Type: CF7 Abandoned Addon Pro.
			 */

			$labels = array(
				'name' => __( 'Abandoned Users', 'abandoned-contact-form-7' ),
				'singular_name' => __( 'Abandoned User Detail', 'abandoned-contact-form-7' ),
				'all_items' => __( 'All Abandoned Users', 'abandoned-contact-form-7' ),
				'edit_item' => __( 'Edit Abandoned User', 'abandoned-contact-form-7' ),
				'search_items' => __( 'Search Abandoned User', 'abandoned-contact-form-7' ),
				'view_item' => __( 'View Abandoned User', 'abandoned-contact-form-7' ),
				'not_found' => __( 'No Abandoned User found', 'abandoned-contact-form-7' ),
				'not_found_in_trash' => __( 'No Abandoned User found in Trash', 'abandoned-contact-form-7' ),
			);

			$args = array(
				'label' => __( 'Abandoned Users', 'abandoned-contact-form-7' ),
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

				$cf7af_mail_notify_option['cf7af_subject'] = __( 'You are so close!', 'abandoned-contact-form-7' );

				$str = __( 'Hello', 'abandoned-contact-form-7' ). ' {email} <br>';
				$str .= __( 'Contact into:', 'abandoned-contact-form-7' )  . ' {contact_form}<br><br>';
				$str .= __( 'We noticed you left something behind.', 'abandoned-contact-form-7' ) . '<br>';
				$str .= __( 'No need to worry, you can still visit the page from where you left accidentally.', 'abandoned-contact-form-7' ) . '<br><br>';
				$str .= __( 'Use the following link to make submissions.', 'abandoned-contact-form-7' ) . '<br> ';
				$str .= '{link}<br><br>';
				$str .= __( 'Thanks!', 'abandoned-contact-form-7' );

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
				<p><?php echo wp_kses_post( __( '<b>Abandoned Contact Form 7 :</b> Contact Form 7 is not active! Please install <a target="_blank" href="https://wordpress.org/plugins/contact-form-7/">Contact Form 7</a>.', 'abandoned-contact-form-7' ) ); ?></p>
			</div>
		<?php
		}
	}
}

function CF7AF() {
	return CF7AF::instance();
}

CF7AF();