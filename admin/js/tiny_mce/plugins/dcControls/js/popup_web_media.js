tinyMCEPopup.requireLangPack();

var popup_web_media = {
	oembed_opts: {
		maxWidth: 480,
		maxHeight: 400,
		defaultOEmbedProvider: 'embed.ly',
		greedy: false,
		embedMethod: 'fill',
		onProviderNotFound: function(url) {
			$('#src').removeClass().addClass('error');
			alert('Provider not supported');
		},
		beforeEmbed: function(data) {
			if (data.type == 'error') {
				$('#src').removeClass().addClass('error');
			} else {
				$('#src').removeClass().addClass('success');
				if (!data.thumbnail_url) {
					$('input[value="thumbnail"]').attr('disabled',true);
				} else {
					$('input[value="thumbnail"]').attr('disabled',false);
				}
				$('div.two-cols').slideDown();
			}
		},
		afterEmbed: function(data) {
			if (data.type != 'error') {
				var attr = new Array();
				if (data.provider_name) attr.push(data.provider_name);
				if (data.author_name) attr.push(data.author_name);
				if (data.title) attr.push(data.title);
				
				$('#alt').val(attr.join(' - '));
				$('#title').val(data.title);
				
				if (data.width) $('#width').val(data.width);
				if (data.height) $('#height').val(data.height);
				
				if (!data.thumbnail_url) {
					$('input[name="insertion" value="thumbnail"]').attr('disabled', 'disabled');
				}
				
				$(this).data('code',popup_web_media.getValidXHTMLCode(data.html));
			}
		},
		onEmbed: function(data) {
			$(this).data('code',popup_web_media.getValidXHTMLCode(data.html));
		},
		onError: function(xhr,status,error) {
			$('#src').removeClass().addClass('error');
		},
		ajaxOptions: {
			cache: false,
			timeout: 5000
		}
	},
	
	init: function() {
		$('#src').focusin(function() {
			$(this).removeClass();
		});
		
		$('#webmedia-insert-search').click(function() {
			if ($('#src').val() == '') {
				return;
			}
			
			$('div.two-cols').slideUp();
			
			$('#src').removeClass().addClass('loading');
			
			$('#alt,#title').val('');
			
			$('div.preview').data('code',null);
			$('div.preview').oembed($('#src').val(),popup_web_media.oembed_opts);
		});
		
		$('#webmedia-insert-ok').click(function(){
			var ed = tinyMCEPopup.editor;
			var xhtml = $('div.preview').data('code');
			
			if (xhtml != null) {
				ed.execCommand('mceInsertContent',false,xhtml.get(0).outerHTML)
				tinyMCEPopup.close();
			} else {
				alert('provide a valide media');
			}
		});
		
		$('#webmedia-insert-cancel').click(function(){
			tinyMCEPopup.close();
		});
	},
	
	getOEmbedListInfo: function(data,attr) {
		var info = $('<ul>');
		for (i in data) {
			if (attr.hasOwnProperty(i)) {
				info.append($('<li>').append(attr[i] + ': ' + data[i]));
			}
			
		}
		return info;
	},
	
	getValidXHTMLCode: function(html) {
		var xhtml = $(html);
		var type = xhtml.get(0).tagName;
		
		if (type == 'IFRAME') {
			var attr = {
				'src': 'data',
				'width': 'width',
				'height': 'height'
			};
			var attributes = xhtml.get(0).attributes;
			xhtml = $('<object>');
			xhtml.attr('type','text/html');
			for (i in attributes) {
				if (attr.hasOwnProperty(attributes[i].name)) {
					xhtml.attr(attr[attributes[i].name],attributes[i].value);
				}
			}
		} 
		else if (type == 'OBJECT') {
			if (xhtml.find('embed').size() > 0) {
				var embed = xhtml.find('embed').get(0);
				if ($.inArray('src',embed.attributes)) {
					xhtml.attr('data',embed.attributes.src.nodeValue);
				}
				if ($.inArray('type',embed.attributes)) {
					xhtml.attr('type',embed.attributes.type.nodeValue);
				}
				xhtml.find('embed').remove();
			}
		}
		else {
			xhtml = this.getValidXHTMLCode(xhtml.find('iframe,object').get(0).outerHTML);
		}
		
		return xhtml;
	}
};

function plop(data) {
	alert(data.type);
}

tinyMCEPopup.onInit.add(popup_web_media.init, popup_web_media);