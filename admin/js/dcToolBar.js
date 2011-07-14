(function($){
	var methods = {
		init: function(options) {
			var settings = {
				formatter: ''
			}
			$.extend(settings,options);
			
			if (dcToolBar.getConfig(settings.formatter) == null) {
				if (console != 'undefined') {
					console.log('No toolbar configuration for formatter ['+ settings.formatter +']');
				}
			}
			
			tinymce.addI18n(dcToolBar.getI18n());
			
			return this.each(function(){
				$.data(this,'toolbar',new tinymce.Editor($(this).attr('id'),dcToolBar.getConfig(settings.formatter)));
				$(this).data('toolbar').activeFormatter = settings.formatter;
			});
		
		},
		draw: function() {
			return this.each(function(){
				if ($(this).data('toolbar') == null) {
					throw 'Toolbar should be initialize before render it';
				}
				$(this).data('toolbar').render();
			});
		},
		show: function() {
			return this.each(function(){
				if ($(this).data('toolbar') == null) {
					throw 'Toolbar should be initialize before show it';
				}
				var t = $(this).data('toolbar');
				
				t.show();
			});
		},
		hide: function() {
			return this.each(function(){
				if ($(this).data('toolbar') == null) {
					throw 'Toolbar should be initialize before hide it';
				}
				var t = $(this).data('toolbar');
				
				t.hide();
			});
		},
		toggle: function() {
			return this.each(function(){
				if ($(this).data('toolbar') == null) {
					throw 'Toolbar should be initialize before toogle it';
				}
				var t = $(this).data('toolbar');
				
				if (t.isHidden()) {
					$(t.getContainer()).show();
					$(t.getElement()).hide();
				} else {
					$(t.getContainer()).hide();
				}
			});
		},
		destroy: function() {
			return this.each(function(){
				if ($(this).data('toolbar') == null) {
					return;
				}
				$(this).data('toolbar').remove();
				$.data(this,'toolbar',null);
			});
		},
		switchMode: function(formatter) {
			return this.each(function(){
				if ($(this).data('formatter') != formatter) {
					var options = {};
					var displayed = !$(this).data('toolbar').isHidden();
					options.formatter = formatter;
					methods.destroy.apply($(this));
					methods.init.apply($(this),[options]);
					if (displayed) {
						methods.draw.apply($(this));
					}
				}
			});
		}
	};
	
	$.fn.dctoolbar = function(method) {
		try {
			if (methods[method]) {
				return methods[method].apply(this,Array.prototype.slice.call(arguments,1));
			} else if (typeof method === 'object' || !method) {
				return methods.init.apply(this,arguments);
			} else {
				throw 'Method ' + method + ' does not exist on jQuery.dctoolbar';
			}
		} catch (e) {
			$.error('Error happened on jQuery.dctoolbar: ' + e);
		}
	};
})(jQuery);

function dcToolBar() {
	this.setConfig = function(formatter,config) {
		this.configurations[formatter] = config;
	};
	
	this.setI18n = function(i18n) {
		this.i18n = i18n;
	};
	
	this.getConfig = function(formatter) {
		if (this.configurations.hasOwnProperty(formatter)) {
			return this.configurations[formatter];
		} else {
			return null;
		}
	};
	
	this.getI18n = function() {
		return this.i18n == null ? {} : this.i18n;
	};
}

dcToolBar.prototype = {
	configurations: {},
	i18n: null
}