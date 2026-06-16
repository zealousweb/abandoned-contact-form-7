/* globals cf7af_abandoned */
(function($) {

	var data = {},
		json = false,
		sent = false;

	var ZealCF7AFAbandoned = {
		/**
		 * Start the engine.
		 *
		 * @since 1.0.0
		 */
		init: function() {

			/* Determine if we have touch support */
			var touchCapable = 'ontouchstart' in window || window.DocumentTouch && document instanceof window.DocumentTouch || navigator.maxTouchPoints > 0 || window.navigator.msMaxTouchPoints > 0;

			/* Form interactions */
			$(document).on( 'input', '.wpcf7-form :input', ZealCF7AFAbandoned.prepData );
			$(document).on( 'change', '.wpcf7-form input[type=radio]', ZealCF7AFAbandoned.prepData );
			$(document).on( 'change', '.wpcf7-form input[type=checkbox]', ZealCF7AFAbandoned.prepData );
			$(document).on( 'change', '.wpcf7-form input[type=file]', ZealCF7AFAbandoned.prepData );

			window.addEventListener('load', function(){ // on page load
				inputs = $('.wpcf7-form :input');
				document.body.addEventListener('touchstart', function(e){
					$(document).on( 'input', '.wpcf7-form :input', ZealCF7AFAbandoned.prepData );
					$(document).on( 'change', '.wpcf7-form input[type=radio]', ZealCF7AFAbandoned.prepData );
					$(document).on( 'change', '.wpcf7-form input[type=checkbox]', ZealCF7AFAbandoned.prepData );
					$(document).on( 'change', '.wpcf7-form input[type=file]', ZealCF7AFAbandoned.prepData );
				}, false);
			}, false);

			/* Abandoned events */
			$(document).on( 'mouseleave', this.abandonMouse );
			if ( touchCapable ) {
				$(document).on( 'touchend', this.abandonMouse );
				$(document).on( 'click', this.abandonClick );
			} else {
				$(document).on( 'mousedown', this.abandonClick );
			}

		},

		/**
		 * As the field inputs change, update the data on the fly.
		 *
		 * @since 1.0.0
		 */
		prepData: function( event ) {
			
			var $form  = $(event.target).closest('.wpcf7-form');
			data = $form.serializeArray();
			json = data;
			ZealCF7AFAbandoned.debug( 'Preping data' );
		},
		/**
		 * Send the data.
		 *
		 * @since 1.0.0
		 */
		sendData: function() {
			/* Don't do anything if the user has not starting filling out a form  or if we have already recently sent one */
			if ( ! json || sent ) {
				return;
			}
			/* This is used to rate limit so that we never post more than once every 10 seconds */
			sent = true;
			setTimeout( function() {
				sent = false;
			}, 2000 );

			ZealCF7AFAbandoned.debug( 'Sending' );

			/* Send the form(s) data via ajax */
			var cf7af_data = {
				'action': 'cf7af_track_abandoned',
				'page_url': window.location.href,
				'cf7af_recover': cf7af_abandoned.cf7af_recover,
				'cf7af_token': cf7af_abandoned.cf7af_recover_token,
				'cf7af_abandoned_nonce': cf7af_abandoned.cf7af_nonce,
				forms: json
			}

			$.ajax({
			  type: "POST",
			  dataType: "json",
			  url: cf7af_abandoned.ajaxurl,
			  data: cf7af_data,
			  success: function(response) {
			  }
			});
			data = {};
			json = false;
		},
		/**
		 * Abandoned via mouseleave.
		 *
		 * This triggers when the user's mouse leaves the page.
		 *
		 * @since 1.0.0
		 */
		abandonMouse: function( event ) {

			ZealCF7AFAbandoned.debug( 'Mouse abandoned' );

			ZealCF7AFAbandoned.sendData();
		},
		/**
		 * Abaondoned via click.
		 *
		 * This triggers when the user clicks on the page.
		 *
		 * @since 1.0.0
		 */
		 abandonClick: function(event) {

			var el = event.srcElement || event.target;

			// Loop up the DOM tree through parent elements if clicked element is not a link (eg: an image inside a link)
			while ( el && (typeof el.tagName === 'undefined' || el.tagName.toLowerCase() !== 'a' || !el.href ) ) {
				el = el.parentNode;
			}

			// If a link with valid href has been clicked
			if ( el && el.href ) {

				ZealCF7AFAbandoned.debug( 'Click abandoned' );

				var link     = el.href,
					type     = 'internal';

				// Determine click event type
				if ( el.protocol === 'mailto' ) { // Email
					type = 'mailto';
				} else if ( cf7af_abandoned.home_url && link.indexOf( cf7af_abandoned.home_url ) === -1 ) { // Outbound
					type = 'external';
				}

				// Trigger form abandonment with internal and external links *
				if ( ( type === 'external' || type === 'internal' ) && ! link.match( /^javascript\:/i ) ) {

					// Is actual target set and not _(self|parent|top)?
					var target = ( el.target && !el.target.match( /^_(self|parent|top)$/i ) ) ? el.target : false;

					// Assume a target if Ctrl|shift|meta-click
					if ( event.ctrlKey || event.shiftKey || event.metaKey || event.which === 2 ) {
						target = '_blank';
					}

					if ( target ) {

						// If target opens a new window then just trigger abandoned entry
						ZealCF7AFAbandoned.sendData();

					} else {

						// Prevent standard click, track then open
						if ( event.preventDefault ) {
							event.preventDefault();
						} else {
							event.returnValue = false;
						}

						// Trigger abandoned entry
						ZealCF7AFAbandoned.sendData();

						// Proceed to URL
						window.location.href = link;
					}
				}
			}
		},
		/**
		 * Populate recovered form field values from localized data.
		 *
		 * @since 2.7
		 */
		fillRecoverForm: function() {
			if ( ! cf7af_abandoned.cf7af_fill_fields || ! cf7af_abandoned.cf7af_fill_fields.length ) {
				return;
			}

			cf7af_abandoned.cf7af_fill_fields.forEach( function( field ) {
				var i, chkArr, chkLength;

				switch ( field.type ) {
					case 'textarea':
						var textarea = document.querySelector( 'textarea[name="' + field.name + '"]' );
						if ( textarea ) {
							textarea.value = field.value;
						}
						break;

					case 'radio':
						jQuery( 'input[name="' + field.name + '"][value="' + field.value + '"]' ).prop( 'checked', true );
						break;

					case 'select_multiple':
						if ( field.value && field.value.length ) {
							field.value.forEach( function( optionValue ) {
								jQuery( 'select[name="' + field.name + '[]"] option[value="' + optionValue + '"]' ).attr( 'selected', 'selected' );
							} );
						}
						break;

					case 'select':
						jQuery( 'select[name="' + field.name + '"] option[value="' + field.value + '"]' ).attr( 'selected', 'selected' );
						break;

					case 'checkbox':
						if ( field.value && field.value.length ) {
							chkArr = document.getElementsByName( field.name + '[]' );
							chkLength = chkArr.length;
							for ( i = 0; i < chkLength; i++ ) {
								if ( field.value.includes( chkArr[i].value ) ) {
									chkArr[i].checked = true;
								}
							}
						}
						break;

					default:
						if ( field.name && field.value !== '' ) {
							jQuery( 'input[name="' + field.name + '"]' ).val( field.value );
						}
						break;
				}
			} );
		},
		/**
		 * Optional debug messages.
		 *
		 * @since 1.x.x
		 */
		debug: function( msg ) {
			if ( window.location.hash && '#wpformsfadebug' === window.location.hash ) {
				console.log( 'WPForms FA: '+msg );
			}
		}
	};
	if( $('.wpcf7-form').length ) {
		ZealCF7AFAbandoned.init();
		ZealCF7AFAbandoned.fillRecoverForm();
	}
})(jQuery);

(function($) {
	"use strict";

	document.addEventListener( 'wpcf7mailsent', function( event ) {
		var cf7af_url_params = new URLSearchParams(window.location.search);
		var cf7af_recover_id = cf7af_url_params.get('cf7af_recover') || cf7af_url_params.get('recover');
		var cf7af_recover_token = cf7af_url_params.get('cf7af_token');
		var cf7af_remove_data = {
			'action': 'cf7af_remove_abandoned',
			'cf7af_cf7_id': event.detail.contactFormId,
			'cf7af_recover_id': cf7af_recover_id,
			'cf7af_token': cf7af_recover_token,
			'cf7af_remove_nonce': cf7af_abandoned.cf7af_remove_nonce,
		}

		$.ajax({
			type: "POST",
			dataType: "json",
			url: cf7af_abandoned.ajaxurl,
			data: cf7af_remove_data,
			success: function(response) {
			}
		});

	}, false );

})(jQuery);
