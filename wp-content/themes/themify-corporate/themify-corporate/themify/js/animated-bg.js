;(function($){

	'use strict';

	var pluginName = "animatedBG",
		defaults = {
			colorSet: ['#ef008c', '#00be59', '#654b9e', '#ff5432', '#00d8e6'],
			speed: 3000
		};
	function Plugin(element, options){
		this.element = element;
		this.settings = $.extend({}, defaults, options);
		this.init();
	}
	// Avoid Plugin.prototype conflicts
	$.extend(Plugin.prototype, {
		// Internal pointers for shorter reference
		colors: [], speed: 3000,
		// Internal color
		color: '',
		init: function(){
			// Save in internal variables
			this.colors = this.settings.colorSet;
			this.speed = this.settings.speed;
			// Set initial color
			this.shiftColor();
			$(this.element).css('backgroundColor', this.color);
			// Start animation
			this.animaColor();
		},
		// Set next color, remove it from list and append it at the end
		shiftColor: function(){
			this.color = this.colors.shift();
			this.colors.push(this.color);
		},
		// Recursive animation
		animaColor: function(){
			var self = this;
			self.shiftColor();
			$(this.element).animate({backgroundColor: this.color}, this.speed, function(){
				self.animaColor();
			});
		}
	});
	// Lightweight wrapper around the constructor to prevent multiple instantiations
	$.fn[ pluginName ] = function(options){
		this.each(function() {
			if(!$.data(this, "plugin_" + pluginName)){
				$.data(this, "plugin_" + pluginName, new Plugin(this, options));
			}
		});
		return this;
	};
})(jQuery);