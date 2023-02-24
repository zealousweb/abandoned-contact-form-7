( function($) {
	"use strict";

	jQuery(document).ready( function($) {

		jQuery( '#cf7af-enable-abandoned-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#cf7af-enable-abandoned-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.cf7af_enable_abandoned,
				position: 'left center',
			} ).pointer('open');
		});

		jQuery( '#cf7af-abandoned-email-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#cf7af-abandoned-email-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.cf7af_abandoned_email,
				position: 'left center',
			} ).pointer('open');
		});
<<<<<<< HEAD

		jQuery( '#cf7af-mailer-type-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#cf7af-mailer-type-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.cf7af_mailer_type,
				position: 'left center',
			} ).pointer('open');
		});

		jQuery( '#cf7af-notification-time-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#cf7af-notification-time-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.cf7af_notification_time,
				position: 'left center',
			} ).pointer('open');
		});

		jQuery( '#cf7af-nums-email-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#cf7af-nums-email-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.cf7af_nums_email,
				position: 'left center',
			} ).pointer('open');
		});

=======
		jQuery( '#cf7af-abandoned-specific-field-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#cf7af-abandoned-specific-field-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.cf7af_abandoned_specific_field,
				position: 'left center',
			} ).pointer('open');
		});
>>>>>>> 19b10dee14580a8ba01b012ccc6478c0dad2c1b4
		jQuery( '#cf7af-subject-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#cf7af-subject-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.cf7af_subject,
				position: 'left center',
			} ).pointer('open');
		});

		jQuery( '#cf7af-email-body-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#cf7af-email-body-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.cf7af_email_body,
				position: 'left center',
			} ).pointer('open');
		});

		jQuery( '#abandoned-email-address-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#abandoned-email-address-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.abandoned_email_address,
				position: 'left center',
			} ).pointer('open');
		});

		jQuery( '#abandoned-from-name-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#abandoned-from-name-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.abandoned_from_name,
				position: 'left center',
			} ).pointer('open');
		});

		jQuery( '#abandoned-from-email-address-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#abandoned-from-email-address-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.abandoned_from_email_address,
				position: 'left center',
			} ).pointer('open');
		});

		jQuery( '#abandoned-subject-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#abandoned-subject-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.abandoned_subject,
				position: 'left center',
			} ).pointer('open');
		});

		jQuery( '#abandoned-email-body-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#abandoned-email-body-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.abandoned_email_body,
				position: 'left center',
			} ).pointer('open');
		});

		jQuery( '#cf7af-from-email-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#cf7af-from-email-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.cf7af_from_email,
				position: 'left center',
			} ).pointer('open');
		});

		jQuery( '#cf7af-from-name-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#cf7af-from-name-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.cf7af_from_name,
				position: 'left center',
			} ).pointer('open');
<<<<<<< HEAD
		})

		jQuery( '#cf7af-smtp-host-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#cf7af-smtp-host-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.cf7af_smtp_host,
				position: 'left center',
			} ).pointer('open');
		});

		jQuery( '#cf7af-smtp-ency-type-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#cf7af-smtp-ency-type-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.cf7af_smtp_ency_type,
				position: 'left center',
			} ).pointer('open');
		});

		jQuery( '#cf7af-smtp-port-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#cf7af-smtp-port-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.cf7af_smtp_port,
				position: 'left center',
			} ).pointer('open');
		});

		jQuery( '#cf7af-smtp-auth-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#cf7af-smtp-auth-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.cf7af_smtp_auth,
				position: 'left center',
			} ).pointer('open');
		});

		jQuery( '#cf7af-smtp-username-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#cf7af-smtp-username-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.cf7af_smtp_username,
				position: 'left center',
			} ).pointer('open');
		});

		jQuery( '#cf7af-smtp-password-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#cf7af-smtp-password-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.cf7af_smtp_password,
				position: 'left center',
			} ).pointer('open');
		});

		jQuery( '#cf7af-test-to-email-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#cf7af-test-to-email-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.cf7af_test_to_email,
				position: 'left center',
			} ).pointer('open');
		});

		jQuery( '#cf7af-test-subject-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#cf7af-test-subject-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.cf7af_test_subject,
				position: 'left center',
			} ).pointer('open');
		});

		jQuery( '#cf7af-test-message-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#cf7af-test-message-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.cf7af_test_message,
				position: 'left center',
			} ).pointer('open');
=======
>>>>>>> 19b10dee14580a8ba01b012ccc6478c0dad2c1b4
		});

	});

} )( jQuery );