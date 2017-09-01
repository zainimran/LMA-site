/**
 * Tabify
 */
;(function ($) {

	'use strict';

	$.fn.tabify = function () {
		return this.each(function () {
			var tabs = $(this);
			$('ul.tab-nav li:first', tabs).addClass('current');
			$('div:first', tabs).show();
			var tabLinks = $('ul.tab-nav li', tabs);
			$(tabLinks).click(function () {
				$(this).addClass('current').siblings().removeClass('current');
				$('.tab-content', tabs).hide();
				var activeTab = $(this).find('a').attr('href');
				$(activeTab).show();
				$( 'body' ).trigger( 'tf_tabs_switch', [ activeTab, tabs ] );
				if ( $(activeTab).find('.shortcode.map').length > 0 ) {
					$(activeTab).find('.shortcode.map').each(function(){
						var mapInit = $(this).find('.map-container').data('map'),
							center = mapInit.getCenter();
						google.maps.event.trigger(mapInit, 'resize');
						mapInit.setCenter(center);
					});
				}
				return false;
			});
			$('.tab-content', tabs).find('a[href^="#tab-"]').on('click', function(event){
				event.preventDefault();
				var dest = $(this).prop('hash').replace('#tab-', ''),
					contentID = $('.tab-content', tabs).eq( dest - 1 ).prop('id');
				if ( $('a[href^="#'+ contentID +'"]').length > 0 ) {
					$('a[href^="#'+ contentID +'"]').trigger('click');
				}
			});
		});
	};

	// $('img.photo',this).themifyBuilderImagesLoaded(myFunction)
	// execute a callback when all images have loaded.
	// needed because .load() doesn't work on cached images
	$.fn.themifyBuilderImagesLoaded = function(callback){
	  var elems = this.filter('img'),
		  len   = elems.length,
		  blank = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==";
		  
	  elems.bind('load.imgloaded',function(){
		  if (--len <= 0 && this.src !== blank){ 
			elems.unbind('load.imgloaded');
			callback.call(elems,this); 
		  }
	  }).each(function(){
		 // cached images don't fire load sometimes, so we reset src.
		 if (this.complete || this.complete === undefined){
			var src = this.src;
			// webkit hack from http://groups.google.com/group/jquery-dev/browse_thread/thread/eee6ab7b2da50e1f
			// data uri bypasses webkit log warning (thx doug jones)
			this.src = blank;
			this.src = src;
		 }  
	  }); 
	 
	  return this;
	};
})(jQuery);

/*
 * Parallax Scrolling Builder
 */
(function( $ ){

	'use strict';

	var $window = $(window);
	var windowHeight = $window.height();

	$window.resize(function () {
		windowHeight = $window.height();
	});

	$.fn.builderParallax = function(xpos, speedFactor, outerHeight) {
		var $this = $(this);
		var getHeight;
		var firstTop;
		var paddingTop = 0, resizeId;
		
		//get the starting position of each element to have parallax applied to it		
		$this.each(function(){
			firstTop = $this.offset().top;
		});
		$window.resize(function(){
			clearTimeout(resizeId);
			resizeId = setTimeout(function(){
				$this.each(function(){
					firstTop = $this.offset().top;
				});
			}, 500);
		});

		if (outerHeight) {
			getHeight = function(jqo) {
				return jqo.outerHeight(true);
			};
		} else {
			getHeight = function(jqo) {
				return jqo.height();
			};
		}
			
		// setup defaults if arguments aren't specified
		if (arguments.length < 1 || xpos === null) xpos = "50%";
		if (arguments.length < 2 || speedFactor === null) speedFactor = 0.1;
		if (arguments.length < 3 || outerHeight === null) outerHeight = true;
		
		// function to be called whenever the window is scrolled or resized
		function update(){
			var pos = $window.scrollTop();				

			$this.each(function(){
				var $element = $(this);
				var top = $element.offset().top;
				var height = getHeight($element);

				// Check if totally above or totally below viewport
				if (top + height < pos || top > pos + windowHeight) {
					return;
				}

				$this.css('backgroundPosition', xpos + " " + Math.round((firstTop - pos) * speedFactor) + "px");
			});
		}		

		$window.bind('scroll', update).resize(update);
		update();
	};
})(jQuery);

