/*	BigVideo by John Polacek @johnpolacek Licensed under MIT and GPL.
	Dependencies: jQuery, jQuery UI (Slider), Video.js, ImagesLoaded */
(function (factory) {
	'use strict';
	if (typeof define === 'function' && define.amd) {
		// Register as an anonymous AMD module:
		define([
			'jquery',
			'videojs',
			'imagesloaded',
			'jquery-ui'
		], factory);
	} else {
		factory(jQuery, videojs);
	}
})(function($, videojs) {

	$.BigVideo = function(options, element) {

		var defaults = {
			// If you want to use a single mp4 source, set as true
			useFlashForFirefox:true,
			// If you are doing a playlist, the video won't play the first time
			// on a touchscreen unless the play event is attached to a user click
			forceAutoplay:false,
			controls:false,
			doLoop:true,
			container: element ? $(element) : $('body'),
			shrinkable:false,
			poster: ''
		};

		var BigVideo = this,
			player,
			vidEl = '.big-video-vid',
			wrap = $('<div class="big-video-wrap"></div>'),
			video = $(''),
			mediaAspect = 16/9,
			vidDur = 0,
			defaultVolume = 0.8,
			isInitialized = false,
			isSeeking = false,
			isPlaying = false,
			isQueued = false,
			isAmbient = false,
			playlist = [],
			currMediaIndex,
			currMediaType;

		var settings = $.extend({}, defaults, options);
		var container = settings.container;
		var vid = container.find(vidEl);

		function updateSize() {
			var $body = $('body'),
				$containerBVWrap = container.find('.big-video-wrap'),
				windowW = $containerBVWrap.width(),
				windowH = $containerBVWrap.height(),
				windowAspect = windowW/windowH;

			if ( settings.container.is( $body ) ) {
				$( 'html,body' ).css( 'height', $( window ).height() > $body.css( 'height', 'auto' ).height() ? '100%' : 'auto' );
			}
			if (windowAspect < mediaAspect) {
				// taller
				if (currMediaType === 'video') {
					player
						.width(windowH*mediaAspect)
						.height(windowH);
					if (!settings.shrinkable) {
						vid
							.css('top',0)
							.css('left',-(windowH*mediaAspect-windowW)/2)
							.css('height',windowH);
					} else {
						vid
							.css('top',-(windowW/mediaAspect-windowH)/2)
							.css('left',0)
							.css('height',windowW/mediaAspect);
					}
					container.find(vidEl+'_html5_api')
						.css('width',windowH*mediaAspect)
						.css('height',windowH);
					container.find('object[id*=_flash_api]').parent()
						.css('width',$(window).height()*mediaAspect)
						.css('height',$(window).height());
					container.find('video').css('width', '');
				} else {
					// is image
					container.find('.big-video-image')
						.css({
							width: 'auto',
							height: windowH,
							top:0,
							left:-(windowH*mediaAspect-windowW)/2
						});
				}
			} else {
				// wider
				if (currMediaType === 'video') {
					player
						.width(windowW)
						.height(windowW/mediaAspect);
					vid
						.css('top',-(windowW/mediaAspect-windowH)/2)
						.css('left',0)
						.css('height',windowW/mediaAspect);
					container.find(vidEl+'_html5_api')
						.css('width',container.find(vidEl+'_html5_api').parent().width()+"px")
						.css('height','auto');
					container.find('object[id*=_flash_api]').parent()
						.css('width', $(window).width())
						.css('height',$(window).width()/mediaAspect);
					container.find('video').css('width', '100%');
				} else {
					// is image
					container.find('.big-video-image')
						.css({
							width: windowW,
							height: 'auto',
							top:-(windowW/mediaAspect-windowH)/2,
							left:0
						});
				}
			}
		}

		function initPlayControl() {
			// create video controller
			var markup = [
			'<div class="big-video-control-container">',
				'<div class="big-video-control">',
					'<a href="#" class="big-video-control-play"></a>',
					'<div class="big-video-control-middle">',
						'<div class="big-video-control-bar">',
							'<div class="big-video-control-bound-left"></div>',
							'<div class="big-video-control-progress"></div>',
							'<div class="big-video-control-track"></div>',
							'<div class="big-video-control-bound-right"></div>',
						'</div>',
					'</div>',
					'<div class="big-video-control-timer"></div>',
				'</div>',
			'</div>'
			].join('');
			container.append(markup);

			// hide until playVideo
			container.find('.big-video-control-container').css('display','none');
			container.find('.big-video-control-timer').css('display','none');

			// add events
			container.find('.big-video-control-track').slider({
				animate: true,
				step: 0.01,
				slide: function(e,ui) {
					isSeeking = true;
					container.find('.big-video-control-progress').css('width',(ui.value-0.16)+'%');
					player.currentTime((ui.value/100)*player.duration());
				},
				stop:function(e,ui) {
					isSeeking = false;
					player.currentTime((ui.value/100)*player.duration());
				}
			});
			container.find('.big-video-control-bar').click(function(e) {
				player.currentTime((e.offsetX/$(this).width())*player.duration());
			});
			container.find('.big-video-control-play').click(function(e) {
				e.preventDefault();
				playControl('toggle');
			});
			player.on('timeupdate', function() {
				if (!isSeeking && (player.currentTime()/player.duration())) {
					var currTime = player.currentTime();
					var minutes = Math.floor(currTime/60);
					var seconds = Math.floor(currTime) - (60*minutes);
					if (seconds < 10) seconds='0'+seconds;
					var progress = player.currentTime()/player.duration()*100;
					container.find('.big-video-control-track').slider('value',progress);
					container.find('.big-video-control-progress').css('width',(progress-0.16)+'%');
					container.find('.big-video-control-timer').text(minutes+':'+seconds+'/'+vidDur);
				}
			});
		}

		function playControl(a) {
			var action = a || 'toggle';
			if (action === 'toggle') action = isPlaying ? 'pause' : 'play';
			if (action === 'pause') {
				player.pause();
				container.find('.big-video-control-play').css('background-position','-16px');
				isPlaying = false;
			} else if (action === 'play') {
				player.play();
				container.find('.big-video-control-play').css('background-position','0');
				isPlaying = true;
			} else if (action === 'skip') {
				nextMedia();
			}
		}

		function setUpAutoPlay() {
			player.play();
			settings.container.off('click',setUpAutoPlay);
		}

		function nextMedia() {
			currMediaIndex++;
			if (currMediaIndex === playlist.length) currMediaIndex=0;
			playVideo(playlist[currMediaIndex]);
		}

		function playVideo(source) {
			// clear image
			vid.css('display','block');
			currMediaType = 'video';
			player.src(source);
			isPlaying = true;
			if (isAmbient) {
				container.find('.big-video-control-container').css('display','none');
				player.ready(function(){
					player.volume(0);
				});
				doLoop = true;
			} else {
				container.find('.big-video-control-container').css('display','block');
				player.ready(function(){
					player.volume(defaultVolume);
				});
				doLoop = true;
			}
			container.find('.big-video-image').css('display','none');
			vid.css('display','block');
		}

		function showPoster(source) {
			// remove old image
			container.find('.big-video-image').remove();

			// hide video
			player.pause();
			vid.css('display','none');
			container.find('.big-video-control-container').css('display','none');

			// show image
			currMediaType = 'image';
			var bgImage = $('<img class="big-video-image" src='+source+' />');
			wrap.append(bgImage);

			container.find('.big-video-image').imagesLoaded(function() {
				mediaAspect = container.find('.big-video-image').width() / container.find('.big-video-image').height();
				updateSize();
			});
		}

		BigVideo.init = function() {
			if (!isInitialized) {

				// create player
				settings.container.prepend(wrap);
				var autoPlayString = settings.forceAutoplay ? 'autoplay' : '';
				player = $('<video class="'+vidEl.substr(1)+'" class="video-js vjs-default-skin" preload="auto" data-setup="{}" '+autoPlayString+' webkit-playsinline poster="' + settings.poster + '"></video>');
				player.css('position','absolute');
				wrap.append(player);

				vid = container.find(vidEl);

				var videoTechOrder = ['html5','flash'];
				// If only using mp4s and on firefox, use flash fallback
				var ua = navigator.userAgent.toLowerCase();
				var isFirefox = ua.indexOf('firefox') != -1;
				if (settings.useFlashForFirefox && (isFirefox)) {
					videoTechOrder = ['flash', 'html5'];
				}

				player = videojs(vid[0], {
					controls:false,
					autoplay:true,
					preload:'auto',
					techOrder:videoTechOrder
				});

				// add controls
				if (settings.controls) initPlayControl();

				// set initial state
				updateSize();
				isInitialized = true;
				isPlaying = false;

				if (settings.forceAutoplay) {
					$('body').on('click', setUpAutoPlay);
				}

				container.find('object[id*=_flash_api]')
					.attr('scale','noborder')
					.attr('width','100%')
					.attr('height','100%');

				// set events
				$(window).on('resize.bigvideo', function() {
					updateSize();
				});

				player.on('loadedmetadata', function(data) {
					if (container.find('object[id*=_flash_api]').length) {
						// use flash callback to get mediaAspect ratio
						mediaAspect = 
							container.find('object[id*=_flash_api]')[0].vjs_getProperty('videoWidth') /
							container.find('object[id*=_flash_api]')[0].vjs_getProperty('videoHeight');
					} else {
						// use html5 player to get mediaAspect
						mediaAspect =
							container.find('video').prop('videoWidth') /
							container.find('video').prop('videoHeight');
					}
					updateSize();
					var dur = Math.round(player.duration());
					var durMinutes = Math.floor(dur/60);
					var durSeconds = dur - durMinutes*60;
					if (durSeconds < 10) durSeconds='0'+durSeconds;
					vidDur = durMinutes+':'+durSeconds;
				});

				player.on('ended', function() {
					if (settings.doLoop) {
						player.currentTime(0);
						player.play();
					}
					if (isQueued) {
						nextMedia();
					}
				});
			}
		};

		BigVideo.show = function(source,options) {
			if (options === undefined) options = {};
			if (!source && container.data('video') !== '') {
				source = container.data('video');
			}
			isAmbient = options.ambient === true;
			if (isAmbient || options.doLoop) settings.doLoop = true;
			if (typeof(source) === 'string') {
				var ext = source.substring(source.lastIndexOf('.')+1);
				if (ext === 'jpg' || ext === 'gif' || ext === 'png') {
					showPoster(source);
				} else {
					if (options.altSource && navigator.userAgent.toLowerCase().indexOf('firefox') > -1) {
						source = options.altSource;
					}
					playVideo(source);
					if (options.onShown) options.onShown();
					isQueued = false;
				}
			} else {
				playlist = source;
				currMediaIndex = 0;
				playVideo(playlist[currMediaIndex]);
				if (options.onShown) options.onShown();
				isQueued = true;
			}
		};

		// Expose Video.js player
		BigVideo.getPlayer = function() {
			return player;
		};

		// Remove/dispose the player
		BigVideo.remove = BigVideo.dispose = function() {
			isInitialized = false;

			wrap.remove();
			$(window).off('resize.bigvideo');

			if(player) {
				player.off('loadedmetadata');
				player.off('ended');
				player.dispose();
			}
		};

		// Expose BigVideoJS player actions play, pause, skip (if a playlist is available)
		// Example: BigVideo.triggerPlayer('skip')
		BigVideo.triggerPlayer = function(action){
			playControl(action);
		};

	};

	//jquery shim
	$.fn.bigvideo = function (options) {
		if (typeof options === 'string') {
			var args = Array.prototype.slice.call(arguments, 1);
			this.each(function () {
				var instance = $.data(this, 'bigvideo');
				if (!instance) {
					$.error('Cannot call methods on bigvideo prior to initialization; Attempted to call method "' + options + '"');
					return;
				}
				if (!$.isFunction(instance[options]) || options.charAt(0) === '_') {
					$.error('No such method "' + options + '" for bigvideo instance');
					return;
				}
				instance[options].apply(instance, args);
			});
		} else {
			this.each(function () {
				$.data(this, 'bigvideo', new $.BigVideo(options, this));
			});
		}
		return this;
	};

});