/* globals wpcf7forms_abandoned */
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
			var data = {
				'action': 'wpcf7forms_abandoned',
				'page_url': window.location.href,
				'recover': wpcf7forms_abandoned.recover,
				forms: json
			}

			$.ajax({
			  type: "POST",
			  dataType: "json",
			  url: wpcf7forms_abandoned.ajaxurl,
			  data: data,
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
				} else if ( link.indexOf( wpcf7forms_abandoned.home_url ) === -1 ) { // Outbound
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
	}
})(jQuery);

(function($) {
	"use strict";

	document.addEventListener( 'wpcf7mailsent', function( event ) {

		var data = {
			'action': 'remove_abandoned',
			'cf7_id':  event.detail.contactFormId  ,
		}

		$.ajax({
			type: "POST",
			dataType: "json",
			url: wpcf7forms_abandoned.ajaxurl,
			data: data,
			success: function(response) {
			}
		});

	}, false );

})(jQuery);