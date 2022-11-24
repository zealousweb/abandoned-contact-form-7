<?php
/**
 * CF7AF_Front_Action Class
 *
 * Handles the Frontend Actions.
 *
 * @package WordPress
 * @subpackage Abandoned Contact Form 7
 * @since 1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CF7AF_Front_Action' ) ){

	/**
	 *  The CF7AF_Front_Action Class
	 */
	class CF7AF_Front_Action {

		function __construct()  {

			add_action( 'wp_enqueue_scripts', array( $this, 'action__wp_enqueue_scripts' ) );
			add_action( 'wp_footer',	array( $this, 'action__fill_contact_form' ) );
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
		 * Action: wp_enqueue_scripts
		 *
		 * - enqueue script in front side
		 *
		 * @ method action__wp_enqueue_scripts
		 *
		 */
		function action__wp_enqueue_scripts() {

			wp_enqueue_script( CF7AF_PREFIX . '_front_js', CF7AF_URL . 'assets/js/front.min.js', array( 'jquery' ), CF7AF_VERSION.'.1.0', true );

			if( isset($_GET['recover']) ) {
				if( $_GET['recover']!='' ) {
					$recover = sanitize_text_field($_GET['recover']);
				} else {
					$recover = '';
				}
			} else {
				$recover = '';
			}
			$vars = array(
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'recover'  => $recover,
			);
			wp_localize_script( CF7AF_PREFIX . '_front_js', 'wpcf7forms_abandoned', $vars );
		}

		/**
		 * Action: wp_footer
		 *
		 * - To fill autopopulate data
		 *
		 @ method action__fill_contact_form
		 *
		 */
		function action__fill_contact_form() {

			if( !isset($_GET['recover']) )
				return;

			$recover_id = $_GET['recover'];
			$post_info = get_post( $recover_id  );

			if( empty($post_info) )
				return;

			$form_id = get_post_meta( $recover_id, 'cf7af_form_id', true );
			$contact_form = WPCF7_ContactForm::get_instance( $form_id );
			$form_fields = $contact_form->scan_form_tags();

			$cf7af_form_data =  get_post_meta( $recover_id, 'cf7af_form_data', true );

			foreach ( $form_fields as $form_field ) {

				if(  $form_field->name!= '' && isset( $cf7af_form_data[$form_field->name] ) && $form_field->basetype!= 'file' ) {

					if( $form_field->basetype == 'textarea' ) {
						echo '<script type="text/javascript"> ';
						echo 'var textarea = document.querySelector("textarea[name='.$form_field->name.']");';
						echo ' textarea.value = "'.$cf7af_form_data[$form_field->name].'"';
						echo '</script>';
					} elseif( $form_field->basetype == 'radio' ) {
						echo '<script type="text/javascript">';
						echo 'jQuery("input[name='.$form_field->name.'][value='.$cf7af_form_data[$form_field->name].']").attr("checked","checked");';
						echo '</script>';
					} elseif( $form_field->basetype == 'select' && in_array( 'multiple', $form_field->options ) ) {
						echo '<script type="text/javascript">';
						$form_field_value = $cf7af_form_data[$form_field->name];
						if( $form_field_value!= '' ) {
							$form_field_value = explode( ",", $form_field_value);
							foreach(  $form_field_value AS $field_value ) {
								echo 'jQuery("select[name=\''.$form_field->name.'[]\'] option[value='.trim($field_value).']").attr("selected","selected");';
							}
						}
						echo '</script>';
					} elseif( $form_field->basetype == 'select' ) {
						echo '<script type="text/javascript">';
						echo 'jQuery("select[name='.$form_field->name.'] option[value='.$cf7af_form_data[$form_field->name].']").attr("selected","selected");';
						echo '</script>';
					} elseif( $form_field->basetype == 'checkbox' ) {
						$form_field_value = $cf7af_form_data[$form_field->name];
						if( $form_field_value!= '' ) {
							$form_field_value = explode( ",", $form_field_value);
							$loop = 0;
							foreach( $form_field_value AS $field_value ) {
								$form_field_value[$loop] = trim( $field_value );
								$loop++;
							}
							echo '<script type="text/javascript">';
							echo 'var chk_arr = document.getElementsByName("'.$form_field->name.'[]");';
							echo 'var chklength = chk_arr.length;';
							echo 'var form_field_value = '.json_encode( $form_field_value ).';';
							echo 'for(k=0;k<chklength;k++)
								{
									if ( form_field_value.includes( chk_arr[k].value ) ) {
										chk_arr[k].checked = true;
									}
								}';
							echo '</script>';
						}
					} else {
						if( $form_field->name && $cf7af_form_data[$form_field->name]!='') {
							echo '<script type="text/javascript">';
							echo 'jQuery("input[name='.$form_field->name.']").val("'.$cf7af_form_data[$form_field->name].'");';
							echo '</script>';
						}
					}
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

	}
	
	add_action( 'plugins_loaded', function() {
		CF7AF()->front->action = new CF7AF_Front_Action;
	} );

}