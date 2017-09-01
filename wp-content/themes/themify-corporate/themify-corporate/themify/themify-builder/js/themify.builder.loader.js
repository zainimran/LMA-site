/*! Themify Builder - Asynchronous Script and Styles Loader */
var tbLoaderVars;

(function($, window, document, undefined){

	'use strict';

	$(document).ready(function(){
		var $tfBuilderAFirst = $('.toggle_tf_builder a:first');

		// #wp-link-backdrop, #wp-link-wrap
		$('#wp--wrap').remove();

		$tfBuilderAFirst.on('click', function(e){

			e.preventDefault();

			// Change text to indicate it's loading
			$('.themify_builder_front_icon').parent().append($(tbLoaderVars.progress));

			// Fire the ajax request.
			var jqxhr = $.post( tbLoaderVars.ajaxurl, {
				action: 'themify_builder_loader',
				scripts: tbLoaderVars.assets.scripts,
				styles: tbLoaderVars.assets.styles
			});

			// Allow refreshes to occur again if an error is triggered.
			jqxhr.fail( function() {
				window.console && console.log( 'AJAX failed' );
			});

			// Success handler
			jqxhr.done( function( response ) {

				response = $.parseJSON( response );

				if ( ! response ) {
					return;
				}

				// Count styles and scripts
				var countStyles = 0, countScripts = 0;

				// Load styles
				if ( response.styles ) {
					countStyles = response.styles.length - 1;
					$( response.styles ).each( function() {
						// Add stylesheet handle to list of those already parsed
						tbLoaderVars.assets.styles.push( this.handle );

						// Build link tag
						var style = document.createElement('link');
						style.rel = 'stylesheet';
						style.href = this.src;
						style.id = this.handle + '-css';
						style.async = false;
						style.onload = function(){
							if ( 0 === countStyles ) {
								// Event replaces $(document).ready() and $(window).load()
								// Functions hooked to those events must be hooked to this instead
								$('body').trigger('builderstylesloaded.themify');
							}
							countStyles--;
						};
						style.onerror = function() {
							countStyles--;
						};

						// Append link tag if necessary
						if ( style ) {
							document.getElementsByTagName('head')[0].appendChild(style);
						}
					} );
				}

				// Load scripts
				if ( response.scripts ) {
					countScripts = response.scripts.length - 1;

					$( response.scripts ).each( function() {
						var elementToAppendTo = this.footer ? 'body' : 'head';

						// Add script handle to list of those already parsed
						tbLoaderVars.assets.scripts.push( this.handle );

						// Output extra data, if present
						if ( this.jsVars ) {
							var data = document.createElement('script'),
								dataContent = document.createTextNode( "//<![CDATA[ \n" + this.jsVars + "\n//]]>" );

							data.type = 'text/javascript';
							data.appendChild( dataContent );

							document.getElementsByTagName( elementToAppendTo )[0].appendChild(data);
						}

						// Build script object
						var script = document.createElement('script');
						script.type = 'text/javascript';
						script.src = this.src;
						script.id = this.handle;
						script.async = false;
						script.onload = function(){
							if ( 0 === countScripts ) {
								// Write themifyBuilder.post_ID
								if ( themifyBuilder ) {
									themifyBuilder.post_ID = tbLoaderVars.post_ID;
								}

								// Remove click event
								$tfBuilderAFirst.off('click');

								// Initialize Builder
								// Event replaces $(document).ready() and $(window).load()
								// Functions hooked to those events must be hooked to this instead
								$('body').trigger('builderscriptsloaded.themify');
							}
							countScripts--;
						};
						script.onerror = function() {
							countScripts--;
						};

						// Append script to DOM in requested location
						document.getElementsByTagName( elementToAppendTo )[0].appendChild(script);

					} );
				}

			});

		});

		// Grab hash url #builder_active then activate frontend edit
		if( window.location.hash === "#builder_active" ) {
			$tfBuilderAFirst.trigger('click');
		}

	});

})(jQuery, window, document);