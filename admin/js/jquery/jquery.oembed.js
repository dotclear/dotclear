(function() {
	var settings = {
		maxWidth: null,
		maxHeight: null,
		embedMethod: 'auto',			// auto|append|replace
		defaultOEmbedProvider: 'embed.ly',	// oohembed|embed.ly|none
		allowedProviders: null,
		disallowedProviders: null,
		customProviders: null,			// [ new $.oembed('OEmbedProvider','customprovider', null, ['customprovider\\.com/watch.+v=[\\w-]+&?']) ]
		greedy: false,
		onProviderNotFound: function() {},
		beforeEmbed: function() {},
		afterEmbed: function() {},
		onEmbed: function() {},
		onError: function() {},
		ajaxOptions: {}
	};
	
	var publicMethods = {
		init: function(url,options) {
			$.extend(settings,options);
			
			internalMethods._init();
			
			return this.each(function(){
				var container = $(this);
				var resourceURL = (url != null) ? url : container.attr('href');
				var provider = null;
				
				if (!options.onEmbed) {
					settings.onEmbed = function (data) {
						internalMethods._insertCode(this,settings.embedMethod,data);
					};
				}
				
				if (resourceURL != null) {
					provider = publicMethods.getOEmbedProvider(resourceURL);
					
					if (provider != null) {
						provider.params = internalMethods._getNormalizedParams(settings[provider.name]) || {};
						provider.maxWidth = settings.maxWidth;
						provider.maxHeight = settings.maxHeight;
						internalMethods._doRequest(container,resourceURL,provider);
					} else {
						settings.onProviderNotFound.call(container, resourceURL);
					}
				}
				
				return container;
			});
		},
		
		OEmbedProvider: function(name,type,urlschemes,apiendpoint,callbackparameter) {
			this.name = name;
			this.type = type; // 'photo', 'video', 'link', 'rich', null
			this.urlschemes = getUrlSchemes(urlschemes);
			this.apiendpoint = apiendpoint;
			this.callbackparameter = callbackparameter;
			this.maxWidth = 500;
			this.maxHeight = 400;
			this.enable = true;
			var i, property, regExp;
			
			this.matches = function (externalUrl) {
				for (i = 0; i < this.urlschemes.length; i++) {
					regExp = new RegExp(this.urlschemes[i], 'i');
					if (externalUrl.match(regExp) != null) {
						return true;
					}
				}
				return false;
			};
			
			this.fromJSON = function (json) {
				for (property in json) {
					if (property != 'urlschemes') {
						this[property] = json[property];
					} else {
						this[property] = getUrlSchemes(json[property]);
					}
				}
				return true;
			};
			
			function getUrlSchemes(urls) {
				if (internalMethods._isNullOrEmpty(urls)) {
					return ['.'];
				}
				if ($.isArray(urls)) {
					return urls;
				}
				return urls.split(';');
			}
		},
		
		getOEmbedProvider: function(url) {
			for (var i = 0; i < providers.length; i++) {
				if (providers[i].matches(url)) {
					return providers[i];
				}
			}
			return null;
		}
	};
	
	var internalMethods = {
		_doRequest: function(container,url,embedProvider) {
			$.ajax($.extend({
				url: internalMethods._getRequestUrl(embedProvider,url),
				type: 'get',
				dataType: 'json',
				cache: false,
				timeout: 10000,
				success:  function (data) {
					var data = $.extend({
						type: null,
						version: null,
						title: null,
						author_name: null,
						author_url: null,
						provider_name: null,
						provider_url: null,
						cache_age: null,
						thumbnail_url: null,
						thumbnail_width: null,
						thumbnail_height: null,
						url: null,
						width: null,
						height: null,
						html: null,
						code: null
					}, data);
					switch (data.type) {
						case 'photo':
							data.code = internalMethods._getPhotoCode(data);
							break;
						case 'video':
							data.code = internalMethods._getVideoCode(data);
							break;
						case 'rich':
							data.code = internalMethods._getRichCode(data);
							break;
						case 'link':
							data.code = internalMethods._getLinkCode(data);
							break;
						case 'error':
							settings.onError.call(null,data.error_code,data.error_message);
							break;
					}
					settings.beforeEmbed.call(container,data);
					settings.onEmbed.call(container,data);
					settings.afterEmbed.call(container,data);
				},
				error: settings.onError
			},settings.ajaxOptions || {}));
		},
		
		_insertCode: function(container,method,data) {
			if (data.type == null && data.code == null) {
				return;
			}
			
			switch (method) {
				case 'auto':
					if (container.attr('href') == null) {
						internalMethods._insertCode(container,'append',data);
					}
					else {
						internalMethods._insertCode(container,'replace',data);
					};
					break;
				case 'replace':
					container.replaceWith(data.code);
					break;
				case 'append':
					container.html(data.code);
					break;
			}
		},
		
		_getPhotoCode: function(data) {
			var href = null;
			var title = null;
			var alt = new Array();
			
			href = data.thumbnail_url ? data.thumbnail_url : data.url;
			title = data.title ? data.title : '';
			
			if (data.provider_name) alt.push(data.provider_name);
			if (data.author_name) alt.push(data.author_name);
			if (data.title) alt.push(data.title);
			
			return $('<a>').attr({
				'href': data.url,
				'title': title
			}).append($('<img>').attr({
				'src': href,
				'title': title,
				'alt': alt.join(' - ')
			}));
		},
		
		_getVideoCode: function(data) {
			return internalMethods._getValidHXHTMLCode(data.html);
		},
		
		_getRichCode: function(data) {
			return internalMethods._getValidHXHTMLCode(data.html);
		},
		
		_getLinkCode: function(data) {
			var title = null;
			var alt = new Array();
			
			title = data.title ? data.title : '';
			
			if (data.provider_name) alt.push(data.provider_name);
			if (data.author_name) alt.push(data.author_name);
			if (data.title) alt.push(data.title);
			
			return $('<a>').attr({
				'href': data.url,
				'title': title
			}).append(title);
		},
		
		_getValidHXHTMLCode: function(html) {
			var xhtml = '';
			
			$(html).each(function() {
				if (this.tagName == 'IFRAME') {
					var attr = {
						'src': 'data',
						'width': 'width',
						'height': 'height'
					};
					var attributes = this.attributes;
					object = $('<object>');
					object.attr('type','text/html');
					for (i in attributes) {
						if (attr.hasOwnProperty(attributes[i].name)) {
							object.attr(attr[attributes[i].name],attributes[i].value);
						}
					}
					xhtml += object.get(0).outerHTML;
				} 
				else if (this.tagName == 'OBJECT') {
					if ($(this).find('embed').size() > 0) {
						var embed = $(this).find('embed').get(0);
						if ($.inArray('src',embed.attributes)) {
							$(this).attr('data',embed.attributes.src.nodeValue);
						}
						if ($.inArray('type',embed.attributes)) {
							$(this).attr('type',embed.attributes.type.nodeValue);
						}
						$(this).find('embed').remove();
					}
					xhtml += this.outerHTML;
				} else {
					xhtml += internalMethods._getValidHXHTMLCode($(html).find('iframe,object').get(0).outerHTML);
				}
			});
			
			return xhtml;
		},
		
		_init: function() {
			var provider;
			var i;
			
			// If there are allowed providers, jQuery oembed can not be greedy
			if (!internalMethods._isNullOrEmpty(settings.allowedProviders)) {
				settings.greedy = false;
			}
			
			// If there are allowed providers, jQuery oembed can not be greedy
			// Disabled also providers
			if (!internalMethods._isNullOrEmpty(settings.disallowedProviders)) {
				for (i = 0; i < providers.length; i++) {
					if ($.inArray(providers[i].name,settings.disallowedProviders)) {
						provider.enable = false;
					}
				}
				settings.greedy = false;
			}
			
			if (!internalMethods._isNullOrEmpty(settings.customProviders)) {
				$.each(settings.customProviders, function(n,customProvider) {
					if (customProvider instanceof publicMethods.OEmbedProvider) {
						providers.push(provider);
					} else {
						provider = new publicMethods.OEmbedProvider();
						if (provider.fromJSON(customProvider)) {
							providers.push(provider);
						}
					}
				});
			}
			
			// If in greedy mode, we add the default provider
			defaultProvider = internalMethods._getDefaultOEmbedProvider(settings.defaultOEmbedProvider);
			if (settings.greedy) {
				providers.push(defaultProvider);
			}
			
			// If any provider has no apiendpoint, we use the default provider endpoint
			for (i = 0; i < providers.length; i++) {
				if (providers[i].enable && providers[i].apiendpoint == null) {
					providers[i].apiendpoint = defaultProvider.apiendpoint;
				}
			}
		},
		
		_getDefaultOEmbedProvider: function(defaultOEmbedProvider) {
			var url = 'http://oohembed.com/oohembed/';
			if (defaultOEmbedProvider == 'embed.ly') {
				url = 'http://api.embed.ly/v1/api/oembed?';
			}
			return new publicMethods.OEmbedProvider(defaultOEmbedProvider,null,null,url,'callback');
		},
		
		_getRequestUrl: function(provider,externalUrl) {
			var url = provider.apiendpoint, qs = "", callbackparameter = provider.callbackparameter || 'callback', i;
			
			if (url.indexOf('?') <= 0) {
				url = url + '?';
			} else {
				url = url + '&';
			}
			
			if (provider.maxWidth != null && provider.params['maxwidth'] == null) {
				provider.params['maxwidth'] = provider.maxWidth;
			}
			
			if (provider.maxHeight != null && provider.params['maxheight'] == null) {
				provider.params['maxheight'] = provider.maxHeight;
			}
			
			for (i in provider.params) {
				// We don't want them to jack everything up by changing the callback parameter
				if (i == provider.callbackparameter) {
					continue;
				}
				
				// allows the options to be set to null, don't send null values to the server as parameters
				if (provider.params[i] != null) {
					qs += '&' + escape(i) + '=' + provider.params[i];
				}
			}
			
			url += 'format=json&url=' + escape(externalUrl) +
			qs +
			'&' + callbackparameter + '=?';
			
			return url;
		},
		
		_getNormalizedParams: function(params) {
			if (params == null) {
				return null;
			}
			
			var key;
			var normalizedParams = {};
			
			for (key in params) {
				if (key != null) {
					normalizedParams[key.toLowerCase()] = params[key];
				}
			}
			return normalizedParams;
		},
		
		_isNullOrEmpty: function(object) {
			if (typeof object == 'undefined') {
				return true;
			} else if (object == null) {
				return true;
			} else if ($.isArray(object) && object.length == 0) {
				return true;
			} else {
				return false;
			}
		}
	};
	
	var providers = [
		new publicMethods.OEmbedProvider('youtube', 'video', ['youtube\\.com/watch.+v=[\\w-]+&?']), // 'http://www.youtube.com/oembed'	(no jsonp)
		new publicMethods.OEmbedProvider('flickr', 'photo', ['flickr\\.com/photos/[-.\\w@]+/\\d+/?'], 'http://flickr.com/services/oembed', 'jsoncallback'),
		new publicMethods.OEmbedProvider('viddler', 'video', ['viddler\.com']), // 'http://lab.viddler.com/services/oembed/' (no jsonp)
		new publicMethods.OEmbedProvider('blip', 'video', ['blip\\.tv/.+'], 'http://blip.tv/oembed/'),
		new publicMethods.OEmbedProvider('hulu', 'video', ['hulu\\.com/watch/.*'], 'http://www.hulu.com/api/oembed.json'),
		new publicMethods.OEmbedProvider('vimeo', 'video', ['http:\/\/www\.vimeo\.com\/groups\/.*\/videos\/.*', 'http:\/\/www\.vimeo\.com\/.*', 'http:\/\/vimeo\.com\/groups\/.*\/videos\/.*', 'http:\/\/vimeo\.com\/.*'], 'http://vimeo.com/api/oembed.json'),
		new publicMethods.OEmbedProvider('dailymotion', 'video', ['dailymotion\\.com/.+']), // 'http://www.dailymotion.com/api/oembed/' (callback parameter does not return jsonp)
		new publicMethods.OEmbedProvider('scribd', 'rich', ['scribd\\.com/.+']), // ', 'http://www.scribd.com/services/oembed'' (no jsonp)		
		new publicMethods.OEmbedProvider('slideshare', 'rich', ['slideshare\.net'], 'http://www.slideshare.net/api/oembed/1'),
		new publicMethods.OEmbedProvider('photobucket', 'photo', ['photobucket\\.com/(albums|groups)/.*'], 'http://photobucket.com/oembed/')
	];
	
	$.fn.oembed = function(method) {
		if (publicMethods[method]) {
			return publicMethods[method].apply(this,Array.prototype.slice.call(arguments,1));
		} else if (typeof method === 'string' || !method) {
			return publicMethods.init.apply(this,arguments);
		} else {
			$.error('Method ' + method + ' does not exist on jQuery.oembed');
		}
	};
})(jQuery)