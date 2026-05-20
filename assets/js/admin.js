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

		jQuery( '#cf7af-abandoned-specific-field-pointer' ).on( 'hover click', function() {
			jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
			jQuery( '#cf7af-abandoned-specific-field-pointer' ).pointer({
				pointerClass: 'wp-pointer cf7af-pointer',
				content: translate_string_cf7af.cf7af_abandoned_specific_field,
				position: 'left center',
			} ).pointer('open');
		});
		
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

		var $cf7afFieldCheckboxes = jQuery( '#cf7af-abandoned-specific-fields .cf7af-field-picker__checkbox' );

		jQuery( '#cf7af-select-all-fields' ).on( 'click', function( e ) {
			e.preventDefault();
			$cf7afFieldCheckboxes.prop( 'checked', true );
		} );

		jQuery( '#cf7af-clear-all-fields' ).on( 'click', function( e ) {
			e.preventDefault();
			$cf7afFieldCheckboxes.prop( 'checked', false );
		} );

	});

} )( jQuery );