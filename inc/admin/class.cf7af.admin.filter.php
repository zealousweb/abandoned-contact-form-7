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
			add_filter( 'posts_search',         				 array( $this, 'filter__posts_search_abandoned_email' ), 10, 2 );
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

			$cf7_post_id = CF7AF_Helpers::get_wpcf7_editor_post_id();

			$tags = array();
			if ( $cf7_post_id ) {
				$cf7 = WPCF7_ContactForm::get_instance( $cf7_post_id );
				if ( $cf7 ) {
					$tags = $cf7->collect_mail_tags();
				}
			}
			
			if ( ! empty( $tags ) ) {
				$panels[ CF7AF_Helpers::CF7_EDITOR_PANEL ] = array(
					'title'    => __( 'Abandoned Form Settings', 'abandoned-contact-form-7' ),
					'callback' => array( $this, 'wpcf7_admin_after_additional_settings' ),
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
			$columns[ CF7AF_Helpers::COLUMN_NUMBER_SENTMAIL ]   = CF7AF_Helpers::COLUMN_NUMBER_SENTMAIL;
			$columns['cf7af_email']                             = 'cf7af_email';
			$columns[ CF7AF_Helpers::COLUMN_NUMBER_FAIL_COUNT ] = CF7AF_Helpers::COLUMN_NUMBER_FAIL_COUNT;
			return $columns;
		}

		/**
		 * Filter: posts_search
		 *
		 * Search abandoned user email via post_excerpt (synced from cf7af_email meta).
		 *
		 * @param string   $search Search SQL.
		 * @param WP_Query $query  Current query.
		 * @return string
		 */
		function filter__posts_search_abandoned_email( $search, $query ) {
			if ( ! is_admin() || CF7AF_POST_TYPE !== $query->get( 'post_type' ) ) {
				return $search;
			}

			$search_term = $query->get( 's' );
			if ( ! is_string( $search_term ) || '' === $search_term ) {
				return $search;
			}

			global $wpdb;

			$like = '%' . $wpdb->esc_like( $search_term ) . '%';

			return $wpdb->prepare( " AND ({$wpdb->posts}.post_excerpt LIKE %s) ", $like );
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
			$columns['cf7af_email'] = __( 'Abandoned User\'s Email', 'abandoned-contact-form-7' );
			$columns['cf7af_ip_address'] = __( 'IP Address', 'abandoned-contact-form-7' );
			$columns['cf7af_send_mail'] = __( 'Send Mail', 'abandoned-contact-form-7' );
			$columns[ CF7AF_Helpers::COLUMN_NUMBER_SENTMAIL ]   = __( 'Number of Emails Sent', 'abandoned-contact-form-7' );
			$columns[ CF7AF_Helpers::COLUMN_NUMBER_FAIL_COUNT ] = __( 'Fail Counter', 'abandoned-contact-form-7' );
			$columns['date'] = __( 'Submitted Date', 'abandoned-contact-form-7' );
			return $columns;
		}

		/**
		 * Adding Abandoned Setting Fields in Abandoned Settings tab
		 *
		 * @param $cf7
		 */
		public function wpcf7_admin_after_additional_settings( $cf7 ) {

			wp_enqueue_script( CF7AF_ADMIN_SCRIPT_HANDLE );

			require_once( CF7AF_DIR . '/inc/admin/template/' . CF7AF_PREFIX . '.template.php' );

		}
	}
	
	add_action( 'plugins_loaded', function() {
		CF7AF()->admin->filter = new CF7AF_Admin_Filter;
	} );
}