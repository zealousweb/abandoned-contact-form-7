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
			add_filter( 'posts_clauses',                array( $this, 'filter__abandoned_orderby_clauses' ), 10, 2 );
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
			wp_enqueue_style( CF7AF_PREFIX  . '_admin_css' );

			wp_register_script( CF7AF_PREFIX . '_admin_js', CF7AF_URL . 'assets/js/admin.min.js', array( 'jquery-core' ), CF7AF_VERSION, true );
		}

		/**
		 * Action: init 99
		 *
		 * - Used to perform the CSV export functionality.
		 *
		 */
		function action__init_99() {
			if ( ! isset( $_REQUEST['export_csv_cf7af'] ) ) {
				return;
			}

			if ( ! current_user_can( 'edit_posts' ) ) {
				return;
			}

			if (
				! isset( $_REQUEST['cf7af_export_nonce'] )
				|| ! wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_REQUEST['cf7af_export_nonce'] ) ),
					'cf7af_export_csv'
				)
			) {
				return;
			}

			if (
				! isset( $_REQUEST['form-id'] )
				|| '' === $_REQUEST['form-id']
			) {
				return;
			}

			$form_id = sanitize_text_field( wp_unslash( $_REQUEST['form-id'] ) );

			if ( 'all' == $form_id ) {
					add_action( 'admin_notices', array( $this, 'action__cf7af_admin_notices_export' ) );
					return;
				}

				/* Get Abandoned Forms Data for Particular Form */
				$args = array(
					'post_type'      => CF7AF_POST_TYPE,
					'posts_per_page' => 10,
					'order'          => 'ASC',
					'post_parent'    => absint( $form_id ),
					'post_status'    => array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' ),
				);
				$exported_data = get_posts( $args );

				if ( empty( $exported_data ) ) {
					add_action( 'admin_notices', array( $this, 'action__cf7af_admin_notices_blank' ) );
					return;
				}

				/** CSV Export **/
				$filename = 'cf7af-' . $form_id . '-' . time() . '.csv';

				$text_cf7af_form_data = __( 'Form Data', 'abandoned-contact-form-7' );
				$text_cf7af_email = __( 'User Email', 'abandoned-contact-form-7' );
				$text_cf7af_ip_address = __( 'User IP', 'abandoned-contact-form-7' );
				$text_number_sentmail = __( 'Number of Send Mail', 'abandoned-contact-form-7' );
				$text_date = __( 'Submitted Date', 'abandoned-contact-form-7' );

				$header_row = array(
					'cf7af_form_data'		=> $text_cf7af_form_data,
					'cf7af_email'			=> $text_cf7af_email,
					'cf7af_ip_address'		=> $text_cf7af_ip_address,
					'number_sentmail'		=> $text_number_sentmail,
					'date'					=> $text_date,
				);
				$data_rows = $cf7af_form_data_array = array();

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

									$row[$key] = get_the_date( 'd, M Y H:i:s', $entry->ID );

								} else {

									$row[$key] = get_post_meta( $entry->ID, $key, true );
								}
						}
						$data_rows[] = $row;
					}
				}
				unset($header_row['cf7af_form_data']);

				$header_title   = array();
				$form_title     = $form_id
					? get_the_title( $form_id )
					: get_post_meta( $form_id, 'cf7af_form_id', true );
				$header_title[] = sprintf(
					/* translators: %s: contact form title */
					__( '%s: You are using free version of plugin so only 10 entires exported.', 'abandoned-contact-form-7' ),
					$form_title
				);

				$csv_content = chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF );
				$csv_content .= $this->format_csv_row( $header_title );
				$csv_content .= $this->format_csv_row( array_values( $header_row ) );
				foreach ( $data_rows as $data_row ) {
					$csv_content .= $this->format_csv_row( array_values( $data_row ) );
				}

				if ( ! function_exists( 'WP_Filesystem' ) ) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
				}

				if ( ! WP_Filesystem() ) {
					wp_die( esc_html__( 'Could not initialize filesystem.', 'abandoned-contact-form-7' ) );
				}

				global $wp_filesystem;

				$temp_file = wp_tempnam( $filename );
				if ( ! $temp_file || ! $wp_filesystem->put_contents( $temp_file, $csv_content ) ) {
					wp_die( esc_html__( 'Could not write export file.', 'abandoned-contact-form-7' ) );
				}

				ob_start();
				header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
				header( 'Content-Description: File Transfer' );
				header( 'Content-type: text/csv' );
				header( 'Content-Disposition: attachment; filename=' . sanitize_file_name( $filename ) );
				header( 'Expires: 0' );
				header( 'Pragma: public' );
				ob_end_clean();

				$export_body = $wp_filesystem->get_contents( $temp_file );
				$wp_filesystem->delete( $temp_file, false, 'f' );

				if ( false === $export_body ) {
					wp_die( esc_html__( 'Could not read export file.', 'abandoned-contact-form-7' ) );
				}

				$this->output_csv_download( $export_body );
				die();
		}

		/**
		 * Action: add_meta_boxes
		 *
		 * - Add mes boxes for the CPT "cf7af_data"
		 */
		function action__add_meta_boxes() {
			add_meta_box( 'cf7af-data', __( 'Abandoned Form Data', 'abandoned-contact-form-7' ), array( $this, 'cf7af_show_from_data' ), CF7AF_POST_TYPE, 'normal', 'high' );
		}

		/**
		 * Save Abandoned Field Settings of Contact Form 7
		 */
		public function action__wpcf7af_save_contact_form( $WPCF7_form ) {

			$wpcf7 = WPCF7_ContactForm::get_current();

			if ( empty( $wpcf7 ) ) {
				return;
			}

			$post_id = $wpcf7->id();
			if ( empty( $post_id ) ) {
				return;
			}

			if (
				! isset( $_POST['_wpnonce'] )
				|| ! wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ),
					'wpcf7-save-contact-form_' . $post_id
				)
			) {
				return;
			}

			$form_fields = array(
				CF7AF_META_PREFIX . 'enable_abandoned',
				CF7AF_META_PREFIX . 'abandoned_email',
			);

			if ( ! empty( $form_fields ) ) {
				foreach ( $form_fields as $key ) {
					if ( CF7AF_META_PREFIX . 'enable_abandoned' === $key ) {
						// Unchecked checkboxes are omitted from the request; clear meta when off.
						$keyval = isset( $_POST[ $key ] ) ? '1' : '';
						update_post_meta( $post_id, $key, $keyval );
						continue;
					}
					if ( ! isset( $_POST[ $key ] ) ) {
						continue;
					}
					$keyval = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
					update_post_meta( $post_id, $key, $keyval );
				}
			}

			/*Start Multiple Field Added */

			$abandoned_specific_field_key = CF7AF_META_PREFIX . 'abandoned_specific_field';
			$old_cf7af_abandoned_specific_field = get_post_meta( $post_id, $abandoned_specific_field_key );
			$new_cf7af_abandoned_specific_field = array();
			if ( isset( $_POST[ $abandoned_specific_field_key ] ) && is_array( $_POST[ $abandoned_specific_field_key ] ) ) {
				$new_cf7af_abandoned_specific_field = array_map(
					'sanitize_text_field',
					wp_unslash( $_POST[ $abandoned_specific_field_key ] )
				);
			}

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
							echo '<a href="' . esc_url( CF7AF_Helpers::get_send_mail_admin_url( $post_id ) ) . '" class="button button-primary"> '. esc_html__( 'Action', 'abandoned-contact-form-7' ).' </a>';

						} else {
							echo '<a href="javascript::void(0);" class="disabled button-primary"> '. esc_html__( 'Invalid Email', 'abandoned-contact-form-7' ).' </a>';
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
		 * Filter: posts_clauses
		 *
		 * Order abandoned list columns without meta_key query vars.
		 *
		 * @param array    $clauses Query clauses.
		 * @param WP_Query $query   Current query.
		 * @return array
		 */
		function filter__abandoned_orderby_clauses( $clauses, $query ) {
			if (
				! is_admin()
				|| CF7AF_POST_TYPE !== $query->get( 'post_type' )
			) {
				return $clauses;
			}

			$orderby = $query->get( 'orderby' );
			$numeric = array( 'number_sentmail', 'number_fail_count' );

			if ( ! in_array( $orderby, array_merge( $numeric, array( 'cf7af_email' ) ), true ) ) {
				return $clauses;
			}

			global $wpdb;

			$order = 'DESC' === strtoupper( $query->get( 'order' ) ) ? 'DESC' : 'ASC';

			$clauses['join'] .= $wpdb->prepare(
				" LEFT JOIN {$wpdb->postmeta} AS cf7af_orderby_meta ON ({$wpdb->posts}.ID = cf7af_orderby_meta.post_id AND cf7af_orderby_meta.meta_key = %s) ",
				$orderby
			);

			if ( in_array( $orderby, $numeric, true ) ) {
				$clauses['orderby'] = "CAST(cf7af_orderby_meta.meta_value AS UNSIGNED) {$order}";
			} else {
				$clauses['orderby'] = "cf7af_orderby_meta.meta_value {$order}";
			}

			return $clauses;
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

			if ( ! current_user_can( 'edit_posts' ) ) {
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

			$selected = CF7AF_Helpers::get_list_filter_form_id();

			echo '<div class="alignleft actions">';
				wp_nonce_field( 'cf7af_filter_posts', 'cf7af_filter_nonce' );
				wp_nonce_field( 'cf7af_export_csv', 'cf7af_export_nonce' );
				echo '<select name="form-id" id="form-id">';
					echo '<option value="all">' . esc_html__( 'Select Forms', 'abandoned-contact-form-7' ) . '</option>';
					foreach ( $posts as $post ) {
						echo '<option value="' . esc_attr( $post->ID ) . '" ' . selected( $selected, $post->ID, false ) . '>' . esc_html( $post->post_title )  . '</option>';

					}
				echo '</select>';

				echo '<button type="submit" name="export_csv_cf7af" class="button action"> '.esc_html__('Export CSV', 'abandoned-contact-form-7' ).'</button>';
			    echo '<a class="cf7af-primary-btn" href="https://support.zealousweb.com/portal/en/home" target="_blank" rel="noopener noreferrer">'
				. esc_html__( 'Open Support Ticket', 'abandoned-contact-form-7' ) .
				'</a>';
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

			if ( ! current_user_can( 'edit_posts' ) ) {
				return;
			}

			$form_id = CF7AF_Helpers::get_list_filter_form_id();
			if ( '' === $form_id ) {
				return;
			}

			if ( 'all' === $form_id ) {
				return;
			}

			$query->set( 'post_parent', absint( $form_id ) );
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
				__( 'Mail Notification Settings', 'abandoned-contact-form-7' ),
				__( 'Mail Notification Settings', 'abandoned-contact-form-7' ),
				'manage_options',
				'cf7af-setting',
				array( $this, 'cf7af_setting_callback' )
			);

			add_submenu_page(
				'',
				__( 'Send Mail', 'abandoned-contact-form-7' ),
				__( 'Send Mail', 'abandoned-contact-form-7' ),
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
					esc_html__( 'Please select form to export.', 'abandoned-contact-form-7' ) .
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
					esc_html__( 'No Abandoned data Found', 'abandoned-contact-form-7' ) .
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
			wp_reset_postdata();
			$cf7af_entry_index = array_search( $post->ID, $total_arr, true );
			$cf7af_form_id = CF7AF_Helpers::get_abandoned_entry_form_id( $post->ID );
			$cf7af_email = get_post_meta( $post->ID, 'cf7af_email', true );
			$cf7af_ip_address = get_post_meta( $post->ID, 'cf7af_ip_address', true );
			$cf7af_form_data = get_post_meta( $post->ID, 'cf7af_form_data', true );
			if ( ! is_array( $cf7af_form_data ) ) {
				$cf7af_form_data = array();
			}
			$cf7af_track_field_names = get_post_meta( $cf7af_form_id, CF7AF_META_PREFIX . 'abandoned_specific_field', false );
			if ( ! is_array( $cf7af_track_field_names ) ) {
				$cf7af_track_field_names = array();
			}
			$cf7af_email_field_name = get_post_meta( $cf7af_form_id, CF7AF_META_PREFIX . 'abandoned_email', true );

			echo '<table class="cf7pap-box-data form-table">' .
				'<style>.inside-field td, .inside-field th{ padding-top: 5px; padding-bottom: 5px;}</style>';

			echo '<tr class="form-field">' .
				'<th scope="row">' .
					'<label for="hcf_author">' . esc_html__( 'CF7 Form Name', 'abandoned-contact-form-7' ) . '</label>' .
				'</th>' .
			    '<td>' . esc_html( get_the_title( $cf7af_form_id ) ) . '</td>';
			'</tr>';

			echo '<tr class="form-field">' .
				'<th scope="row">' .
					'<label for="hcf_author">' . esc_html__( 'User Email', 'abandoned-contact-form-7' ) . '</label>' .
				'</th>' .
				'<td>' . esc_html( $cf7af_email ) . '</td>';
			'</tr>';

			echo '<tr class="form-field">' .
				'<th scope="row">' .
					'<label for="hcf_author">' . esc_html__( 'User IP', 'abandoned-contact-form-7' ) . '</label>' .
				'</th>' .
				'<td>'.esc_html($cf7af_ip_address).'</td>' .
			'</tr>';
			echo '<tr class="form-field">' .
				'<th scope="row">' .
					'<label for="hcf_author">' . esc_html__( 'Extra Form Field Detail', 'abandoned-contact-form-7' ) . '</label>' .
				'</th>' .
				'<td>';

				if ( ! empty( $cf7af_form_data ) ) {
					if ( false !== $cf7af_entry_index && $cf7af_entry_index >= 10 ) {
						echo '<table><tbody>';
						echo '<tr class="inside-field"><th scope="row">' . esc_html__( 'You are using Free Abandoned Contact Form 7 - no license needed. Enjoy! 🙂', 'abandoned-contact-form-7' ) . '</th></tr>';
						echo '<tr class="inside-field"><th scope="row"><a href="https://store.zealousweb.com/abandoned-contact-form-7-pro" target="_blank">' . esc_html__( 'To unlock more features consider upgrading to PRO.', 'abandoned-contact-form-7' ) . '</a></th></tr>';
						echo '</tbody></table>';
					} else {
						$cf7af_has_rows = false;
						echo '<table><tbody>';
						foreach ( $cf7af_form_data as $cf7af_field_name => $cf7af_field_value ) {
							if ( '' === $cf7af_field_value && '0' !== (string) $cf7af_field_value ) {
								continue;
							}
							if ( $cf7af_email_field_name && $cf7af_field_name === $cf7af_email_field_name ) {
								continue;
							}
							if ( ! empty( $cf7af_track_field_names ) && ! in_array( $cf7af_field_name, $cf7af_track_field_names, true ) ) {
								continue;
							}
							$cf7af_has_rows = true;
							echo '<tr class="inside-field"><th scope="row">' . esc_html( $cf7af_field_name ) . '</th><td>' . esc_html( $cf7af_field_value ) . '</td></tr>';
						}
						echo '</tbody></table>';
						if ( ! $cf7af_has_rows ) {
							echo '<p class="description">' . esc_html__( 'No tracked field data for this entry. Configure “Fields to Track” on the contact form’s Abandoned settings tab.', 'abandoned-contact-form-7' ) . '</p>';
						}
					}
				} else {
					echo '<p class="description">' . esc_html__( 'No form field data was captured for this entry.', 'abandoned-contact-form-7' ) . '</p>';
				}
				echo '</td>' .
			'</tr>';

			if( filter_var( $cf7af_email, FILTER_VALIDATE_EMAIL ) ) {
				echo '<tr class="form-field">' .
					'<th scope="row">' .
						'<label for="cf7af_mail_status">'. esc_html__('Send Mail', 'abandoned-contact-form-7' ) .'</label>' .
					'</th>' .
					'<td>' .
						'<a href="' . esc_url( CF7AF_Helpers::get_send_mail_admin_url( $post->ID ) ) . '" class="button button-primary"> '. esc_html__( 'Action', 'abandoned-contact-form-7' ).' </a> '.
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

		/**
		 * Output a CSV file download body.
		 *
		 * HTML escaping would corrupt the attachment; only invalid UTF-8 is stripped.
		 *
		 * @param string $export_body CSV file contents.
		 */
		private function output_csv_download( $export_body ) {
			if ( ! is_string( $export_body ) || '' === $export_body ) {
				return;
			}

			$export_body = wp_check_invalid_utf8( $export_body, true );

			echo wp_kses( $export_body, array() );
		}

		/**
		 * Format a row for CSV export.
		 *
		 * @param array $fields Row values.
		 * @return string
		 */
		private function format_csv_row( $fields ) {
			$escaped = array();

			foreach ( $fields as $field ) {
				$field = (string) $field;
				if ( preg_match( '/[",\r\n]/', $field ) ) {
					$field = '"' . str_replace( '"', '""', $field ) . '"';
				}
				$escaped[] = $field;
			}

			return implode( ',', $escaped ) . "\r\n";
		}
		
	}
	
	add_action( 'plugins_loaded', function() {
		CF7AF()->admin->action = new CF7AF_Admin_Action;
	} );
}
