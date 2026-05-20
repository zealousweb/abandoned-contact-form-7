<?php
/**
 * Request and nonce helpers for Abandoned Contact Form 7.
 *
 * @package Abandoned Contact Form 7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CF7AF_Helpers' ) ) {

	/**
	 * Security helpers for admin and public recovery URLs.
	 */
	class CF7AF_Helpers {

		/**
		 * Whether a post is an abandoned entry.
		 *
		 * @param int $post_id Post ID.
		 * @return bool
		 */
		public static function is_abandoned_post( $post_id ) {
			$post = get_post( absint( $post_id ) );
			return $post && CF7AF_POST_TYPE === $post->post_type;
		}

		/**
		 * Verify Contact Form 7 save nonce for a form ID.
		 *
		 * @param int $post_id CF7 contact form post ID.
		 * @return bool
		 */
		public static function verify_wpcf7_save_nonce( $post_id ) {
			$post_id = absint( $post_id );
			if ( ! $post_id || ! isset( $_POST['_wpnonce'] ) ) {
				return false;
			}

			return (bool) wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ),
				'wpcf7-save-contact-form_' . $post_id
			);
		}

		/**
		 * Resolve CF7 contact form ID in the admin editor.
		 *
		 * @return int
		 */
		public static function get_wpcf7_editor_post_id() {
			if ( function_exists( 'wpcf7_current_user_can_manage' ) && ! wpcf7_current_user_can_manage() ) {
				return 0;
			}

			if ( isset( $_GET['post'], $_GET['_wpnonce'] ) ) {
				$post_id = absint( wp_unslash( $_GET['post'] ) );
				if (
					$post_id
					&& wp_verify_nonce(
						sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ),
						'wpcf7-save-contact-form_' . $post_id
					)
				) {
					return $post_id;
				}
			}

			if ( class_exists( 'WPCF7_ContactForm' ) ) {
				$wpcf7 = WPCF7_ContactForm::get_current();
				if ( $wpcf7 ) {
					return (int) $wpcf7->id();
				}
			}

			return 0;
		}

		/**
		 * Verified form-id from the abandoned users list filter (GET).
		 *
		 * @return string Sanitized form ID or empty string.
		 */
		public static function get_list_filter_form_id() {
			if ( ! is_admin() || ! current_user_can( 'edit_posts' ) ) {
				return '';
			}

			if ( ! isset( $_GET['form-id'], $_GET['cf7af_filter_nonce'] ) || '' === $_GET['form-id'] ) {
				return '';
			}

			if (
				! wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_GET['cf7af_filter_nonce'] ) ),
					'cf7af_filter_posts'
				)
			) {
				return '';
			}

			return sanitize_text_field( wp_unslash( $_GET['form-id'] ) );
		}

		/**
		 * Admin URL for the send-mail screen with nonce.
		 *
		 * @param int $abandoned_id Abandoned entry post ID.
		 * @return string
		 */
		public static function get_send_mail_admin_url( $abandoned_id ) {
			$abandoned_id = absint( $abandoned_id );

			return add_query_arg(
				array(
					'post_type'    => CF7AF_POST_TYPE,
					'page'         => 'cf7af-send-mail',
					'abandoned_id' => $abandoned_id,
					'_wpnonce'     => wp_create_nonce( 'cf7af_send_mail_' . $abandoned_id ),
				),
				admin_url( 'edit.php' )
			);
		}

		/**
		 * Verified abandoned entry ID for the send-mail admin page (GET).
		 *
		 * @return int
		 */
		public static function get_send_mail_abandoned_id() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return 0;
			}

			if ( ! isset( $_GET['abandoned_id'], $_GET['_wpnonce'] ) || '' === $_GET['abandoned_id'] ) {
				return 0;
			}

			$abandoned_id = absint( wp_unslash( $_GET['abandoned_id'] ) );

			if (
				! $abandoned_id
				|| ! self::is_abandoned_post( $abandoned_id )
				|| ! wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ),
					'cf7af_send_mail_' . $abandoned_id
				)
			) {
				return 0;
			}

			return $abandoned_id;
		}

		/**
		 * Query args for a front-end recovery link (includes nonce).
		 *
		 * @param int $abandoned_id Abandoned entry post ID.
		 * @return array
		 */
		public static function get_recover_link_args( $abandoned_id ) {
			$abandoned_id = absint( $abandoned_id );

			return array(
				'recover'              => $abandoned_id,
				'cf7af_recover_nonce'  => wp_create_nonce( 'cf7af_recover_' . $abandoned_id ),
			);
		}

		/**
		 * Build a recovery URL on a page URL.
		 *
		 * @param string $page_url     Page URL stored on the entry.
		 * @param int    $abandoned_id Abandoned entry post ID.
		 * @return string
		 */
		public static function build_recover_url( $page_url, $abandoned_id ) {
			$page_url = (string) $page_url;
			if ( '' === $page_url ) {
				return '';
			}

			if ( false !== strpos( $page_url, 'recover=' ) ) {
				return $page_url;
			}

			return add_query_arg( self::get_recover_link_args( $abandoned_id ), $page_url );
		}

		/**
		 * Verified recover ID from a public recovery link (GET).
		 *
		 * New links include cf7af_recover_nonce. Legacy email links without a nonce
		 * are accepted only when the ID is a valid abandoned entry.
		 *
		 * @return int
		 */
		public static function get_recover_id_from_request() {
			$recover_id = filter_input( INPUT_GET, 'recover', FILTER_VALIDATE_INT );
			if ( ! $recover_id || ! self::is_abandoned_post( $recover_id ) ) {
				return 0;
			}

			$nonce = filter_input( INPUT_GET, 'cf7af_recover_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( is_string( $nonce ) && '' !== $nonce ) {
				if ( ! wp_verify_nonce( $nonce, 'cf7af_recover_' . $recover_id ) ) {
					return 0;
				}
				return (int) $recover_id;
			}

			return (int) $recover_id;
		}

		/**
		 * Recover ID as a string for front-end script localization.
		 *
		 * @return string
		 */
		public static function get_recover_id_for_script() {
			$recover_id = self::get_recover_id_from_request();
			return $recover_id ? (string) $recover_id : '';
		}

	}
}