/*! WOW - v1.0.2 - 2014-10-28
* Copyright (c) 2014 Matthieu Aussaguel; Licensed MIT */
(function() {
  var MutationObserver, Util, WeakMap, getComputedStyle, getComputedStyleRX,
	__bind = function(fn, me){ return function(){ return fn.apply(me, arguments); }; },
	__indexOf = [].indexOf || function(item) { for (var i = 0, l = this.length; i < l; i++) { if (i in this && this[i] === item) return i; } return -1; };

  Util = (function() {
	function Util() {}

	Util.prototype.extend = function(custom, defaults) {
	  var key, value;
	  for (key in defaults) {
		value = defaults[key];
		if (custom[key] == null) {
		  custom[key] = value;
		}
	  }
	  return custom;
	};

	Util.prototype.isMobile = function(agent) {
	  return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(agent);
	};

	Util.prototype.addEvent = function(elem, event, fn) {
	  if (elem.addEventListener != null) {
		return elem.addEventListener(event, fn, false);
	  } else if (elem.attachEvent != null) {
		return elem.attachEvent("on" + event, fn);
	  } else {
		return elem[event] = fn;
	  }
	};

	Util.prototype.removeEvent = function(elem, event, fn) {
	  if (elem.removeEventListener != null) {
		return elem.removeEventListener(event, fn, false);
	  } else if (elem.detachEvent != null) {
		return elem.detachEvent("on" + event, fn);
	  } else {
		return delete elem[event];
	  }
	};

	Util.prototype.innerHeight = function() {
	  if ('innerHeight' in window) {
		return window.innerHeight;
	  } else {
		return document.documentElement.clientHeight;
	  }
	};

	return Util;

  })();

  WeakMap = this.WeakMap || this.MozWeakMap || (WeakMap = (function() {
	function WeakMap() {
	  this.keys = [];
	  this.values = [];
	}

	WeakMap.prototype.get = function(key) {
	  var i, item, _i, _len, _ref;
	  _ref = this.keys;
	  for (i = _i = 0, _len = _ref.length; _i < _len; i = ++_i) {
		item = _ref[i];
		if (item === key) {
		  return this.values[i];
		}
	  }
	};

	WeakMap.prototype.set = function(key, value) {
	  var i, item, _i, _len, _ref;
	  _ref = this.keys;
	  for (i = _i = 0, _len = _ref.length; _i < _len; i = ++_i) {
		item = _ref[i];
		if (item === key) {
		  this.values[i] = value;
		  return;
		}
	  }
	  this.keys.push(key);
	  return this.values.push(value);
	};

	return WeakMap;

  })());

  MutationObserver = this.MutationObserver || this.WebkitMutationObserver || this.MozMutationObserver || (MutationObserver = (function() {
	function MutationObserver() {
	  if (typeof console !== "undefined" && console !== null) {
		console.warn('MutationObserver is not supported by your browser.');
	  }
	  if (typeof console !== "undefined" && console !== null) {
		console.warn('WOW.js cannot detect dom mutations, please call .sync() after loading new content.');
	  }
	}

	MutationObserver.notSupported = true;

	MutationObserver.prototype.observe = function() {};

	return MutationObserver;

  })());

  getComputedStyle = this.getComputedStyle || function(el, pseudo) {
	this.getPropertyValue = function(prop) {
	  var _ref;
	  if (prop === 'float') {
		prop = 'styleFloat';
	  }
	  if (getComputedStyleRX.test(prop)) {
		prop.replace(getComputedStyleRX, function(_, char) {
		  return char.toUpperCase();
		});
	  }
	  return ((_ref = el.currentStyle) != null ? _ref[prop] : void 0) || null;
	};
	return this;
  };

  getComputedStyleRX = /(\-([a-z]){1})/g;

  this.WOW = (function() {
	WOW.prototype.defaults = {
	  boxClass: 'wow',
	  animateClass: 'animated',
	  offset: 0,
	  mobile: true,
	  live: true
	};

	function WOW(options) {
	  if (options == null) {
		options = {};
	  }
	  this.scrollCallback = __bind(this.scrollCallback, this);
	  this.scrollHandler = __bind(this.scrollHandler, this);
	  this.start = __bind(this.start, this);
	  this.scrolled = true;
	  this.config = this.util().extend(options, this.defaults);
	  this.animationNameCache = new WeakMap();
	}

	WOW.prototype.init = function() {
	  var _ref;
	  this.element = window.document.documentElement;
	  if ((_ref = document.readyState) === "interactive" || _ref === "complete") {
		this.start();
	  } else {
		this.util().addEvent(document, 'DOMContentLoaded', this.start);
	  }
	  return this.finished = [];
	};

	WOW.prototype.start = function() {
	  var box, _i, _len, _ref;
	  this.stopped = false;
	  this.boxes = (function() {
		var _i, _len, _ref, _results;
		_ref = this.element.querySelectorAll("." + this.config.boxClass);
		_results = [];
		for (_i = 0, _len = _ref.length; _i < _len; _i++) {
		  box = _ref[_i];
		  _results.push(box);
		}
		return _results;
	  }).call(this);
	  this.all = (function() {
		var _i, _len, _ref, _results;
		_ref = this.boxes;
		_results = [];
		for (_i = 0, _len = _ref.length; _i < _len; _i++) {
		  box = _ref[_i];
		  _results.push(box);
		}
		return _results;
	  }).call(this);
	  if (this.boxes.length) {
		if (this.disabled()) {
		  this.resetStyle();
		} else {
		  _ref = this.boxes;
		  for (_i = 0, _len = _ref.length; _i < _len; _i++) {
			box = _ref[_i];
			this.applyStyle(box, true);
		  }
		}
	  }
	  if (!this.disabled()) {
		this.util().addEvent(window, 'scroll', this.scrollHandler);
		this.util().addEvent(window, 'resize', this.scrollHandler);
		this.interval = setInterval(this.scrollCallback, 50);
	  }
	  if (this.config.live) {
		return new MutationObserver((function(_this) {
		  return function(records) {
			var node, record, _j, _len1, _results;
			_results = [];
			for (_j = 0, _len1 = records.length; _j < _len1; _j++) {
			  record = records[_j];
			  _results.push((function() {
				var _k, _len2, _ref1, _results1;
				_ref1 = record.addedNodes || [];
				_results1 = [];
				for (_k = 0, _len2 = _ref1.length; _k < _len2; _k++) {
				  node = _ref1[_k];
				  _results1.push(this.doSync(node));
				}
				return _results1;
			  }).call(_this));
			}
			return _results;
		  };
		})(this)).observe(document.body, {
		  childList: true,
		  subtree: true
		});
	  }
	};

	WOW.prototype.stop = function() {
	  this.stopped = true;
	  this.util().removeEvent(window, 'scroll', this.scrollHandler);
	  this.util().removeEvent(window, 'resize', this.scrollHandler);
	  if (this.interval != null) {
		return clearInterval(this.interval);
	  }
	};

	WOW.prototype.sync = function(element) {
	  if (MutationObserver.notSupported) {
		return this.doSync(this.element);
	  }
	};

	WOW.prototype.doSync = function(element) {
	  var box, _i, _len, _ref, _results;
	  if (element == null) {
		element = this.element;
	  }
	  if (element.nodeType !== 1) {
		return;
	  }
	  element = element.parentNode || element;
	  _ref = element.querySelectorAll("." + this.config.boxClass);
	  _results = [];
	  for (_i = 0, _len = _ref.length; _i < _len; _i++) {
		box = _ref[_i];
		if (__indexOf.call(this.all, box) < 0) {
		  this.boxes.push(box);
		  this.all.push(box);
		  if (this.stopped || this.disabled()) {
			this.resetStyle();
		  } else {
			this.applyStyle(box, true);
		  }
		  _results.push(this.scrolled = true);
		} else {
		  _results.push(void 0);
		}
	  }
	  return _results;
	};

	WOW.prototype.show = function(box) {
	  this.applyStyle(box);
	  return box.className = "" + box.className + " " + this.config.animateClass;
	};

	WOW.prototype.applyStyle = function(box, hidden) {
	  var delay, duration, iteration;
	  duration = box.getAttribute('data-wow-duration');
	  delay = box.getAttribute('data-wow-delay');
	  iteration = box.getAttribute('data-wow-iteration');
	  return this.animate((function(_this) {
		return function() {
		  return _this.customStyle(box, hidden, duration, delay, iteration);
		};
	  })(this));
	};

	WOW.prototype.animate = (function() {
	  if ('requestAnimationFrame' in window) {
		return function(callback) {
		  return window.requestAnimationFrame(callback);
		};
	  } else {
		return function(callback) {
		  return callback();
		};
	  }
	})();

	WOW.prototype.resetStyle = function() {
	  var box, _i, _len, _ref, _results;
	  _ref = this.boxes;
	  _results = [];
	  for (_i = 0, _len = _ref.length; _i < _len; _i++) {
		box = _ref[_i];
		_results.push(box.style.visibility = 'visible');
	  }
	  return _results;
	};

	WOW.prototype.customStyle = function(box, hidden, duration, delay, iteration) {
	  if (hidden) {
		this.cacheAnimationName(box);
	  }
	  box.style.visibility = hidden ? 'hidden' : 'visible';
	  if (duration) {
		this.vendorSet(box.style, {
		  animationDuration: duration
		});
	  }
	  if (delay) {
		this.vendorSet(box.style, {
		  animationDelay: delay
		});
	  }
	  if (iteration) {
		this.vendorSet(box.style, {
		  animationIterationCount: iteration
		});
	  }
	  this.vendorSet(box.style, {
		animationName: hidden ? 'none' : this.cachedAnimationName(box)
	  });
	  return box;
	};

	WOW.prototype.vendors = ["moz", "webkit"];

	WOW.prototype.vendorSet = function(elem, properties) {
	  var name, value, vendor, _results;
	  _results = [];
	  for (name in properties) {
		value = properties[name];
		elem["" + name] = value;
		_results.push((function() {
		  var _i, _len, _ref, _results1;
		  _ref = this.vendors;
		  _results1 = [];
		  for (_i = 0, _len = _ref.length; _i < _len; _i++) {
			vendor = _ref[_i];
			_results1.push(elem["" + vendor + (name.charAt(0).toUpperCase()) + (name.substr(1))] = value);
		  }
		  return _results1;
		}).call(this));
	  }
	  return _results;
	};

	WOW.prototype.vendorCSS = function(elem, property) {
	  var result, style, vendor, _i, _len, _ref;
	  style = getComputedStyle(elem);
	  result = style.getPropertyCSSValue(property);
	  _ref = this.vendors;
	  for (_i = 0, _len = _ref.length; _i < _len; _i++) {
		vendor = _ref[_i];
		result = result || style.getPropertyCSSValue("-" + vendor + "-" + property);
	  }
	  return result;
	};

	WOW.prototype.animationName = function(box) {
	  var animationName;
	  try {
		animationName = this.vendorCSS(box, 'animation-name').cssText;
	  } catch (_error) {
		animationName = getComputedStyle(box).getPropertyValue('animation-name');
	  }
	  if (animationName === 'none') {
		return '';
	  } else {
		return animationName;
	  }
	};

	WOW.prototype.cacheAnimationName = function(box) {
	  return this.animationNameCache.set(box, this.animationName(box));
	};

	WOW.prototype.cachedAnimationName = function(box) {
	  return this.animationNameCache.get(box);
	};

	WOW.prototype.scrollHandler = function() {
	  return this.scrolled = true;
	};

	WOW.prototype.scrollCallback = function() {
	  var box;
	  if (this.scrolled) {
		this.scrolled = false;
		this.boxes = (function() {
		  var _i, _len, _ref, _results;
		  _ref = this.boxes;
		  _results = [];
		  for (_i = 0, _len = _ref.length; _i < _len; _i++) {
			box = _ref[_i];
			if (!(box)) {
			  continue;
			}
			if (this.isVisible(box)) {
			  this.show(box);
			  continue;
			}
			_results.push(box);
		  }
		  return _results;
		}).call(this);
		if (!(this.boxes.length || this.config.live)) {
		  return this.stop();
		}
	  }
	};

	WOW.prototype.offsetTop = function(element) {
	  var top;
	  while (element.offsetTop === void 0) {
		element = element.parentNode;
	  }
	  top = element.offsetTop;
	  while (element = element.offsetParent) {
		top += element.offsetTop;
	  }
	  return top;
	};

	WOW.prototype.isVisible = function(box) {
	  var bottom, offset, top, viewBottom, viewTop;
	  offset = box.getAttribute('data-wow-offset') || this.config.offset;
	  viewTop = window.pageYOffset;
	  viewBottom = viewTop + Math.min(this.element.clientHeight, this.util().innerHeight()) - offset;
	  top = this.offsetTop(box);
	  bottom = top + box.clientHeight;
	  return top <= viewBottom && bottom >= viewTop;
	};

	WOW.prototype.util = function() {
	  return this._util != null ? this._util : this._util = new Util();
	};

	WOW.prototype.disabled = function() {
	  return !this.config.mobile && this.util().isMobile(navigator.userAgent);
	};

	return WOW;

  })();

}).call(this);

