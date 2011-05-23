(function($){
	var methods = {
		init: function(options) {
			var settings = {
				mode: 'xhtml'
			};
			return this.each(function(){
				if (options) {
					$.extend(settings,options);
				}
				$.data(this,'mode',settings.mode);
				dcToolBarManager._load(settings.mode,this);
			});
		
		},
		draw: function() {
			return this.each(function(){
				dcToolBarManager._draw($(this).data('mode'),this);
			});
		},
		switch: function(options) {
			mode = options ? options : 'xhtml';
			
			return this.each(function(){
				if ($(this).data('mode') != mode) {
					$.data(this,'mode',mode);
					dcToolBarManager._destroy($(this).data('mode'),this);
					dcToolBarManager._load(mode,this);
					dcToolBarManager._draw(mode,this);
				}
			});
		}
	};
	
	$.fn.dctoolbarmanager = function(method) {
		if (methods[method]) {
			return methods[method].apply(this,Array.prototype.slice.call(arguments,1));
		} else if (typeof method === 'object' || !method) {
			return methods.init.apply(this,arguments);
		} else {
			$.error('Method ' + method + ' does not exist on jQuery.dctoolbar');
		}
	};
})(jQuery);

function dcToolBarManager() {
	this.setToolBar = function(options) {
		var settings = {
			id: 'generic',
			js_urls: [],
			css_urls: [],
			preinit: function() {},
			init: function() {},
			load: function() {},
			draw: function() {},
			destroy: function() {}
		};
		
		$.extend(settings,options);
		
		this.toolbars[settings.id] = {
			id: settings.id,
			js_urls: settings.js_urls,
			css_urls: settings.css_urls,
			preinit: settings.preinit,
			init: settings.init,
			load: settings.load,
			draw: settings.draw,
			destroy: settings.destroy,
			loaded: false
		};
		
		this.fn[settings.id] = new Array();
		
		this._init(settings.id);
	};
};

dcToolBarManager.prototype = {
	toolbars: {},
	fn: {},
	msg: {
		toolbar_does_not_exists: 'Toolbar [%s] does not exists'
	},
	
	_init: function(mode) {
		try {
			var _this = this;
			var results = [];
			
			var t = this.toolbars[mode];
			
			if (t.loaded) {
				return;
			}
			
			var n = t.js_urls.length;
			
			// Pre-initialization
			t.preinit();
			
			// Loading JS scripts
			$.each(t.js_urls, function(i,url) {
				$('head').append($('<script>').attr({
					type: 'text/javascript',
					src: url
				}));
				if(! --n) {
					t.loaded = true;
					t.init();
					$.each(_this.fn[mode],function(i,callback) {
						callback();
					});
				}
			});
			
			// Loading CSS scripts
			$.each(t.css_urls, function(j,css) {
				$('head').append($('<link/>').attr({
					rel: 'stylesheet',
					type: 'text/css',
					href: css
				}));
			});
		} catch (e) {
			$.error('Error during toolbar [' + id + '] initialization: ' + e.message);
		}
	},
	
	_load: function(mode,elm) {
		if (!this.toolbars[mode]) {
			throw this.msg.toolbar_does_not_exists.replace('%s',mode);
		}
		var t = this.toolbars[mode];
		t.load(elm);
	},
	
	_draw: function(mode,elm) {
		var t = this.toolbars[mode];
		t.draw(elm);
	},
	
	_destroy: function(mode,elm) {
		var t = this.toolbars[mode];
		t.destroy(elm);
	}
}