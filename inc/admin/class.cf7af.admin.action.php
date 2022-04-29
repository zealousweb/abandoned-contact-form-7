<?php
/**
 * CF7AF_Admin_Action Class
 *
 * Handles the admin functionality.
 *
 * @package WordPress
 * @package Abandoned Contact Form 7 Pro
 * @since 1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CF7AF_Admin_Action' ) ) {

	/**
	 *  The CF7AF_Admin_Action Class
	 */

	class CF7AF_Admin_Action {

		public $cf7af_smtp_opt,$cf7af_mail_notify_opt;

		function __construct()  {

			$this->cf7af_smtp_opt = get_option( 'cf7af_smtp_option' );
			$this->cf7af_mail_notify_opt = get_option( 'cf7af_mail_notify_option' );

			add_action( 'admin_init', 		array( $this, 'action__admin_init' ) );
			add_action( 'init',         	array( $this, 'action__init_99' ), 99 );
			add_action( 'add_meta_boxes', 	array( $this, 'action__add_meta_boxes' ) );
			add_action( 'manage_cf7af_data_posts_custom_column', array( $this, 'action__manage_cf7af_data_posts_custom_column' ), 10, 2 );
			// Save settings of contact form 7 admin
			add_action( 'wpcf7_save_contact_form', 		array( $this, 'action__wpcf7af_save_contact_form' ), 20, 2 );
			add_action( 'pre_get_posts',       		    array( $this, 'action__pre_get_posts' ) );
			add_action( 'admin_menu',    				array( $this, 'action__add_submenu' ) , 99 );
			add_action( 'restrict_manage_posts',		array( $this, 'action__cf7af_restrict_manage_posts' ) );
			add_action( 'parse_query',					array( $this, 'action__cf7af_parse_query' ) );
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

		/**
		 * Action: admin_init
		 *
		 * - Register admin min js and admin min css
		 *
		 */
		function action__admin_init() {

			wp_register_style( CF7AF_PREFIX . '_admin_css', CF7AF_URL . 'assets/css/admin.min.css', array(), CF7AF_VERSION );

			wp_register_script( CF7AF_PREFIX . '_admin_js', CF7AF_URL . 'assets/js/admin.min.js', array( 'jquery-core' ), CF7AF_VERSION );
		}

		/**
		 * Action: init 99
		 *
		 * - Used to perform the CSV export functionality.
		 *
		 */
		function action__init_99() {
			if (
				isset( $_REQUEST['export_csv_cf7af'] )
				&& isset( $_REQUEST['form-id'] )
				&& !empty( $_REQUEST['form-id'] )
			) {
				$form_id = sanitize_text_field($_REQUEST['form-id']);

				if ( 'all' == $form_id ) {
					add_action( 'admin_notices', array( $this, 'action__cf7af_admin_notices_export' ) );
					return;
				}

				$meta_query_args = array();
				if( $form_id && $form_id!='all' ) {
					$meta_query_args = array(
						'relation' => 'OR', // Optional, defaults to "AND"
						array(
							'key'     => 'cf7af_form_id',
							'value'   => $form_id,
							'compare' => '='
						)
					);
				}

				/* Get Abandoned Forms Data for Particular Form */
				$args = array(
					'post_type' => CF7AF_POST_TYPE,
					'posts_per_page' => -1,
					'meta_query' => $meta_query_args,

				);
				$exported_data = get_posts( $args );

				if ( empty( $exported_data ) ) {
					add_action( 'admin_notices', array( $this, 'action__cf7af_admin_notices_blank' ) );
					return;
				}

				/** CSV Export **/
				$filename = 'cf7af-' . $form_id . '-' . time() . '.csv';

				$text_cf7af_form_data = __( 'Form Data', 'cf7-abandoned-form' );
				$text_cf7af_email = __( 'User Email', 'cf7-abandoned-form' );
				$text_cf7af_ip_address = __( 'User IP', 'cf7-abandoned-form' );
				$text_cf7af_mail_status = __( 'is Enable Send Mail', 'cf7-abandoned-form' );
				$text_number_sentmail = __( 'Number of Send Mail', 'cf7-abandoned-form' );
				$text_date = __( 'Submitted Date', 'cf7-abandoned-form' );

				$header_row = array(
					'cf7af_form_data'		=> $text_cf7af_form_data,
					'cf7af_email'			=> $text_cf7af_email,
					'cf7af_ip_address'		=> $text_cf7af_ip_address,
					'cf7af_mail_status'		=> $text_cf7af_mail_status,
					'number_sentmail'		=> $text_number_sentmail,
					'date'					=> $text_date,
				);
				$other_rows = $data_rows = $cf7af_form_data_array = array();

				/* Get all field of contact form 7 */
				$contact_form = WPCF7_ContactForm::get_instance( $form_id );
				if( $contact_form ) {
					$form_fields = $contact_form->scan_form_tags();
					foreach ( $form_fields as $form_field ) {
						if( $form_field->name != '' || $form_field->type != 'submit' ) {
							if(  $form_field->type != 'file'  ) {
								$cf7af_form_data_array[ $form_field->name ] = $form_field->name ;
							}
						}
					}
					$header_row = array_merge( $cf7af_form_data_array, $header_row );
				}

				if ( !empty( $exported_data ) ) {
					foreach ( $exported_data as $entry ) {

						$row = array();

						foreach ( $header_row as $key => $val ) {

							if( 'cf7af_form_data' == $key ) {

									$cf7af_form_data = get_post_meta( $entry->ID, $key, true );

									if( $cf7af_form_data ) {
										foreach( $cf7af_form_data AS $key => $val ) {
											if( isset( $cf7af_form_data_array[ $key ] ) ){
												$row[ $key ] = $val;
											}
										}
									}

								} elseif( 'date' == $key ) {

									$row[$key] = __( get_the_date( 'd, M Y H:i:s', $entry->ID ) );

								} elseif( 'cf7af_mail_status' == $key ) {

									$cf7af_mail_status = get_post_meta( $entry->ID, $key, true );
									if( $cf7af_mail_status == 1 ) $row[$key] = __( 'Yes', 'cf7-abandoned-form' );
									if( $cf7af_mail_status == 0 ) $row[$key] = __( 'No', 'cf7-abandoned-form' );

								} else {

									$row[$key] = get_post_meta( $entry->ID, $key, true );
								}
						}
						$data_rows[] = $row;
					}
				}
				unset($header_row['cf7af_form_data']);

				$header_title = array();
				$header_title[] = __(
									(
									$form_id
									? get_the_title( $form_id )
									: get_post_meta( $form_id, 'cf7af_form_id' , true )
								)
							). ':';

				ob_start();

				$fh = @fopen( 'php://output', 'w' );
				fprintf( $fh, chr(0xEF) . chr(0xBB) . chr(0xBF) );
				header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
				header( 'Content-Description: File Transfer' );
				header( 'Content-type: text/csv' );
				header( "Content-Disposition: attachment; filename={$filename}" );
				header( 'Expires: 0' );
				header( 'Pragma: public' );
				fputcsv( $fh, $header_title );
				fputcsv( $fh, $header_row );
				foreach ( $data_rows as $data_row ) {
					fputcsv( $fh, $data_row );
				}
				fclose( $fh );

				ob_end_flush();
				die();
			}
		}

		/**
		 * Action: add_meta_boxes
		 *
		 * - Add mes boxes for the CPT "cf7af_data"
		 */
		function action__add_meta_boxes() {
			add_meta_box( 'cf7af-data', __( 'Abandoned Form Data', 'cf7-abandoned-form' ), array( $this, 'cf7af_show_from_data' ), CF7AF_POST_TYPE, 'normal', 'high' );
		}

		/**
		 * Save Abandoned Field Settings of Contact Form 7
		 */
		public function action__wpcf7af_save_contact_form( $WPCF7_form ) {

			$wpcf7 = WPCF7_ContactForm::get_current();

			if ( !empty( $wpcf7 ) ) {
				$post_id = $wpcf7->id();
			}

			$form_fields = array(
				CF7AF_META_PREFIX . 'enable_abandoned',
				CF7AF_META_PREFIX . 'abandoned_email',
			);

			if ( !empty( $form_fields ) ) {
				foreach ( $form_fields as $key ) {
					$keyval = ( $_REQUEST[ $key ] );
					update_post_meta( $post_id, $key, $keyval );
				}
			}
		}

		/**
		 * Action: manage_cf7af_data_posts_custom_column
		 *
		 * @method action__manage_cf7af_data_posts_custom_column
		 *
		 * @param  string  $column
		 * @param  int     $post_id
		 *
		 * @return string
		 */
		function action__manage_cf7af_data_posts_custom_column( $column, $post_id ) {
			switch ( $column ) {

				case 'cf7af_email' :
					echo (
						!empty( get_post_meta( $post_id , 'cf7af_email', true ) )
						? get_post_meta( $post_id , 'cf7af_email', true )
						: ''
					);
					break;

				case 'cf7af_ip_address' :
					echo (
						!empty( get_post_meta( $post_id , 'cf7af_ip_address', true ) )
						? get_post_meta( $post_id , 'cf7af_ip_address', true )
						: ''
					);
					break;

				case 'cf7af_send_mail' :
						$cf7af_mail_status = get_post_meta( $post_id , 'cf7af_mail_status', true );

						$cf7af_email = !empty( get_post_meta( $post_id , 'cf7af_email', true ) )
						? get_post_meta( $post_id , 'cf7af_email', true )
						: '';

						$abandoned_post_status = get_post_status( $post_id );
						if( $cf7af_email &&
							filter_var( $cf7af_email, FILTER_VALIDATE_EMAIL) &&
							$abandoned_post_status != 'trash'
						) {
							if( !$cf7af_mail_status ) {
								echo '<a href="' . esc_url( admin_url( 'edit.php?post_type='.CF7AF_POST_TYPE.'&page=cf7af-send-mail&abandoned_id='.$post_id.'"' ) ) . '" class="button button-primary"> '. __( 'Action', 'cf7-abandoned-form' ).' </a>';
							} else {
								$alert_msg = __( 'To enable this button please allow mail notification from detail page', 'cf7-abandoned-form' );
								echo '<a href="javascript:void(0);" onClick="alert( \' '. $alert_msg . ' \')" class="disable-action button button-primary" disabled> '. __( 'Disable', 'cf7-abandoned-form' ).' </a>';
							}
						} else {
							echo ' &nbsp; &nbsp; &nbsp; &nbsp;- ';
						}
					break;

				case 'number_sentmail' :
					echo (
						!empty( get_post_meta( $post_id, 'number_sentmail', true ) )
						? get_post_meta( $post_id, 'number_sentmail', true )
						: 0
					);
					break;

				case 'number_fail_count' :
					echo (
						!empty( get_post_meta( $post_id, 'number_fail_count', true ) )
						? get_post_meta( $post_id, 'number_fail_count', true )
						: 0
					);
					break;
			}
		}

		/**
		 * Action: pre_get_posts
		 *
		 * - Used to perform order by into CPT List.
		 *
		 * @method action__pre_get_posts
		 *
		 * @param  object $query WP_Query
		 */
		function action__pre_get_posts( $query ) {

			if (
				! is_admin()
				|| !in_array ( $query->get( 'post_type' ), array( CF7AF_POST_TYPE ) )
			)
				return;

			$orderby = $query->get( 'orderby' );

			if ( 'number_sentmail' == $orderby ) {
				$query->set( 'meta_key', 'number_sentmail' );
				$query->set( 'orderby', 'meta_value_num' );
			}
			if ( 'cf7af_email' == $orderby ) {
				$query->set( 'meta_key', 'cf7af_email' );
				$query->set( 'orderby', 'meta_value' );
			}
			if ( 'number_fail_count' == $orderby ) {
				$query->set( 'meta_key', 'number_fail_count' );
				$query->set( 'orderby', 'meta_value' );
			}
		}

		/**
		 * Action: restrict_manage_posts
		 *
		 * - Used to creat filter by form and export functionality.
		 *
		 * @method action__cf7af_restrict_manage_posts
		 *
		 * @param  string $post_type
		 */
		function action__cf7af_restrict_manage_posts( $post_type ) {

			if ( CF7AF_POST_TYPE != $post_type ) {
				return;
			}

			$posts = get_posts(
				array(
					'post_type'        => 'wpcf7_contact_form',
					'post_status'      => 'publish',
					'suppress_filters' => false,
					'posts_per_page'   => -1
				)
			);

			if ( empty( $posts ) ) {
				return;
			}

			$selected = ( isset( $_GET['form-id'] ) ? sanitize_text_field($_GET['form-id']) : '' );

			echo '<div class="alignleft actions">';
				echo '<select name="form-id" id="form-id">';
					echo '<option value="all">' . __( 'Select Forms', 'cf7-abandoned-form' ) . '</option>';
					foreach ( $posts as $post ) {
						echo '<option value="' . $post->ID . '" ' . selected( $selected, $post->ID, false ) . '>' . $post->post_title  . '</option>';
					}
				echo '</select>';

				echo '<input type="submit" id="doaction2" name="export_csv_cf7af" class="button action" value=" '.__('Export CSV', 'cf7-abandoned-form' ).'">';
			echo '</div>';
		}

		/**
		 * Action: parse_query
		 *
		 * - Filter data by form id.
		 *
		 * @method action__cf7af_parse_query
		 *
		 * @param  object $query WP_Query
		 */
		function action__cf7af_parse_query( $query ) {

			if ( !is_admin() || !in_array ( $query->get( 'post_type' ), array( CF7AF_POST_TYPE ) ) ){
				return;
			}

			if ( is_admin() && isset( $_GET['form-id'] ) && 'all' != $_GET['form-id'] ) {
				$query->query_vars['meta_value']   = sanitize_text_field($_GET['form-id']);
				$query->query_vars['meta_compare'] = '=';
			}
		}

		/**
		 * Action: admin_menu
		 *
		 * - Add Submenu Option
		 *
		 * @method action__add_submenu
		 *
		 */
		function action__add_submenu(){

			add_submenu_page(
				'edit.php?post_type='.CF7AF_POST_TYPE,
				__( 'Mail Notification Settings', 'cf7-abandoned-form' ),
				__( 'Mail Notification Settings', 'cf7-abandoned-form' ),
				'manage_options',
				'cf7af-setting',
				array( $this, 'cf7af_setting_callback' )
			);

			add_submenu_page(
				'edit.php?post_type='.CF7AF_POST_TYPE,
				__( 'SMTP Settings', 'cf7-abandoned-form' ),
				__( 'SMTP Settings', 'cf7-abandoned-form' ),
				'manage_options',
				'cf7af-stmp-setting',
				array( $this, 'cf7af_smtp_setting_callback' )
			);

			add_submenu_page(
				'',
				__( 'Send Mail', 'cf7-abandoned-form' ),
				__( 'Send Mail', 'cf7-abandoned-form' ),
				'manage_options',
				'cf7af-send-mail',
				array( $this, 'cf7af_send_mail_callback' )
			);
		}

		/**
		 * Action: admin_notices
		 *
		 * - Added use notice when trying to export without selecting the form.
		 *
		 * @method action__cf7af_admin_notices_export
		 */
		function action__cf7af_admin_notices_export() {
			echo '<div id="setting-error-settings_updated" class="notice notice-error settings-error is-dismissible"> ' .
				'<p>' .
					__( 'Please select form to export.', 'cf7-abandoned-form' ) .
				'</p>' .
			'</div>';
		}

		/**
		 * Action: admin_notices
		 *
		 * - Added use notice when form data not found.
		 *
		 * @method action__cf7af_admin_notices_blank
		 */
		function action__cf7af_admin_notices_blank() {
			echo '<div id="setting-error-settings_updated" class="notice notice-error settings-error is-dismissible"> ' .
				'<p>' .
					__( 'No Abandoned data Found', 'cf7-abandoned-form' ) .
				'</p>' .
			'</div>';
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

		/**
		 * - Used to display the form data in CPT detail page.
		 *
		 * @method cf7af_show_from_data
		 *
		 * @param  object $post WP_Post
		 */
		function cf7af_show_from_data( $post ) {

			$cf7af_form_id = get_post_meta( $post->ID, 'cf7af_form_id', true );
			$cf7af_email = get_post_meta( $post->ID, 'cf7af_email', true );
			$cf7af_ip_address = get_post_meta( $post->ID, 'cf7af_ip_address', true );
			$cf7af_form_data = get_post_meta( $post->ID, 'cf7af_form_data', true );
			$cf7af_mail_status = get_post_meta( $post->ID, 'cf7af_mail_status', true );

			echo '<table class="cf7pap-box-data form-table">' .
				'<style>.inside-field td, .inside-field th{ padding-top: 5px; padding-bottom: 5px;}</style>';

			echo '<tr class="form-field">' .
				'<th scope="row">' .
					'<label for="hcf_author">' . __( 'CF7 Form Name', 'cf7-abandoned-form' ) . '</label>' .
				'</th>' .
				'<td>'.get_the_title( $cf7af_form_id ).'</td>' .
			'</tr>';

			echo '<tr class="form-field">' .
				'<th scope="row">' .
					'<label for="hcf_author">' . __( 'User Email', 'cf7-abandoned-form' ) . '</label>' .
				'</th>' .
				'<td>'.$cf7af_email.'</td>' .
			'</tr>';

			echo '<tr class="form-field">' .
				'<th scope="row">' .
					'<label for="hcf_author">' . __( 'User IP', 'cf7-abandoned-form' ) . '</label>' .
				'</th>' .
				'<td>'.$cf7af_ip_address.'</td>' .
			'</tr>';

			echo '<tr class="form-field">' .
				'<th scope="row">' .
					'<label for="hcf_author">' . __( 'Other Form Detail', 'cf7-abandoned-form' ) . '</label>' .
				'</th>' .
				'<td>';

				if( $cf7af_form_data ) {
					echo '<table><tbody>';
						foreach( $cf7af_form_data AS $key => $val ) {
							echo'<tr class="inside-field"><th scope="row"> '.$key.' :</th><td> '.$val.' </td></tr>';
						}
					echo '</tbody></table>';
				}
				echo '</td>' .
			'</tr>';

			echo '<tr class="form-field">' .
				'<th scope="row">' .
					'<label for="cf7af_mail_status">'. esc_html__('Disable Mail Notification', 'cf7-abandoned-form' ) .'</label>' .
				'</th>' .
				'<td>' .
					'<label for="cf7af_mail_status_1"><input id="cf7af_mail_status_1" class="cf7af_mail_status" type="radio" name="cf7af_mail_status" value="1" '. checked( 1,$cf7af_mail_status, false ) .' /> ' . __( 'Yes', 'cf7-abandoned-form' ) . ' </label>'.
					'<label for="cf7af_mail_status_0"><input id="cf7af_mail_status_0" class="cf7af_mail_status" type="radio" name="cf7af_mail_status" value="0" '. checked( 0,$cf7af_mail_status, false ) .'  /> ' . __( 'No', 'cf7-abandoned-form' ) . ' </label>'.
				'</td>' .
			'</tr>';

			if( filter_var( $cf7af_email, FILTER_VALIDATE_EMAIL ) ) {
				echo '<tr class="form-field">' .
					'<th scope="row">' .
						'<label for="cf7af_mail_status">'. esc_html__('Send Mail', 'cf7-abandoned-form' ) .'</label>' .
					'</th>' .
					'<td>' .
						'<a href="' . esc_url( admin_url( 'edit.php?post_type='.CF7AF_POST_TYPE.'&page=cf7af-send-mail&abandoned_id='.$post->ID.'"' ) ) . '" class="button button-primary"> '. __( 'Action', 'cf7-abandoned-form' ).' </a> '.
					'</td>' .
				'</tr>';
			}

			echo '</table>';
		}

		/**
		 * - Used to manage email notification settings.
		 */
		public function cf7af_setting_callback() {

			require_once( CF7AF_DIR . '/inc/admin/template/' . CF7AF_PREFIX . '.notification.settings.template.php' );

		}

		/**
		 * - Used to configure SMTP settings.
		 */
		public function cf7af_smtp_setting_callback() {

			require_once( CF7AF_DIR . '/inc/admin/template/' . CF7AF_PREFIX . '.smtp.settings.template.php' );

		}

		/**
		 * - Used to send mail to particular user.
		 */
		public function cf7af_send_mail_callback() {

			require_once( CF7AF_DIR . '/inc/admin/template/' . CF7AF_PREFIX . '.send.mail.template.php' );

		}
	}
}