/* Backstretch 2.0.3 2012-11-30 http://srobbin.com/jquery-plugins/backstretch/ (c) Scott Robbin Licensed MIT */
;(function(f,b,g){f.fn.backstretch=function(h,e){return(h===g||h.length===0)&&f.error("No images were supplied for Backstretch"),f(b).scrollTop()===0&&b.scrollTo(0,0),this.each(function(){var i=f(this),j=i.data("backstretch");j&&(e=f.extend(j.options,e),j.destroy(!0)),j=new a(this,h,e),i.data("backstretch",j)})},f.backstretch=function(e,h){return f("body").backstretch(e,h).data("backstretch")},f.expr[":"].backstretch=function(e){return f(e).data("backstretch")!==g},f.fn.backstretch.defaults={centeredX:!0,centeredY:!0,duration:5000,fade:0/*!Themify*/,mode:'fullcover'/*!Themify*/ };var d={wrap:{left:0,top:0,overflow:"hidden",margin:0,padding:0,height:"100%",width:"100%",zIndex:-999999},img:{position:"absolute",display:"none",margin:0,padding:0,border:"none",width:"auto",height:"auto",maxWidth:"none",zIndex:-999999}},a=function(l,j,k){this.options=f.extend({},f.fn.backstretch.defaults,k||{}),this.images=f.isArray(j)?j:[j],f.each(this.images,function(){f("<img />")[0].src=this}),this.isBody=l===document.body,this.$container=f(l),this.$wrap=f('<div class="backstretch"></div>').css(d.wrap).appendTo(this.$container),this.$root=this.isBody?c?f(b):f(document):this.$container;if(!this.isBody){var h=this.$container.css("position"),e=this.$container.css("zIndex");this.$container.css({position:h==="static"?"relative":h,zIndex:e==="auto"?0:e,background:"none"}),this.$wrap.css({zIndex:-999998})}this.$wrap.css({position:this.isBody&&c?"fixed":"absolute"}),this.index=0,this.show(this.index),f(b).on("resize.backstretch",f.proxy(this.resize,this)).on("orientationchange.backstretch",f.proxy(function(){this.isBody&&b.pageYOffset===0&&(b.scrollTo(0,1),this.resize())},this))};a.prototype={resize:function(){try{var m={left:0,top:0},q=this.isBody?this.$root.width():this.$root.innerWidth(),l=q,j=this.isBody?b.innerHeight?b.innerHeight:this.$root.height():this.$root.innerHeight(),k=l/this.$img.data("ratio"),p;k>=j?(p=(k-j)/2,this.options.centeredY&&(m.top="-"+p+"px")):(k=j,l=k*this.$img.data("ratio"),p=(l-q)/2,this.options.centeredX&&(m.left="-"+p+"px")),this.$wrap.css({width:q,height:j}).find("img:not(.deleteable)").css({width:l,height:k}).css(m);
/*!Begin Themify*/
;if("best-fit"==k.options.mode){if(this.$img.width()<this.$img.height()){if(this.$img.closest('.row_inner').width()<f(window).height()){if(this.$img.closest('.row_inner').width()<this.$img.width()){this.$img.removeClass("best-fit-vertical").addClass("best-fit-horizontal")}}else{this.$img.removeClass("best-fit-horizontal").addClass("best-fit-vertical")}}}
/*!End Themify*/
}catch(h){}return this},show:function(h){if(Math.abs(h)>this.images.length-1){return}this.index=h;var k=this,e=k.$wrap.find("img").addClass("deleteable"),j=f.Event("backstretch.show",{relatedTarget:k.$container[0]});return clearInterval(k.interval),k.$img=f("<img />").css(d.img).bind("load",function(i){var l=this.width||f(i.target).width(),m=this.height||f(i.target).height();
/*!Begin Themify*/
;if("best-fit"==k.options.mode){if(this.width<this.height){f(this).removeClass('best-fit-horizontal').addClass("best-fit best-fit-vertical")}else{f(this).removeClass('best-fit-vertical').addClass("best-fit best-fit-horizontal")}}
/*!End Themify*/
;f(this).data("ratio",l/m),f(this).fadeIn(k.options.speed||k.options.fade,function(){e.remove(),k.paused||k.cycle(),k.$container.trigger(j,k)}),k.resize()}).appendTo(k.$wrap),k.$img.attr("src",k.images[h]),k},next:function(){return this.show(this.index<this.images.length-1?this.index+1:0)},prev:function(){return this.show(this.index===0?this.images.length-1:this.index-1)},pause:function(){return this.paused=!0,this},resume:function(){return this.paused=!1,this.next(),this},cycle:function(){return this.images.length>1&&(clearInterval(this.interval),this.interval=setInterval(f.proxy(function(){this.paused||this.next()},this),this.options.duration)),this},destroy:function(e){f(b).off("resize.backstretch orientationchange.backstretch"),clearInterval(this.interval),e||this.$wrap.remove(),this.$container.removeData("backstretch")}};var c=function(){var t=navigator.userAgent,k=navigator.platform,h=t.match(/AppleWebKit\/([0-9]+)/),p=!!h&&h[1],x=t.match(/Fennec\/([0-9]+)/),j=!!x&&x[1],w=t.match(/Opera Mobi\/([0-9]+)/),v=!!w&&w[1],q=t.match(/MSIE ([0-9]+)/),m=!!q&&q[1];return !((k.indexOf("iPhone")>-1||k.indexOf("iPad")>-1||k.indexOf("iPod")>-1)&&p&&p<534||b.operamini&&{}.toString.call(b.operamini)==="[object OperaMini]"||w&&v<7458||t.indexOf("Android")>-1&&p&&p<533||j&&j<6||"palmGetResource" in b&&p&&p<534||t.indexOf("MeeGo")>-1&&t.indexOf("NokiaBrowser/8.5.0")>-1||m&&m<=6)}()})(jQuery,window);