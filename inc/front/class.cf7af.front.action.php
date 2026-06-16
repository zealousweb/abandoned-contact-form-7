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

			wp_enqueue_script( CF7AF_FRONT_SCRIPT_HANDLE, CF7AF_URL . 'assets/js/front.min.js', array( 'jquery' ), CF7AF_VERSION . '.1.0', true );

			$cf7af_recover_id = CF7AF_Helpers::get_recover_id_from_request();

			$cf7af_vars = array(
				'ajaxurl'             => admin_url( 'admin-ajax.php' ),
				'cf7af_recover'       => $cf7af_recover_id ? (string) $cf7af_recover_id : '',
				'cf7af_recover_token' => $cf7af_recover_id ? CF7AF_Helpers::get_recover_token_from_request() : '',
				'cf7af_nonce'         => wp_create_nonce( 'cf7af_abandoned_track' ),
				'cf7af_remove_nonce'  => wp_create_nonce( 'cf7af_remove_abandoned' ),
				'cf7af_fill_fields'   => $cf7af_recover_id ? $this->get_recover_fill_fields( $cf7af_recover_id ) : array(),
			);
			wp_localize_script( CF7AF_FRONT_SCRIPT_HANDLE, 'cf7af_abandoned', $cf7af_vars );
		}

		/**
		 * Build field data for recovering abandoned form values on the front end.
		 *
		 * @param int $recover_id Abandoned entry post ID.
		 * @return array
		 */
		private function get_recover_fill_fields( $recover_id ) {
			$post_info = get_post( $recover_id );
			if ( empty( $post_info ) ) {
				return array();
			}

			$form_id = CF7AF_Helpers::get_abandoned_entry_form_id( $recover_id );
			$contact_form = WPCF7_ContactForm::get_instance( $form_id );
			if ( ! $contact_form ) {
				return array();
			}

			$form_fields     = $contact_form->scan_form_tags();
			$cf7af_form_data = get_post_meta( $recover_id, 'cf7af_form_data', true );
			if ( ! is_array( $cf7af_form_data ) ) {
				return array();
			}

			$fill_fields = array();

			foreach ( $form_fields as $form_field ) {
				if (
					'' === $form_field->name
					|| ! isset( $cf7af_form_data[ $form_field->name ] )
					|| 'file' === $form_field->basetype
				) {
					continue;
				}

				$field_value = $cf7af_form_data[ $form_field->name ];
				$field_entry = array(
					'type'  => $form_field->basetype,
					'name'  => $form_field->name,
					'value' => $field_value,
				);

				if ( 'select' === $form_field->basetype && in_array( 'multiple', $form_field->options, true ) ) {
					$field_entry['type']  = 'select_multiple';
					$field_entry['value'] = '' !== $field_value
						? array_map( 'trim', explode( ',', $field_value ) )
						: array();
				} elseif ( 'checkbox' === $form_field->basetype && '' !== $field_value ) {
					$field_entry['value'] = array_map( 'trim', explode( ',', $field_value ) );
				}

				$fill_fields[] = $field_entry;
			}

			return $fill_fields;
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
