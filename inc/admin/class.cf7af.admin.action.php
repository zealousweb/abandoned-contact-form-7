<?php
/**
 * CF7AF_Admin_Action Class
 *
 * Handles the admin functionality.
 *
 * @package WordPress
 * @package Abandoned Contact Form 7
 * @since 1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CF7AF_Admin_Action' ) ) {

	/**
	 *  The CF7AF_Admin_Action Class
	 */

	class CF7AF_Admin_Action {

		public $cf7af_mail_notify_opt;

		function __construct()  {

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
			if (isset( $_REQUEST['export_csv_cf7af'] )
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
					'posts_per_page' => 10,
					'order' => 'ASC',
					'meta_query' => $meta_query_args,
					'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash'),

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
				$text_number_sentmail = __( 'Number of Send Mail', 'cf7-abandoned-form' );
				$text_date = __( 'Submitted Date', 'cf7-abandoned-form' );

				$header_row = array(
					'cf7af_form_data'		=> $text_cf7af_form_data,
					'cf7af_email'			=> $text_cf7af_email,
					'cf7af_ip_address'		=> $text_cf7af_ip_address,
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
							). ': You are using free version of plugin so only 10 entires exported.';

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

			if (!empty($form_fields)) {
				foreach ($form_fields as $key) {
						$keyval = sanitize_text_field($_REQUEST[$key]);
						update_post_meta($post_id, $key, $keyval);
					
				}
			}

			/*Start Multiple Field Added */

			$old_cf7af_abandoned_specific_field = get_post_meta($post_id, CF7AF_META_PREFIX . 'abandoned_specific_field');
			$new_cf7af_abandoned_specific_field = isset ($_REQUEST[ CF7AF_META_PREFIX .'abandoned_specific_field'] )  ? $_REQUEST[ CF7AF_META_PREFIX .'abandoned_specific_field'] : array();

			if ( empty ($new_cf7af_abandoned_specific_field) ) {
			   delete_post_meta($post_id, CF7AF_META_PREFIX . 'abandoned_specific_field');
			} else {
			  $already_cf7af_abandoned_specific_field = array();
			  if ( ! empty($old_cf7af_abandoned_specific_field) ) {
			    foreach ($old_cf7af_abandoned_specific_field as $value) {
			      if ( ! in_array($value, $new_cf7af_abandoned_specific_field) ) {
			        delete_post_meta($post_id, CF7AF_META_PREFIX . 'abandoned_specific_field', $value);
			      } else {
			        $already_cf7af_abandoned_specific_field[] = $value;
			      }
			    }
			  }
			  $to_save_cf7af_abandoned_specific_field = array_diff($new_cf7af_abandoned_specific_field, $already_cf7af_abandoned_specific_field);
			  if ( ! empty($to_save_cf7af_abandoned_specific_field) ) {
			    foreach ( $to_save_cf7af_abandoned_specific_field as $to_save_cf7af_abandoned_specific_field_data ) {
			       add_post_meta( $post_id, CF7AF_META_PREFIX . 'abandoned_specific_field', $to_save_cf7af_abandoned_specific_field_data);
			    }
			  }
			}
			
			/*End Multiple Field Added*/
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
					echo ! empty( get_post_meta( $post_id , 'cf7af_email', true ) )
						? esc_html( get_post_meta( $post_id , 'cf7af_email', true ) )
						: '';
					break;

				case 'cf7af_ip_address' :
					echo !empty( get_post_meta( $post_id , 'cf7af_ip_address', true ) )
						? esc_html(get_post_meta( $post_id , 'cf7af_ip_address', true ))
						: '';					
					break;

				case 'cf7af_send_mail' :

						$cf7af_email = !empty( get_post_meta( $post_id , 'cf7af_email', true ) )
						? get_post_meta( $post_id , 'cf7af_email', true )
						: '';

						$abandoned_post_status = get_post_status( $post_id );
						if( $cf7af_email &&
							filter_var( $cf7af_email, FILTER_VALIDATE_EMAIL) &&
							$abandoned_post_status != 'trash'
						) {
							echo '<a href="' . esc_url( admin_url( 'edit.php?post_type='.CF7AF_POST_TYPE.'&page=cf7af-send-mail&abandoned_id='.$post_id.'"' ) ) . '" class="button button-primary"> '. esc_html__( 'Action', 'cf7-abandoned-form' ).' </a>';

						} else {
							echo '<a href="javascript::void(0);" class="disabled button-primary"> '. esc_html__( 'Invalid Email', 'cf7-abandoned-form' ).' </a>';
						}
					break;

				case 'number_sentmail' :
					echo (
						!empty( get_post_meta( $post_id, 'number_sentmail', true ) )
						? esc_html(get_post_meta( $post_id, 'number_sentmail', true ))
						: 0
					);
					break;

				case 'number_fail_count' :
					echo (
						!empty( get_post_meta( $post_id, 'number_fail_count', true ) )
						? esc_html(get_post_meta( $post_id, 'number_fail_count', true ))
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
					echo '<option value="all">' . esc_html__( 'Select Forms', 'cf7-abandoned-form' ) . '</option>';
					foreach ( $posts as $post ) {
						echo '<option value="' . esc_attr( $post->ID ) . '" ' . selected( $selected, $post->ID, false ) . '>' . esc_html( $post->post_title )  . '</option>';

					}
				echo '</select>';

				echo '<button type="submit" name="export_csv_cf7af" class="button action"> '.esc_html__('Export CSV', 'cf7-abandoned-form' ).'</button>';
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

			if ( is_admin() && isset( $_GET['form-id'] ) && 'all' != isset($_GET['form-id']) ) {
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
					esc_html__( 'Please select form to export.', 'cf7-abandoned-form' ) .
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
					esc_html__( 'No Abandoned data Found', 'cf7-abandoned-form' ) .
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
			$post_type = $post->post_type;
			/*$count_pages = wp_count_posts($post_type);*/
			$args = array(
              'post_type' => $post_type,
              'order' => 'ASC',
			  'posts_per_page' => -1,
			  'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash'),
              );
			$my_query = new WP_Query($args);
			$total_arr = array();
			while ( $my_query->have_posts() ) {
        		$my_query->the_post();
        		array_push($total_arr, $my_query->post->ID);
        	}
        	update_option('cf7af_total', $total_arr);
			$key = array_search ($post->ID, $total_arr);
			$cf7af_form_id = get_post_meta( $post->ID, 'cf7af_form_id', true );
			$cf7af_email = get_post_meta( $post->ID, 'cf7af_email', true );
			$cf7af_specific_field = get_post_meta($post->ID, 'cf7af_abandoned_specific_field', false );
			$cf7af_specific_field_implode_data=implode('</br>',$cf7af_specific_field);
			$cf7af_ip_address = get_post_meta( $post->ID, 'cf7af_ip_address', true );
			$cf7af_form_data = get_post_meta( $post->ID, 'cf7af_form_data', true );

			echo '<table class="cf7pap-box-data form-table">' .
				'<style>.inside-field td, .inside-field th{ padding-top: 5px; padding-bottom: 5px;}</style>';

			echo '<tr class="form-field">' .
				'<th scope="row">' .
					'<label for="hcf_author">' . esc_html__( 'CF7 Form Name', 'cf7-abandoned-form' ) . '</label>' .
				'</th>' .
			    '<td>' . esc_html( get_the_title( $cf7af_form_id ) ) . '</td>';
			'</tr>';

			echo '<tr class="form-field">' .
				'<th scope="row">' .
					'<label for="hcf_author">' . esc_html__( 'User Email', 'cf7-abandoned-form' ) . '</label>' .
				'</th>' .
				'<td>' . esc_html( $cf7af_email ) . '</td>';
			'</tr>';

			echo '<tr class="form-field">' .
				'<th scope="row">' .
					'<label for="hcf_author">' . esc_html__( 'User IP', 'cf7-abandoned-form' ) . '</label>' .
				'</th>' .
				'<td>'.esc_html($cf7af_ip_address).'</td>' .
			'</tr>';
			echo '<tr class="form-field">' .
				'<th scope="row">' .
					'<label for="hcf_author">' . esc_html__( 'Extra Form Field Detail', 'cf7-abandoned-form' ) . '</label>' .
				'</th>' .
				'<td>';

				if( $cf7af_form_data ) {
					if($key >= 10)
					{
						echo '<table><tbody>';
							echo'<tr class="inside-field"><th scope="row">You are using Free Abandoned Contact Form 7 - no license needed. Enjoy! 🙂</th></tr>';
							echo'<tr class="inside-field"><th scope="row"><a href="https://store.zealousweb.com/abandoned-contact-form-7-pro" target="_blank">To unlock more features consider upgrading to PRO.</a></th></tr>';
						echo '</tbody></table>';
					}
					else
					{
						echo '<table><tbody>';
							foreach( $cf7af_form_data AS $key => $val ) {
								if (in_array($val, $cf7af_specific_field)){
									echo '<tr class="inside-field"><th scope="row"> ' . esc_html( $key ) . ' :</th><td> ' . esc_html( $val ) . ' </td></tr>';
								}
							}
						echo '</tbody></table>';
					}
					
				}
				echo '</td>' .
			'</tr>';

			if( filter_var( $cf7af_email, FILTER_VALIDATE_EMAIL ) ) {
				echo '<tr class="form-field">' .
					'<th scope="row">' .
						'<label for="cf7af_mail_status">'. esc_html__('Send Mail', 'cf7-abandoned-form' ) .'</label>' .
					'</th>' .
					'<td>' .
						'<a href="' . esc_url( admin_url( 'edit.php?post_type='.CF7AF_POST_TYPE.'&page=cf7af-send-mail&abandoned_id='.$post->ID.'"' ) ) . '" class="button button-primary"> '. esc_html__( 'Action', 'cf7-abandoned-form' ).' </a> '.
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
		 * - Used to send mail to particular user.
		 */
		public function cf7af_send_mail_callback() {

			require_once( CF7AF_DIR . '/inc/admin/template/' . CF7AF_PREFIX . '.send.mail.template.php' );

		}
		
	}
	
	add_action( 'plugins_loaded', function() {
		CF7AF()->admin->action = new CF7AF_Admin_Action;
	} );
}