;var ThemifyBuilderModuleJs;
(function($, window, document, undefined) {

	'use strict';

	ThemifyBuilderModuleJs = {

		wow: null,

		fwvideos: [], // make it accessible to public

		init: function() {
			this.setupBodyClasses();
			this.bindEvents();
		},

		bindEvents: function() {
			var self = ThemifyBuilderModuleJs;

			$(document).ready(function(){

				self.wowInit();
				self.showcaseGallery();
				self.touchdropdown();
				self.accordion();
				self.tabs();
				self.rowCover();

				if ( 'undefined' !== typeof $.fn.themifyScrollHighlight ) {
					$('body').themifyScrollHighlight( ( themifyScript && themifyScript.scrollHighlight ) ? themifyScript.scrollHighlight : {} );
				}

				if(tbLocalScript.isTouch) return;
				// put code for mobile on
			});

			$(window).load(function(){
				self.carousel();
				self.tabsDeepLink();
				self.animatedBackground();
				self.charts();
				self.backgroundSlider();

				if ( tbLocalScript.isTouch ) {
					self.fullheight();
					return;
				}
				self.fullwidthVideo();
				if ( tbLocalScript.isParallaxActive ) {
					self.backgroundScrolling();
				}
			});

			$(window).bind( 'hashchange', function(){
				self.tabsDeepLink();
			} );
		},

		wowInit: function() {
			var self = this;
			if ( tbLocalScript.animationInviewSelectors.length == 0 ) return;

			self.animationOnScroll();
			self.wow = new WOW({
				live: false,
				offset : parseInt( tbLocalScript.animationOffset )
			});
			self.wow.init();

			$('body').on( 'builder_load_module_partial builder_toggle_frontend', function(){
				self.wow.doSync();
				self.wow.sync();
			});
		},

		loadOnAjax: function() {
			ThemifyBuilderModuleJs.touchdropdown();
			ThemifyBuilderModuleJs.tabs();
			ThemifyBuilderModuleJs.carousel(true);
			ThemifyBuilderModuleJs.charts();
			ThemifyBuilderModuleJs.animatedBackground();
		},

		rowCover : function(){
			$( 'body' ).on( 'mouseenter', '.themify_builder_row', function(){
				var cover = $( this ).find( '> .builder_row_cover' );
				if( cover.length == 0 ) return;
				var new_color = cover.data( 'hover-color' );
				if( new_color !== undefined ) {
					cover.css( 'opacity', 1 );
					cover.css( 'background-color', new_color );
				}
			} )
			.on( 'mouseleave', '.themify_builder_row', function(){
				var cover = $( this ).find( '> .builder_row_cover' );
				if( cover.length == 0 ) return;
				var new_color = cover.data( 'color' );
				if( new_color == undefined ) {
					cover.css( 'opacity', 0 );
				} else {
					cover.css( 'opacity', 1 );
					cover.css( 'background-color', new_color );
				}
			} );
		},

		fullheight: function() {
			// Set full-height rows to viewport height
			if ( navigator.userAgent.match(/(iPad)/g) ) {
				var didResize = false,
					selector = '.themify_builder_row.fullheight';
				$(window).resize(function() {
					didResize = true;
				});
				setInterval(function() {
					if ( didResize ) {
						didResize = false;
						$(selector).each(function(){
							$(this).css({
								'height': $(window).height()
							});
						});
					}
				}, 250);
			}
		},

		// Row: Background Slider
		backgroundSlider: function() {

			var $rowSliders = $('.row-slider');

			var themifySectionVars = {
				autoplay: tbLocalScript.backgroundSlider.autoplay,
				speed: tbLocalScript.backgroundSlider.speed,
			};

			// Parse injected vars
			themifySectionVars.autoplay = parseInt(themifySectionVars.autoplay, 10);
			if ( themifySectionVars.autoplay <= 10 ) {
				themifySectionVars.autoplay *= 1000;
			}
			themifySectionVars.speed = parseInt(themifySectionVars.speed, 10);

			if ( $rowSliders.length > 0 ) {

				// Initialize slider
				$rowSliders.each( function() {
					var $thisRowSlider = $(this),
						$backel = $thisRowSlider.closest('.themify_builder_row'),
						rsImages = [],
						bgMode = $thisRowSlider.data('bgmode');

					// Initialize images array with URLs
					$thisRowSlider.find('li').each(function(){
						rsImages.push( $(this).attr('data-bg') );
					});

					// Call backstretch for the first time
					$backel.backstretch(rsImages, {
						fade : themifySectionVars.speed,
						duration : themifySectionVars.autoplay,
						mode: bgMode
					});
					// Cache Backstretch object
					var thisBGS = $backel.data('backstretch');

					// Previous and Next arrows
					$thisRowSlider.find('.row-slider-prev').on('click', function(e){
						e.preventDefault();
						thisBGS.prev();
					});
					$thisRowSlider.find('.row-slider-next').on('click', function(e){
						e.preventDefault();
						thisBGS.next();
					});

					// Dots
					$thisRowSlider.find('.row-slider-dot').each(function(){
						var $dot = $(this);
						$dot.on('click', function(){
							thisBGS.show( $dot.data('index') );
						});
					});
				});
			}
		},

		// Row: Animated Color Background
		animatedBackground: function(){
			if ( 'undefined' !== typeof $.fn.animatedBG && 'object' === typeof themifyScript ) {
				if ( ( 'string' === typeof themifyScript.colorAnimationSet && 'string' === typeof themifyScript.colorAnimationSpeed ) && ( '' != themifyScript.colorAnimationSet && '' != themifyScript.colorAnimationSpeed ) ) {
					$('.themify_builder_row.animated-bg').animatedBG({
						colorSet: themifyScript.colorAnimationSet.split(','),
						speed: parseInt( themifyScript.colorAnimationSpeed, 10 )
					});
				}
			}
		},

		// Row: Fullwidth video background
		fullwidthVideo: function(){
			if ( typeof $.BigVideo !== 'undefined' && !ThemifyBuilderModuleJs._checkBrowser('opera') ) {
				var $videos = $('.themify_builder_row[data-fullwidthvideo]'),
					self = ThemifyBuilderModuleJs;
				$.each($videos, function (i, elm) {
					self.fwvideos[i] = new $.BigVideo({
						useFlashForFirefox: true,
						container: $(elm),
						doLoop : tbLocalScript.backgroundVideoLoop == 'yes',
						id: i,
						poster: tbLocalScript.videoPoster
					});
					self.fwvideos[i].init();
					self.fwvideos[i].show($(elm).data('fullwidthvideo'));
				});
			}
		},

		touchdropdown: function(){
			if( typeof jQuery.fn.themifyDropdown == 'function' ) {
				$( '.module-menu .nav' ).themifyDropdown();
			}
		},

		accordion: function() {
			$( 'body' ).on( 'click', '.accordion-title', function( e ){
				var $this = $(this),
					$panel = $this.next(),
					def = $this.closest( 'li' ).toggleClass( 'current' ).siblings().removeClass( 'current' );

				if( 'accordion' === $this.closest( '.module.module-accordion' ).data( 'behavior' ) ) {
					def.find('.accordion-content').slideUp();
				}
				$panel.slideToggle();
				$( 'body' ).trigger( 'tf_accordion_switch', [ $panel ] );
				e.preventDefault();
			} );
		},

		charts: function() {
			$('.module-feature .module-feature-chart').each(function(){
				$(this).waypoint(function(){
					var $self = $(this),
						barColor = $self.data('color'),
						percent = $self.data('percent');

					$self.easyPieChart( {
						'percent' : percent,
						'barColor' : barColor,
						'trackColor' : $self.data('trackcolor'),
						'scaleColor' : $self.data('scalecolor'),
						'scaleLength' : $self.data('scalelength'),
						'lineCap' : $self.data('linecap'),
						'rotate' : $self.data('rotate'),
						'size' : $self.data('size'),
						'lineWidth' : $self.data('linewidth'),
						'animate' : $self.data('animate')
					} );
				}, {
					offset: '100%',
					triggerOnce: true
				});
			});
		},

		showcaseGallery : function() {
			$( 'body' ).on( 'click', '.module.module-gallery.layout-showcase a', function(){
				$( this ).closest( '.gallery' ).find( '.gallery-showcase-image img' ).prop( 'src', $(this).data( 'image' ) );
				return false;
			});
		},

		tabs: function() {
			$(".module.module-tab").each(function(){
				var $height = $(".tab-nav", this).outerHeight();
				if($height > 200) {
					$(".tab-content", this).css('min-height', $height);
				}
			});
			$(".module.module-tab").tabify();
		},

		tabsDeepLink: function() {
			var hash = window.location.hash;
			if ( '' != hash && '#' != hash && $( hash + '.tab-content' ).length > 0 ) {
				var cons = 100,
					$moduleTab = $( hash ).closest( '.module-tab' );
				if ( $moduleTab.length > 0 ) {
					$( 'a[href=' + hash + ']' ).click();
					$( 'html, body' ).animate( { scrollTop: $moduleTab.offset().top - cons }, 1000 );
				}
			}
		},

		carousel: function(checkImageLoaded) {
			checkImageLoaded = checkImageLoaded || false;
			$('.themify_builder_slider').each(function(){
				var $this = $(this),
					img_length = $this.find('img').length,
					$args = {
					responsive: true,
					circular: true,
					infinite: true,
					items: {
						visible: { min: 1, max: $this.data('visible') },
						width: 150,
						height: 'variable'
					},
					onCreate: function( items ) {
						$('.themify_builder_slider_wrap').css({'visibility':'visible', 'height':'auto'});
						$this.trigger('updateSizes');
						$('.themify_builder_slider_loader').remove();
					}
				};

				if($this.closest('.themify_builder_slider_wrap').find('.caroufredsel_wrapper').length > 0) {
					return;
				}

				// fix the one slide problem
				if($this.children().length < 2) {
					$('.themify_builder_slider_wrap').css({'visibility':'visible', 'height':'auto'});
					$('.themify_builder_slider_loader').remove();
					$(window).resize();
					return;
				}

				// Auto
				if(parseInt($this.data('auto-scroll')) > 0) {
					$args.auto = {
						play: true,
						timeoutDuration: parseInt($this.data('auto-scroll') * 1000)
					};
				}
				else if($this.data('effect') !== 'continuously' && ( typeof $this.data('auto-scroll') !== 'undefined' || parseInt($this.data('auto-scroll')) == 0 )  ){
					$args.auto = false;
				}

				// Touch
				$args.swipe = true;

				// Scroll
				if($this.data('effect') == 'continuously'){
					if(typeof $args.auto !== 'undefined'){
						delete $args.auto;
					}
					var speed = $this.data('speed'), duration;
					if ( speed == .5 ) {
						duration = 0.10;
					} else if ( speed == 4 ) {
						duration = 0.04;
					} else {
						duration = 0.07;
					}
					$args.auto = { timeoutDuration: 0 };
					$args.align = false;
					$args.scroll = {
						delay: 1000,
						easing: 'linear',
						items: $this.data('scroll'),
						duration: duration,
						pauseOnHover: $this.data('pause-on-hover')
					};
				} else {
					$args.scroll = {
						items: $this.data('scroll'),
						pauseOnHover: $this.data('pause-on-hover'),
						duration: parseInt($this.data('speed') * 1000),
						fx: $this.data('effect')
					}
				}

				if($this.data('arrow') == 'yes') {
					$args.prev = '#' + $this.data('id') + ' .carousel-prev';
					$args.next = '#' + $this.data('id') + ' .carousel-next';
				}

				if($this.data('pagination') == 'yes') {
					$args.pagination = {
						container : '#' + $this.data('id') + ' .carousel-pager',
						items : $this.data( 'visible' )
					};
				}

				if( $this.data('wrap') == 'no' ) {
					$args.circular = false;
					$args.infinite = false;
				}

				if ( 'undefined' !== typeof $.fn.carouFredSel ) {
					if ( checkImageLoaded && img_length > 0 ) {
						$(this).find('img').themifyBuilderImagesLoaded(function(){
							$this.carouFredSel($args);
						});
					} else {
						$this.carouFredSel($args);
					}
				}

				$('.mejs-video').on('resize', function(e){
					e.stopPropagation();
				});

				var didResize = false, afterResize;
				$(window).resize(function() {
					didResize = true;
				});
				setInterval(function() {
					if ( didResize ) {
						didResize = false;
						clearTimeout(afterResize);
						afterResize = setTimeout(function(){
							$('.mejs-video').resize();
							$this.trigger('updateSizes');
						}, 100);
					}
				}, 250);

			});
		},

		request: 0,

		initialize: function(address, num, zoom, type, scroll, drag) {
			var delay = this.request++ * 500;
			setTimeout( function(){
				var geo = new google.maps.Geocoder(),
					latlng = new google.maps.LatLng(-34.397, 150.644),
					mapOptions = {
						'zoom': zoom,
						center: latlng,
						mapTypeId: google.maps.MapTypeId.ROADMAP,
						scrollwheel: scroll,
						draggable: drag
					};
				switch( type.toUpperCase() ) {
					case 'ROADMAP':
						mapOptions.mapTypeId = google.maps.MapTypeId.ROADMAP;
						break;
					case 'SATELLITE':
						mapOptions.mapTypeId = google.maps.MapTypeId.SATELLITE;
						break;
					case 'HYBRID':
						mapOptions.mapTypeId = google.maps.MapTypeId.HYBRID;
						break;
					case 'TERRAIN':
						mapOptions.mapTypeId = google.maps.MapTypeId.TERRAIN;
						break;
				}
				var node = document.getElementById( 'themify_map_canvas_' + num );
				var	map = new google.maps.Map( node, mapOptions ),
					revGeocoding = $( node ).data('reverse-geocoding') ? true: false;

				/* store a copy of the map object in the dom node, for future reference */
				$( node ).data( 'gmap_object', map );

				if ( revGeocoding ) {
					var latlngStr = address.split(',', 2),
						lat = parseFloat(latlngStr[0]),
						lng = parseFloat(latlngStr[1]),
						geolatlng = new google.maps.LatLng(lat, lng),
						geoParams = { 'latLng': geolatlng };
				} else {
					var geoParams = { 'address': address };
				}

				geo.geocode( geoParams, function(results, status) {
					if (status == google.maps.GeocoderStatus.OK) {
						var position = revGeocoding ? geolatlng : results[0].geometry.location;
						map.setCenter(position);
						var marker = new google.maps.Marker({
							map: map,
							position: position
						}),

						info = $('#themify_map_canvas_' + num).data('info-window');
						if( undefined !== info ) {
							var contentString = '<div class="themify_builder_map_info_window">'+ info +'</div>',

							infowindow = new google.maps.InfoWindow({
								content: contentString
							});

							google.maps.event.addListener( marker, 'click', function() {
								infowindow.open( map, marker );
							});
						}
					}
				});
			}, delay );
		},

		backgroundScrolling: function() {
			$('.builder-parallax-scrolling').each(function(){
				$(this).builderParallax('50%', 0.1);
			});
		},

		animationOnScroll: function() {
			var self = ThemifyBuilderModuleJs;
			if(!self.supportTransition()) return;

			$('body').addClass('animation-on')
			.on('builder_toggle_frontend', function(event, is_edit){
				self.doAnimation();
			});
			self.doAnimation();
		},

		doAnimation: function( resync ){
			resync = resync || false;
			// On scrolling animation
			var self = ThemifyBuilderModuleJs, selectors = tbLocalScript.animationInviewSelectors,
				newSelectors = tbLocalScript.animationNewSelectors,
				iterate = 0, animateEnd, $body = $('body'), $overflow = $('body');

			if(!ThemifyBuilderModuleJs.supportTransition()) return;

			if ( $body.find(selectors).length > 0 ) {
				if( !$overflow.hasClass('animation-running')){
					$overflow.addClass('animation-running');
				}
			} else {
				if( $overflow.hasClass('animation-running')){
					$overflow.removeClass('animation-running');
				}
			}

			// Global Animation
			if ( tbLocalScript.createAnimationSelectors.selectors ) {
				$.each(tbLocalScript.createAnimationSelectors.selectors, function(key, val){
					$(val).addClass(tbLocalScript.createAnimationSelectors.effect);
				});
			}
			
			// Specific Animation
			if ( tbLocalScript.createAnimationSelectors.specificSelectors ) {
				$.each(tbLocalScript.createAnimationSelectors.specificSelectors, function(selector, effect){
					$(selector).addClass(effect);
				});
			}

			// Core Builder Animation
			$.each(selectors, function(i,selector){
				$(selector).addClass('wow');
			});

			if ( resync ) 
				self.wow.doSync();
		},

		supportTransition: function() {
			var b = document.body || document.documentElement,
				s = b.style,
				p = 'transition';

			if (typeof s[p] == 'string') { return true; }

			// Tests for vendor specific prop
			var v = ['Moz', 'webkit', 'Webkit', 'Khtml', 'O', 'ms'];
			p = p.charAt(0).toUpperCase() + p.substr(1);

			for (var i=0; i<v.length; i++) {
				if (typeof s[v[i] + p] == 'string') { return true; }
			}
			return false;
		},

		setupBodyClasses: function(){
			var classes = [];
			if(tbLocalScript.isTouch) 
				classes.push( 'builder-is-touch' );

			$('body').addClass( classes.join(' ') );
		},

		_checkBrowser: function( browser ) {
			var isOpera = !!window.opera || navigator.userAgent.indexOf(' OPR/') >= 0;
			if ( 'opera' == browser ) {
				return isOpera;
			} else {
				// Add more browser detection here
				return false;
			}
		}
	};

	// Initialize
	ThemifyBuilderModuleJs.init();

}(jQuery, window, document));