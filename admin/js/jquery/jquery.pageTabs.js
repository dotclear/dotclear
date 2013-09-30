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
		
		$.pageTabs.options = $.extend({}, defaults, opts);
		var active_tab = start_tab || '';
		var hash = $.pageTabs.getLocationHash();

		if (hash !== undefined && hash) {
			$('ul li a[href$="#'+hash+'"]').parent().trigger('click');
			active_tab = hash;
		} else if (active_tab == '') { // open first part
			active_tab = $('.'+$.pageTabs.options.contentClass+':eq(0)').attr('id');
		}

		createTabs();
		
		$('ul li', '.'+$.pageTabs.options.containerClass).click(function(e) {
			if ($(this).hasClass($.pageTabs.options.activeClass)) {
				return;
			}

			$(this).parent().find('li.'+$.pageTabs.options.activeClass).removeClass($.pageTabs.options.activeClass);
			$(this).addClass($.pageTabs.options.activeClass);
			$('.'+$.pageTabs.options.contentClass+'.active').removeClass('active').hide();

			var part_to_activate = $('#'+$.pageTabs.options.partPrefix+getHash($(this).find('a').attr('href')));

			part_to_activate.addClass('active').show();
			if (!part_to_activate.hasClass('loaded')) {
				part_to_activate.onetabload();
				part_to_activate.addClass('loaded');
			}
			
			part_to_activate.tabload();
		});

		$(window).bind('hashchange onhashchange', function(e) {
			$.pageTabs.clickTab($.pageTabs.getLocationHash());
		});

		$.pageTabs.clickTab(active_tab);
		
		return this;
	};
	
	var createTabs = function createTabs() {
		var lis = [], li_class = '';
		
		$('.'+$.pageTabs.options.contentClass).each(function() {
			$(this).hide();
			lis.push('<li id="'+$.pageTabs.options.idTabPrefix+$(this).attr('id')+'">'
				 +'<a href="#'+$(this).attr('id')+'">'+$(this).attr('title')+'</a></li>');
			$(this).attr('id', $.pageTabs.options.partPrefix + $(this).attr('id')).prop('title','');
		});
		
		$('<div class="'+$.pageTabs.options.containerClass+'"><ul>'+lis.join('')+'</ul></div>')
			.insertBefore($('.'+$.pageTabs.options.contentClass).get(0));
	};
	
	var getHash = function getHash(href) {
		return href.replace(/.*#/, '');
	};
	
	$.pageTabs.clickTab = function(tab) {
		if (tab=='') {
			tab = getHash($('ul li a', '.'+$.pageTabs.options.containerClass+':eq(0)').attr('href'));
		}

		$('ul li a', '.'+$.pageTabs.options.containerClass).filter(function() {
			return getHash($(this).attr('href'))==tab;
		}).parent().click();
	};

	$.pageTabs.getLocationHash = function() {
		return getHash(document.location.hash);
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
