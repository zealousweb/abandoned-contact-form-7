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

		const META_NUMBER_SENTMAIL          = 'cf7af_number_sentmail';
		const META_NUMBER_FAIL_COUNT        = 'cf7af_number_fail_count';
		const LEGACY_META_NUMBER_SENTMAIL   = 'number_sentmail';
		const LEGACY_META_NUMBER_FAIL_COUNT = 'number_fail_count';

		const SESSION_FORM_PREFIX        = 'cf7af_session_form_';
		const LEGACY_SESSION_FORM_PREFIX = 'wp_cf7form_id_';
		const SESSION_KEY                = 'cf7af_session_key';
		const LEGACY_SESSION_KEY         = 'wp_cf7af_key';

		const RECOVER_QUERY_ARG        = 'cf7af_recover';
		const LEGACY_RECOVER_QUERY_ARG = 'recover';
		const TOKEN_QUERY_ARG          = 'cf7af_token';

		const COLUMN_NUMBER_SENTMAIL   = 'cf7af_number_sentmail';
		const COLUMN_NUMBER_FAIL_COUNT = 'cf7af_number_fail_count';

		const NONCE_SEND_MAIL = 'cf7af_send_mail_nonce';
		const NONCE_NOTIFY    = 'cf7af_notify_nonce';

		const CF7_EDITOR_PANEL = 'cf7af-abandoned-settings';

		/**
		 * Read abandoned-entry meta with legacy key fallback.
		 *
		 * @param int    $post_id Post ID.
		 * @param string $key     Logical meta key.
		 * @return mixed
		 */
		public static function get_abandoned_entry_meta( $post_id, $key ) {
			$post_id = absint( $post_id );
			$map     = array(
				'number_sentmail'   => array( self::META_NUMBER_SENTMAIL, self::LEGACY_META_NUMBER_SENTMAIL ),
				'number_fail_count' => array( self::META_NUMBER_FAIL_COUNT, self::LEGACY_META_NUMBER_FAIL_COUNT ),
			);

			if ( ! isset( $map[ $key ] ) ) {
				return get_post_meta( $post_id, $key, true );
			}

			$value = get_post_meta( $post_id, $map[ $key ][0], true );
			if ( '' === $value && '0' !== (string) $value ) {
				$legacy_value = get_post_meta( $post_id, $map[ $key ][1], true );
				if ( '' !== $legacy_value || '0' === (string) $legacy_value ) {
					update_post_meta( $post_id, $map[ $key ][0], $legacy_value );
					return $legacy_value;
				}
			}

			return $value;
		}

		/**
		 * Update abandoned-entry meta using the prefixed key.
		 *
		 * @param int    $post_id Post ID.
		 * @param string $key     Logical meta key.
		 * @param mixed  $value   Meta value.
		 * @return int|bool
		 */
		public static function update_abandoned_entry_meta( $post_id, $key, $value ) {
			$post_id = absint( $post_id );
			$map     = array(
				'number_sentmail'   => self::META_NUMBER_SENTMAIL,
				'number_fail_count' => self::META_NUMBER_FAIL_COUNT,
			);

			$meta_key = isset( $map[ $key ] ) ? $map[ $key ] : $key;

			return update_post_meta( $post_id, $meta_key, $value );
		}

		/**
		 * Session key for an in-progress abandoned entry.
		 *
		 * @param int $form_id CF7 form post ID.
		 * @return string
		 */
		public static function get_session_form_key( $form_id ) {
			return self::SESSION_FORM_PREFIX . absint( $form_id );
		}

		/**
		 * Read the session abandoned-entry ID for a form.
		 *
		 * @param int $form_id CF7 form post ID.
		 * @return int
		 */
		public static function get_session_form_id( $form_id ) {
			if ( ! session_id() ) {
				return 0;
			}

			$form_id = absint( $form_id );
			$new_key = self::get_session_form_key( $form_id );

			if ( isset( $_SESSION[ $new_key ] ) ) {
				return absint( $_SESSION[ $new_key ] );
			}

			$legacy_key = self::LEGACY_SESSION_FORM_PREFIX . $form_id;
			if ( isset( $_SESSION[ $legacy_key ] ) ) {
				$_SESSION[ $new_key ] = absint( $_SESSION[ $legacy_key ] );
				return absint( $_SESSION[ $legacy_key ] );
			}

			return 0;
		}

		/**
		 * Store the session abandoned-entry ID for a form.
		 *
		 * @param int $form_id CF7 form post ID.
		 * @param int $post_id Abandoned entry post ID.
		 */
		public static function set_session_form_id( $form_id, $post_id ) {
			if ( ! session_id() ) {
				return;
			}

			$_SESSION[ self::get_session_form_key( $form_id ) ] = absint( $post_id );
		}

		/**
		 * Remove the session abandoned-entry ID for a form.
		 *
		 * @param int $form_id CF7 form post ID.
		 */
		public static function unset_session_form_id( $form_id ) {
			if ( ! session_id() ) {
				return;
			}

			$form_id = absint( $form_id );
			unset( $_SESSION[ self::get_session_form_key( $form_id ) ] );
			unset( $_SESSION[ self::LEGACY_SESSION_FORM_PREFIX . $form_id ] );
		}

		/**
		 * Ensure the plugin session key exists.
		 */
		public static function ensure_session_key() {
			if ( ! session_id() ) {
				return;
			}

			if ( ! isset( $_SESSION[ self::SESSION_KEY ] ) && isset( $_SESSION[ self::LEGACY_SESSION_KEY ] ) ) {
				$_SESSION[ self::SESSION_KEY ] = absint( $_SESSION[ self::LEGACY_SESSION_KEY ] );
			}

			if ( ! isset( $_SESSION[ self::SESSION_KEY ] ) ) {
				$_SESSION[ self::SESSION_KEY ] = time();
			}
		}

		/**
		 * Refresh the plugin session key.
		 */
		public static function refresh_session_key() {
			if ( ! session_id() ) {
				return;
			}

			$_SESSION[ self::SESSION_KEY ] = time();
		}

		/**
		 * Read a sanitized integer from $_POST (prefixed key, then legacy fallback).
		 *
		 * Caller must verify the request nonce before invoking this helper.
		 *
		 * @param string $prefixed_key Prefixed POST field name.
		 * @param string $legacy_key   Legacy POST field name.
		 * @return int
		 */
		private static function get_post_int_value( $prefixed_key, $legacy_key ) {
			// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce is verified by the calling handler.
			if ( isset( $_POST[ $prefixed_key ] ) ) {
				return absint( wp_unslash( $_POST[ $prefixed_key ] ) );
			}

			if ( '' !== $legacy_key && isset( $_POST[ $legacy_key ] ) ) {
				return absint( wp_unslash( $_POST[ $legacy_key ] ) );
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			return 0;
		}

		/**
		 * Recover ID from POST with prefixed and legacy support.
		 *
		 * Caller must verify the AJAX nonce before calling this method.
		 *
		 * @return int
		 */
		public static function get_recover_id_from_post() {
			return self::get_post_int_value( self::RECOVER_QUERY_ARG, self::LEGACY_RECOVER_QUERY_ARG );
		}

		/**
		 * CF7 form ID from remove AJAX POST with prefixed and legacy support.
		 *
		 * Caller must verify the AJAX nonce before calling this method.
		 *
		 * @return int
		 */
		public static function get_cf7_id_from_post() {
			return self::get_post_int_value( 'cf7af_cf7_id', 'cf7_id' );
		}

		/**
		 * Recover ID from remove AJAX POST with prefixed and legacy support.
		 *
		 * Caller must verify the AJAX nonce before calling this method.
		 *
		 * @return int
		 */
		public static function get_recover_id_from_remove_post() {
			return self::get_post_int_value( 'cf7af_recover_id', 'recover_id' );
		}

		/**
		 * Read a POST value using the prefixed name with legacy fallback.
		 *
		 * Caller must verify the form nonce before calling this method.
		 * The returned value is unslashed; the caller must sanitize for its use case.
		 *
		 * @param string $prefixed_key Prefixed POST key.
		 * @param string $legacy_key   Legacy POST key.
		 * @return string
		 */
		public static function get_post_field_value( $prefixed_key, $legacy_key ) {
			$prefixed_key = sanitize_key( $prefixed_key );
			$legacy_key   = sanitize_key( $legacy_key );

			// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified by caller; caller sanitizes the returned value.
			if ( isset( $_POST[ $prefixed_key ] ) ) {
				return wp_unslash( $_POST[ $prefixed_key ] );
			}

			if ( '' !== $legacy_key && isset( $_POST[ $legacy_key ] ) ) {
				return wp_unslash( $_POST[ $legacy_key ] );
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			return '';
		}

		/**
		 * Recover ID from GET with prefixed and legacy support.
		 *
		 * @return int
		 */
		private static function get_recover_id_from_query() {
			$recover_id = filter_input( INPUT_GET, self::RECOVER_QUERY_ARG, FILTER_VALIDATE_INT );
			if ( $recover_id ) {
				return (int) $recover_id;
			}

			$recover_id = filter_input( INPUT_GET, self::LEGACY_RECOVER_QUERY_ARG, FILTER_VALIDATE_INT );
			return $recover_id ? (int) $recover_id : 0;
		}

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

			if ( ! isset( $_GET['cf7af_form_id'], $_GET['cf7af_filter_nonce'] ) || '' === $_GET['cf7af_form_id'] ) {
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

			if (
				! wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_GET['cf7af_filter_nonce'] ) ),
					'cf7af_filter_posts'
				)
			) {
				return '';
			}

			return sanitize_text_field( wp_unslash( $_GET['cf7af_form_id'] ) );
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
					'cf7af_abandoned_id' => $abandoned_id,
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

			if ( ! isset( $_GET['cf7af_abandoned_id'], $_GET['_wpnonce'] ) || '' === $_GET['cf7af_abandoned_id'] ) {
				if ( ! isset( $_GET['abandoned_id'], $_GET['_wpnonce'] ) || '' === $_GET['abandoned_id'] ) {
					return 0;
				}
				$abandoned_id = absint( wp_unslash( $_GET['abandoned_id'] ) );
			} else {
				$abandoned_id = absint( wp_unslash( $_GET['cf7af_abandoned_id'] ) );
			}

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
		 * Post meta key for the hashed recovery token.
		 *
		 * @return string
		 */
		public static function get_recover_token_meta_key() {
			return CF7AF_META_PREFIX . 'recover_token_hash';
		}

		/**
		 * Issue a new recovery token and store its hash on the abandoned entry.
		 *
		 * @param int $abandoned_id Abandoned entry post ID.
		 * @return string Plain recovery token, or empty string on failure.
		 */
		public static function issue_recover_token( $abandoned_id ) {
			$abandoned_id = absint( $abandoned_id );
			if ( ! self::is_abandoned_post( $abandoned_id ) ) {
				return '';
			}

			$token = wp_generate_password( 32, false, false );
			update_post_meta( $abandoned_id, self::get_recover_token_meta_key(), wp_hash_password( $token ) );

			return $token;
		}

		/**
		 * Verify a plain recovery token against the stored hash.
		 *
		 * @param int    $abandoned_id Abandoned entry post ID.
		 * @param string $token        Plain recovery token from the request.
		 * @return bool
		 */
		public static function verify_recover_token( $abandoned_id, $token ) {
			$abandoned_id = absint( $abandoned_id );
			if ( ! $abandoned_id || ! is_string( $token ) || '' === $token ) {
				return false;
			}

			$hash = get_post_meta( $abandoned_id, self::get_recover_token_meta_key(), true );
			if ( ! is_string( $hash ) || '' === $hash ) {
				return false;
			}

			return wp_check_password( $token, $hash );
		}

		/**
		 * Query args for a front-end recovery link (includes token).
		 *
		 * @param int $abandoned_id Abandoned entry post ID.
		 * @return array
		 */
		public static function get_recover_link_args( $abandoned_id ) {
			$abandoned_id = absint( $abandoned_id );
			$token        = self::issue_recover_token( $abandoned_id );

			if ( '' === $token ) {
				return array();
			}

			return array(
				self::RECOVER_QUERY_ARG => $abandoned_id,
				self::TOKEN_QUERY_ARG   => $token,
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

			$args = self::get_recover_link_args( $abandoned_id );
			if ( empty( $args ) ) {
				return '';
			}

			return add_query_arg( $args, $page_url );
		}

		/**
		 * Plain recovery token from the query string.
		 *
		 * Public recovery links authenticate with cf7af_token instead of a WordPress nonce.
		 *
		 * @return string
		 */
		private static function get_recover_token_from_query() {
			$token = filter_input( INPUT_GET, self::TOKEN_QUERY_ARG );

			return is_string( $token ) ? sanitize_text_field( $token ) : '';
		}

		/**
		 * Verified recover ID from a public recovery link (GET).
		 *
		 * @return int
		 */
		public static function get_recover_id_from_request() {
			$recover_id = self::get_recover_id_from_query();
			$token      = self::get_recover_token_from_query();

			if ( ! $recover_id || ! is_string( $token ) || '' === $token ) {
				return 0;
			}

			if ( ! self::is_abandoned_post( $recover_id ) ) {
				return 0;
			}

			if ( ! self::verify_recover_token( $recover_id, $token ) ) {
				return 0;
			}

			return (int) $recover_id;
		}

		/**
		 * Plain recovery token from the current request (after ID verification).
		 *
		 * @return string
		 */
		public static function get_recover_token_from_request() {
			if ( ! self::get_recover_id_from_request() ) {
				return '';
			}

			return self::get_recover_token_from_query();
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

		/**
		 * Contact Form 7 form ID linked to an abandoned entry.
		 *
		 * Uses post_parent (indexed) with post meta fallback for legacy rows.
		 *
		 * @param int $abandoned_id Abandoned entry post ID.
		 * @return int
		 */
		public static function get_abandoned_entry_form_id( $abandoned_id ) {
			$abandoned_id = absint( $abandoned_id );
			if ( ! $abandoned_id ) {
				return 0;
			}

			$form_id = (int) get_post_field( 'post_parent', $abandoned_id );
			if ( $form_id > 0 ) {
				return $form_id;
			}

			return (int) get_post_meta( $abandoned_id, 'cf7af_form_id', true );
		}

		/**
		 * Keep post_parent and post_excerpt in sync for query performance.
		 *
		 * @param int    $abandoned_id Abandoned entry post ID.
		 * @param int    $form_id      CF7 contact form post ID.
		 * @param string $email        Abandoned user email.
		 */
		public static function sync_abandoned_entry_post_fields( $abandoned_id, $form_id, $email = '' ) {
			$abandoned_id = absint( $abandoned_id );
			$form_id      = absint( $form_id );

			if ( ! $abandoned_id ) {
				return;
			}

			$update = array( 'ID' => $abandoned_id );

			if ( $form_id > 0 ) {
				$update['post_parent'] = $form_id;
			}

			if ( is_string( $email ) && '' !== $email ) {
				$update['post_excerpt'] = sanitize_text_field( $email );
			}

			if ( count( $update ) > 1 ) {
				wp_update_post( $update );
			}
		}

		/**
		 * One-time migration: copy cf7af_form_id and cf7af_email into post columns.
		 */
		public static function maybe_sync_abandoned_post_data() {
			if ( (int) get_option( CF7AF_OPTION_POST_DATA_SYNCED, 0 ) >= 1 ) {
				return;
			}

			$post_ids = get_posts(
				array(
					'post_type'      => CF7AF_POST_TYPE,
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);

			foreach ( $post_ids as $post_id ) {
				$form_id = (int) get_post_meta( $post_id, 'cf7af_form_id', true );
				$email   = get_post_meta( $post_id, 'cf7af_email', true );
				self::sync_abandoned_entry_post_fields(
					$post_id,
					$form_id,
					is_string( $email ) ? $email : ''
				);
			}

			update_option( CF7AF_OPTION_POST_DATA_SYNCED, 1, false );
		}

		/**
		 * Sanitize abandoned form field data from the front-end AJAX payload.
		 *
		 * @param array $forms Serialized form fields (name/value pairs).
		 * @return array
		 */
		public static function sanitize_abandoned_forms( $forms ) {
			if ( ! is_array( $forms ) ) {
				return array();
			}

			$sanitized = array();

			foreach ( $forms as $form_field ) {
				if ( ! is_array( $form_field ) ) {
					continue;
				}

				$name = isset( $form_field['name'] ) ? sanitize_text_field( $form_field['name'] ) : '';
				if ( '' === $name ) {
					continue;
				}

				$sanitized[] = array(
					'name'  => $name,
					'value' => isset( $form_field['value'] ) ? sanitize_text_field( $form_field['value'] ) : '',
				);
			}

			return $sanitized;
		}

		/**
		 * Resolve and sanitize the client IP address from server variables.
		 *
		 * @return string
		 */
		public static function get_client_ip_address() {
			$ip = '';

			if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
			} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && '' !== $_SERVER['HTTP_X_FORWARDED_FOR'] ) {
				$forwarded_ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
				$ip            = trim( $forwarded_ips[0] );
			} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
			}

			return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
		}

	}
}
