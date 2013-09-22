(function($) {
	'use strict';
    
	$.pageTabs = function(start_tab, opts) {
		var defaults = {
			containerClass: 'part-tabs',
			partPrefix: 'part-',
			contentClass: 'multi-part',
			activeClass: 'part-tabs-active',
			idTabPrefix: 'part-tabs-'
		};
		
		var options = $.extend({}, defaults, opts);
		var active_tab = start_tab || '';
		var hash = $.pageTabsGetHash();

		if (hash !== undefined && hash) {
			$('ul li a[href$="#'+hash+'"]').parent().trigger('click');
			active_tab = hash;
		} else { // open first part
			active_tab = $('.'+options.contentClass+':eq(0)').attr('id');
		}
		
		createTabs(active_tab, options);
		
		$('ul li', '.'+options.containerClass).click(function(e) {
			$(this).parent().find('li.'+options.activeClass).removeClass(options.activeClass);
			$(this).addClass(options.activeClass);
			$('.'+options.contentClass+'.active').removeClass('active').hide();
			$('#'+options.partPrefix+getId($(this).find('a').attr('href'))).addClass('active').show();
		});
		
		return this;
	};
	
	var createTabs = function createTabs(start_tab, options) {
		var lis = [], li_class = '', to_trigger = null;
		
		$('.'+options.contentClass).each(function() {
			if (start_tab != $(this).attr('id')) {
				$(this).hide();
				li_class = '';
			} else {
				$(this).addClass('active');
				to_trigger = $(this);
				li_class = ' class="'+options.activeClass+'"';
			}
			lis.push('<li id="'+options.idTabPrefix+$(this).attr('id')+'"'+li_class
				 +'><a href="#'+$(this).attr('id')+'">'+$(this).attr('title')+'</a></li>');
			$(this).attr('id', options.partPrefix + $(this).attr('id'));
		});
		
		$('<div class="'+options.containerClass+'"><ul>'+lis.join('')+'</ul></div>')
			.insertBefore($('.'+options.contentClass).get(0));	

		if (to_trigger != null) {
			$(to_trigger).onetabload();
			$(to_trigger).tabload();	
		}

	};
	
	var getId = function getId(href) {
		return href.split('#').join('');
	};

	$.pageTabsGetHash = function() {
		return document.location.hash.split('#').join('');
	};
})(jQuery);

jQuery.fn.tabload = function(f) {
	this.each(function() {
		if (f) {
			chainHandler(this,'tabload',f)
		} else {
			var h = this.tabload;
			if (h) { h.apply(this); }
		}
	});
	return this;
};

jQuery.fn.onetabload = function(f) {
	this.each(function() {
		if (f) {
			chainHandler(this,'onetabload',f);
		} else {
			var h = this.onetabload;
			if (h != null) {
				h.apply(this);
				this.onetabload = null;
			}
		}
	});
	return this;
};
