<?php
/**
 * CF7AF_Admin_Filter Class
 *
 * Handles the admin functionality.
 *
 * @package WordPress
 * @subpackage Abandoned Contact Form 7
 * @since 1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CF7AF_Admin_Filter' ) ) {

	/**
	 *  The CF7AF_Admin_Filter Class
	 */
	class CF7AF_Admin_Filter {

		function __construct() {

			// Adding setting tab
			add_filter( 'wpcf7_editor_panels', array( $this, 'filter__wpcf7af_editor_panels' ), 10, 3 );
			add_filter( 'post_row_actions',    array( $this, 'filter__post_row_actions' ), 10, 3 );
			add_filter( 'manage_edit-cf7af_data_sortable_columns', array( $this, 'filter__manage_cf7af_data_sortable_columns' ), 10, 3 );
			add_filter( 'manage_cf7af_data_posts_columns',       array( $this, 'filter__manage_cf7af_data_posts_columns' ), 10, 3 );
			add_filter( 'wpforms_display_media_button',        	 array( $this, 'filter__wpf_dev_remove_media_button' ) );
			add_filter( 'pre_get_posts',         				 array( $this, 'filter__pre_get_posts' ) );
		}

		/*
		######## #### ##       ######## ######## ########   ######
		##        ##  ##          ##    ##       ##     ## ##    ##
		##        ##  ##          ##    ##       ##     ## ##
		######    ##  ##          ##    ######   ########   ######
		##        ##  ##          ##    ##       ##   ##         ##
		##        ##  ##          ##    ##       ##    ##  ##    ##
		##       #### ########    ##    ######## ##     ##  ######
		*/

		/**
		 * Filter: wpforms_display_media_button
		 *
		 * - Used to remove the 'Add Form' WPForms TinyMCE button.
		 *
		 * @method filter__wpf_dev_remove_media_button
		 *
		 * @param  array $display
		 *
		 * @return array
		 */
		function filter__wpf_dev_remove_media_button( $display ) {

			$screen = get_current_screen();

			if ( CF7AF_POST_TYPE == $screen->post_type ) {
				return false;
			}
			return $display;
		}

		/**
		 * Filter: wpcf7_editor_panels
		 *
		 * - Used add settings tab in contact form 7 detail
		 *
		 * @method filter__wpcf7af_editor_panels
		 *
		 * @param  array $panels
		 *
		 * @return array
		 */
		public function filter__wpcf7af_editor_panels( $panels ) {

			$post_id = ( isset( $_REQUEST[ 'post' ] ) ? sanitize_text_field( $_REQUEST[ 'post' ] ) : '' );

			if ( empty( $post_id ) ) {
				$wpcf7 = WPCF7_ContactForm::get_current();
				$post_id = $wpcf7->id();
			}

			if ( !empty( $post_id ) ) {
				$cf7 = WPCF7_ContactForm::get_instance($_GET['post']);
				$tags = $cf7->collect_mail_tags();
			}

			if( !empty( $tags ) ){
				$panels[ 'Abandoned-add-on' ] = array(
					'title'    => __( 'Abandoned Form Settings', 'cf7-abandoned-form' ),
					'callback' => array( $this, 'wpcf7_admin_after_additional_settings' )
				);
			}

			return $panels;
		}

		/**
		 * Filter: post_row_actions
		 *
		 * - Used to modify the post list action buttons.
		 *
		 * @method filter__post_row_actions
		 *
		 * @param  array $actions
		 *
		 * @return array
		 */
		function filter__post_row_actions( $actions ) {

			if ( get_post_type() === CF7AF_POST_TYPE ) {
				unset( $actions['view'] );
				unset( $actions['inline hide-if-no-js'] );
			}
			return $actions;
		}

		/**
		 * Filter: manage_edit-cf7af_data_sortable_columns
		 *
		 * - Used to add the sortable fields into "cf7af_data" CPT
		 *
		 * @method filter__manage_cf7af_data_sortable_columns
		 *
		 * @param  array $columns
		 *
		 * @return array
		 */
		function filter__manage_cf7af_data_sortable_columns( $columns ) {
			$columns['number_sentmail'] = 'number_sentmail';
			$columns['cf7af_email'] = 'cf7af_email';
			$columns['number_fail_count'] = 'number_fail_count';
			return $columns;
		}

		/**
		 * Filter: pre_get_posts
		 *
		 * - Used to search post meta
		 *
		 * @method filter__pre_get_posts
		 *
		 * @param  array $query
		 */
		function filter__pre_get_posts( $query ){

			// Extend search for document post type
			$post_type = CF7AF_POST_TYPE;

			// Custom fields to search for
			$custom_fields = array( "cf7af_email" );

			if( ! is_admin() )
				return;

			if ( $query->query['post_type'] != $post_type )
				return;

			$search_term = $query->query_vars['s'];

			// Set to empty, otherwise it won't find anything
			$query->query_vars['s'] = '';

			if ( $search_term != '' ) {
				$meta_query = array( 'relation' => 'OR' );

				foreach( $custom_fields as $custom_field ) {
					array_push( $meta_query, array(
						'key' => $custom_field,
						'value' => $search_term,
						'compare' => 'LIKE'
					));
				}

				$query->set( 'meta_query', $meta_query );
			};
		}

		/**
		 * Filter: manage_cf7af_data_posts_columns
		 *
		 * - Used to add new column fields for the "cf7af_data" CPT
		 *
		 * @method filter__manage_cf7af_data_posts_columns
		 *
		 * @param  array $columns
		 *
		 * @return array $columns
		 */
		function filter__manage_cf7af_data_posts_columns( $columns ) {
			unset( $columns['date'] );
			$columns['cf7af_email'] = __( 'Abandoned User\'s Email', 'cf7-abandoned-form' );
			$columns['cf7af_ip_address'] = __( 'IP Address', 'cf7-abandoned-form' );
			$columns['cf7af_send_mail'] = __( 'Send Mail', 'cf7-abandoned-form' );
			$columns['number_sentmail'] = __( 'Number of Emails Sent', 'cf7-abandoned-form' );
			$columns['number_fail_count'] = __( 'Fail Counter', 'cf7-abandoned-form' );
			$columns['date'] = __( 'Submitted Date', 'cf7-abandoned-form' );
			return $columns;
		}

		/**
		 * Filter: wp_mail_content_type
		 *
		 * - Used to send HTML formatted emails with WordPress wp_mail()
		 *
		 * @method filter__wp_set_mail_content_type
		 *
		 * @return str
		 */
		function filter__wp_set_mail_content_type(){
			return "text/html";
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
		 * Adding Abandoned Setting Fields in Abandoned Settings tab
		 *
		 * @param $cf7
		 */
		public function wpcf7_admin_after_additional_settings( $cf7 ) {

			wp_enqueue_script( CF7AF_PREFIX . '_admin_js' );

			require_once( CF7AF_DIR . '/inc/admin/template/' . CF7AF_PREFIX . '.template.php' );

		}
	}
	
	add_action( 'plugins_loaded', function() {
		CF7AF()->admin->filter = new CF7AF_Admin_Filter;
	} );
}