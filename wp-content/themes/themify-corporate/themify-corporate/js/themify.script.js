;// Themify Theme Scripts - http://themify.me/

// Initialize object literals
var EntryFilter = {};

/////////////////////////////////////////////
// jQuery functions					
/////////////////////////////////////////////
(function($){

/////////////////////////////////////////////
// jQuery functions
/////////////////////////////////////////////
$.fn.fixedHeader = function(options){
	var defaults = {
			fixedClass: 'fixed-header'
		},
		settings = $.extend({}, defaults, options);

	return this.each(function(){
		var $this = $(this),
			$parent = $this.parent(),
			thisHeight = $this.height(),
			$window = $(window),
			$body = $('body'),
			resizeId;

		function onResize(){
			thisHeight = $this.height();
		}

		function onScroll(){
			var scrollTop = $window.scrollTop(),
				$headerwrap = $('#headerwrap');
			if(scrollTop > thisHeight) {
				$this.addClass(settings.fixedClass);
				$parent.css('padding-top', thisHeight);
				$body.addClass('fixed-header-on');
			} else {
				$this.removeClass(settings.fixedClass);
				$parent.css('padding-top', '');
				$body.removeClass('fixed-header-on');
			}
		};

		$window.on('scroll.fixedHeader touchstart.touchScroll touchmove.touchScroll', onScroll)
		.on('resize', function(){
			clearTimeout(resizeId);
			resizeId = setTimeout(onResize, 500);
		});
	});
};

// Initialize carousels //////////////////////////////
function createCarousel(obj) {
	obj.each(function() {
		var $this = $(this);
		$this.carouFredSel({
			responsive : true,
			prev : '#' + $this.data('id') + ' .carousel-prev',
			next : '#' + $this.data('id') + ' .carousel-next',
			pagination : {
				container : '#' + $this.data('id') + ' .carousel-pager'
			},
			circular : true,
			infinite : true,
			swipe: true,
			scroll : {
				items : 1,
				fx : $this.data('effect'),
				duration : parseInt($this.data('speed'))
			},
			auto : {
				play : !!('off' != $this.data('autoplay')),
				timeoutDuration : 'off' != $this.data('autoplay') ? parseInt($this.data('autoplay')) : 0
			},
			items : {
				visible : {
					min : 1,
					max : 1
				},
				width : 222
			},
			onCreate : function() {
				$this.closest('.slideshow-wrap').css({
					'visibility' : 'visible',
					'height' : 'auto'
				});
				var $testimonialSlider = $this.closest('.testimonial.slider');
				if( $testimonialSlider.length > 0 ) {
					$testimonialSlider.css({
						'visibility' : 'visible',
						'height' : 'auto'
					});
				}
				$(window).resize();
			}
		});
	});
}

// Test if touch event exists //////////////////////////////
function is_touch_device() {
	return 'true' == themifyScript.isTouch;
}

// Scroll to Element //////////////////////////////
function themeScrollTo(offset) {
	$('body,html').animate({ scrollTop: offset }, 800);
}

// Entry Filter /////////////////////////
EntryFilter = {
	filter: function(){
		var $filter = $('.post-filter');
		if ( $filter.find('a').length > 0 && 'undefined' !== typeof $.fn.isotope ){
			$filter.find('li').each(function(){
				var $li = $(this),
					$entries = $li.parent().next(),
					cat = $li.attr('class').replace( /(current-cat)|(cat-item)|(-)|(active)/g, '' ).replace( ' ', '' );
				if ( $entries.find('.portfolio-post.cat-' + cat).length <= 0 ) {
					$li.remove();
				}
			});

			$filter.show().on('click', 'a', function(e) {
				e.preventDefault();
				var $li = $(this).parent(),
					$entries = $li.parent().next();
				if ( $li.hasClass('active') ) {
					$li.removeClass('active');
					$entries.isotope( {
						filter: '.portfolio-post'
					} );
				} else {
					$li.siblings('.active').removeClass('active');
					$li.addClass('active');
					$entries.isotope( {
						filter: '.cat-' + $li.attr('class').replace( /(current-cat)|(cat-item)|(-)|(active)/g, '' ).replace( ' ', '' )  } );
				}
			} );
		}
	},
	layout: function(){
		var $entries = $('.loops-wrapper.portfolio'),
			layout = $entries.hasClass( 'list-post' ) ? 'vertical' : 'fitRows';
		$entries.isotope({
			layoutMode: layout,
			transformsEnabled: false,
			itemSelector : '.portfolio-post'
		});
	}
};

// DOCUMENT READY
$(document).ready(function() {

	var $body = $('body'), $window = $(window), $skills = $('.progress-bar');

	// Initialize color animation
	if ( 'undefined' !== typeof $.fn.animatedBG ) {
		$('.animated-bg:not(.themify_builder_row)').animatedBG({
			colorSet: themifyScript.colorAnimationSet.split(','),
			speed: parseInt( themifyScript.colorAnimationSpeed, 10 )
		});
	}

	// make portfolio overlay clickable
	$( 'body' ).on( 'click', '.loops-wrapper.grid4.portfolio .post-image + .post-content, .loops-wrapper.grid3.portfolio .post-image + .post-content, .loops-wrapper.grid2.portfolio .post-image + .post-content', function(e){
		if( $( e.target ).is( 'a' ) || $( e.target ).parent().is( 'a' ) ) return;
		var $link = $( this ).find( '.post-title a' );
		if( $link.length > 0 && ! $link.hasClass( 'lightbox' ) ) {
			window.location = $link.attr( 'href' );
		}
	});

	/////////////////////////////////////////////
	// Fixed header
	/////////////////////////////////////////////
	if('undefined' !== $.fn.fixedHeader && '' != themifyScript.fixedHeader){
		$('#headerwrap').fixedHeader();
	}

	/////////////////////////////////////////////
	// Scroll to row when a menu item is clicked.
	/////////////////////////////////////////////
	if ( 'undefined' !== typeof $.fn.themifyScrollHighlight ) {
		$body.themifyScrollHighlight();
	}

	/////////////////////////////////////////////
	// Entry Filter
	/////////////////////////////////////////////
	EntryFilter.filter();

	/////////////////////////////////////////////
	// Skillset Animation
	/////////////////////////////////////////////
	if( themifyScript.scrollingEffectOn ) {
		$skills.each(function(){
			var $self = $(this).find('span'),
				percent = $self.data('percent');

			if( typeof $.waypoints !== 'undefined' ) {
				$self.width(0);
				$self.waypoint(function(direction){
					$self.animate({width: percent}, 800,function(){
						$(this).addClass('animated');
					});
				}, {offset: '80%'});
			}
		});
	}

	/////////////////////////////////////////////
	// Scroll to top
	/////////////////////////////////////////////
	$('.back-top a').on('click', function(e){
		e.preventDefault();
		themeScrollTo(0);
	});

	/////////////////////////////////////////////
	// Toggle main nav on mobile
	/////////////////////////////////////////////
	if( typeof jQuery.fn.themifyDropdown == 'function' ) {
		$( '#main-nav' ).themifyDropdown();
	}

	$('#menu-icon').themifySideMenu({
		close: '#menu-icon-close'
	});

	/////////////////////////////////////////////
	// Add class "first" to first elements
	/////////////////////////////////////////////
	$('.highlight-post:odd').addClass('odd');

	/////////////////////////////////////////////
	// Lightbox / Fullscreen initialization
	/////////////////////////////////////////////
	if(typeof ThemifyGallery !== 'undefined') {
		ThemifyGallery.init({'context': $(themifyScript.lightboxContext)});
	}

});

// WINDOW LOAD
$(window).load(function() {

	/////////////////////////////////////////////
	// Carousel initialization
	/////////////////////////////////////////////
	if( typeof $.fn.carouFredSel !== 'undefined' ) {
		createCarousel($('.slideshow'));
	}

	/////////////////////////////////////////////
	// Entry Filter Layout
	/////////////////////////////////////////////
	EntryFilter.layout();

});
	
})(jQuery);