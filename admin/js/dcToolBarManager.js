(function($){
	var methods = {
		init: function(options) {
			var settings = {
				id: null,
				mode: null,
			};
			$.extend(settings,options);
			
			return this.each(function(){
				var _this = this;
				$.each(settings, function(k,v) {
					$.data(_this,k,v);
				});
				dcToolBarManager._init(settings.mode,_this);
			});
		
		},
		draw: function() {
			return this.each(function(){
				if ($(this).data('toolbar') == null) {
					throw 'Toolbar should be initialize before render it';
				}
				dcToolBarManager._draw($(this).data('mode'),this);
			});
		},
		switch: function(options) {
			mode = options ? options : 'xhtml';
			
			return this.each(function(){
				if ($(this).data('mode') != mode) {
					$.data(this,'mode',mode);
					dcToolBarManager._destroy($(this).data('mode'),this);
					dcToolBarManager._init(mode,this);
				}
			});
		}
	};
	
	$.fn.dctoolbarmanager = function(method) {
		//try {
			if (methods[method]) {
				return methods[method].apply(this,Array.prototype.slice.call(arguments,1));
			} else if (typeof method === 'object' || !method) {
				return methods.init.apply(this,arguments);
			} else {
				throw 'Method ' + method + ' does not exist on jQuery.dctoolbarmanager';
			}
		/*} catch (e) {
			$.error('Error happend on jQuery.dctoolbarmanager: ' + e);
		}*/
	};
})(jQuery);

function dcToolBarManager() {
	this.setToolBar = function(options) {
		try {
			var toolbar = {
				id: null,
				mode: null,
				js: [],
				css: [],
				onPreInit: function() {},
				onInit: function() {},
				onDraw: function() {},
				onDestroy: function() {}
			};
			
			$.extend(toolbar,options);
			
			if (toolbar.id == null || toolbar.id == '') {
				throw 'Invalid toolbar id';
			}
			
			if (toolbar.mode == null || toolbar.mode == '') {
				throw 'Invalid toolbar mode';
			}
			
			//var js = $.makeArray(toolbar.js);
			if (!$.isArray(toolbar.js)) {
				throw 'Invalid format for JS scripts';
			}
			//var css = $.makeArray(toolbar.css);
			if (!$.isArray(toolbar.css)) {
				throw 'Invalid format for CSS scripts';
			}
			
			// Add toolbar
			this.toolbars[toolbar.mode] = {
				id: toolbar.id,
				js: toolbar.js,
				css: toolbar.css,
				loaded: false,
				init: false
			};
			
			// Add events
			this.events[toolbar.mode] = {
				onPreInit: [],
				onInit: [],
				onDraw: [],
				onDestroy: []
			};
			this.bind('onPreInit',toolbar.mode,toolbar.onPreInit);
			this.bind('onInit',toolbar.mode,toolbar.onInit);
			this.bind('onDraw',toolbar.mode,toolbar.onDraw);
			this.bind('onDestroy',toolbar.mode,toolbar.onDestroy);
			
			// Pre-init toolbar
			this._preInit(toolbar.mode);
		} catch (e) {
			$.error('Toolbar signature error: ' + e.message);
		}
	};
	
	this.bind = function(event,mode,callback) {
		if (!this.events.hasOwnProperty(mode)) {
			throw 'No event available for toolbar [%s]'.replace('%s',id);
		}
		
		var events = this.events[mode];
		
		if (!events.hasOwnProperty(event)) {
			throw 'Event [%1$s] does not exist for toolbar [%2$s]'.replace('%1$s',event).replace('%2$s',mode);
		}
		
		if (typeof(callback) === 'function') {
			events[event].push(callback);
		}
	},
	
	this.call = function(event,mode) {
		if (!this.events.hasOwnProperty(mode)) {
			return;
		}
		
		var events = this.events[mode];
		
		if (!events.hasOwnProperty(event)) {
			return;
		}
		
		var _arguments = arguments;
		
		$.each(events[event],function(i,fn) {
			fn.apply(this,Array.prototype.slice.call(_arguments,2));
		});
	}
}

dcToolBarManager.prototype = {
	toolbars: {},
	events: {},
	msg: {},
	
	_preInit: function(mode) {
		if (!this.toolbars.hasOwnProperty(mode)) {
			throw 'Toolbar [%s] does not exist'.replace('%s',mode);
		}
		
		this.call('onPreInit',mode);
		
		var t = this.toolbars[mode];
		var n = t.js.length;
		
		// Loading JS scripts
		$.each(t.js, function(i,url) {
			$.ajax({
				async: false,
				url: url,
				dataType: 'script'
			});
		});
		
		// Loading CSS scripts
		$.each(t.css, function(j,css) {
			$('head').append($('<link/>').attr({
				rel: 'stylesheet',
				type: 'text/css',
				href: css
			}));
		});
		
		t.loaded = true;
	},
	
	_init: function(mode,elm) {
		if (!this.toolbars.hasOwnProperty(mode)) {
			throw 'Toolbar [%s] does not exist'.replace('%s',mode);
		}
		
		var _this = this;
		var t = this.toolbars[mode];
		
		var _this = this;
		var t = this.toolbars[mode];
		
		if (t.loaded) {
			if (!t.init) {
				// Init toolbar
				this.call('onInit',mode,elm);
				t.init = true;
			}
			// Draw toolbar
			this._draw(mode,elm);
			return;
		}
		
		setTimeout(function() { _this._init.apply(_this,[mode,elm]); },1);
	},
	
	_draw: function(mode,elm) {
		if (!this.toolbars.hasOwnProperty(mode)) {
			throw 'Toolbar [%s] does not exist'.replace('%s',mode);
		}
		
		var t = this.toolbars[mode];
		
		// Draw toolbar
		this.call('onDraw',mode,elm);
	},
	
	_destroy: function(mode,elm) {
		if (!this.toolbars.hasOwnProperty(mode)) {
			throw 'Toolbar [%s] does not exist'.replace('%s',mode);
		}
		
		var t = this.toolbars[mode];
		
		// Destroy toolbar
		this.call('onDestroy',mode,elm);
	}
}