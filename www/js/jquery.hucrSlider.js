/**
* jQuery Slider Plugin
*
* http://www.hucr.cz/
*
* Copyright (c) 2012 Lukáš Záplata
*/

(function($) {
	$.fn.hucrSlider = function(options) {
		var defaults = {
				autoScroll: false,
				scrollTime: 5,
				scrollType: 'scroll',
				hoverStop: false,
				scrollNumbers: false,
				scrollThumbs: false
		}
		
		var options = $.extend(defaults, options);
		
		var slider = this.children("ul");
		var right = this.children("div.right");
		var left = this.children("div.left");
		var widthItem = slider.children("li:first").width();
		var width = 0;
		var child;
		var interval;
		var numbers = this.children("div.numbers");
		
		if (!options.scrollNumbers && !options.scrollThumbs) {
			numbers.remove();
		}
		else {
			if (!options.scrollNumbers) {	
				numbers.children("span").each(function () {
					$(this).children("a").remove();
				});
			}
			
			if (!options.scrollThumbs) {	
				numbers.children("span").each(function () {
					$(this).children("img").remove();
				});
			}
		}
		
		this.css("overflow", "hidden");
		
		$(window).load(function (){
			slider.children("li").each(function() {
				$(this).addClass("f-left");
				width += $(this).width();
				
				if (options.scrollType == 'fade') {
					$(this).css("position", "absolute").hide();
				}
			});
			
			slider.children("li:first").show();
			slider.addClass("f-left").css("width", width+"px");
		});
		
		createInterval();
		
		$(window).blur(function() {
			clearInterval(interval);
		});
		
		$(window).focus(function() {
			createInterval();
		});
		
		if (options.hoverStop) {
			this.hover(
				function () {
					clearInterval(interval);
				},
				function () {
					createInterval();
				}
			)
		}
		
		right.click(function() {
			clearInterval(interval);
			scrollRight(false, false);
			createInterval();
		});
		
		left.click(function() {	
			clearInterval(interval);	
			scrollLeft();
			createInterval();
		});
		
		if (options.scrollNumbers || options.scrollThumbs) {
			numbers.children().click(function () {
				var number = $(this).index() * 1 + 1;
				var position = slider.children("li#"+number).index();
				
				if (position > 0) {
		 			width = widthItem * position;
		 								
					clearInterval(interval);	
					scrollRight(width, position);
					createInterval();
				}
			});
		}
		
		function createInterval () {
			clearInterval(interval);
			if (options.autoScroll) {
				if (options.scrollType == 'scroll') {
					interval = setInterval(function () {scrollRight(false, false)}, options.scrollTime * 1000);
				}
				else {
					interval = setInterval(function () {fade()}, options.scrollTime * 1000);
				}
			}
		}
		
		function scrollRight (width, position) {
			if (width) {
				child = slider.children("li:lt("+position+")");
			}
			else {
				child = slider.children("li:first");
			}
			
			if (options.scrollType == 'scroll') {
				child.animate({"margin-left": "-="+(width ? width : widthItem)+"px"}, 300, function() {
					slider.append(child);	
					slider.children("li").css("margin-left", "0");
					addCurrent();
				});
			}
			else {
				fade();
			}
		}
		
		function scrollLeft () {			
			child = slider.children("li:last");
			
			if (options.scrollType == 'scroll') {
				slider.prepend(child);
				child.css("margin-left", "-"+widthItem+"px");
				
				slider.children("li:first").animate({"margin-left": "+="+widthItem+"px"}, 300, function() {
					child.siblings().removeAttr("style");
					addCurrent();
				});
			}
			else {
				slider.children("li:first").fadeOut(300, function () {
					slider.prepend(child);
				});
				child.fadeIn(300);
			}
		}
		
		function fade() {
			child = slider.children("li:first");
			
			child.fadeOut(300, function () {
				slider.append(child);
			}).next().fadeIn(300);
		}
		
		function addCurrent () {
			if (options.scrollNumbers || options.scrollThumbs) {
				numbers.children("span:eq("+(slider.children("li:first").attr("id")-1)+")").addClass("current").siblings().removeClass("current");
			}
		}
	};
})(jQuery);