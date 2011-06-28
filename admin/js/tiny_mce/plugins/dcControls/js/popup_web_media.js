tinyMCEPopup.requireLangPack();

var popup_web_media = {
	oembed_opts: {
		maxWidth: 450,
		maxHeight: 400,
		onProviderNotFound: function(url) {
			$('#src').removeClass().addClass('error');
			alert(tinyMCEPopup.editor.getLang('dcControls_dlg.provider_not_supported'));
		},
		beforeEmbed: function(data) {
			if (data.type == 'error') {
				$('#src').removeClass().addClass('error');
				$('a.insert').hide();
			} else {
				$('#src').removeClass().addClass('success');
				$('a.insert').show();
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
					$('input[value="thumbnail"]').attr('disabled',true);
				} else {
					$('input[value="thumbnail"]').attr('disabled',false);
				}
				
				$(this).data('data',data);
				
				$('div.two-cols').slideDown();
			}
		},
		onError: function(xhr,status,error) {
			$('#src').removeClass().addClass('error');
			alert(tinyMCEPopup.editor.getLang('dcControl_dlg.webmedia_no_information'));
		}
	},
	
	init: function() {
		$('a.insert').hide();
		
		$('#src').focusin(function(e) {
			$(this).removeClass();
		}).keypress(function(e) {
			if (e.keyCode == 13) {
				$(this).focusout();
				$('#webmedia-insert-search').click();
			}
		});
		
		$('#webmedia-insert-search').click(function() {
			if ($('#src').val() == '') {
				return;
			}
			
			$('a.insert').hide();
			
			$('div.two-cols').slideUp();
			
			$('#src').removeClass().addClass('loading');
			
			$('#alt,#title,#width,#height').val('');
			
			$('div.preview').data('data',null);
			$('div.preview').oembed($('#src').val(),popup_web_media.oembed_opts);
		});
		
		$('#webmedia-insert-ok').click(function(){
			var ed = tinyMCEPopup.editor;
			var media_align_grid = {
				left: 'float: left; margin: 0 1em 1em 0;',
				right: 'float: right; margin: 0 0 1em 1em;',
				center: 'text-align: center;'
			};
			var data = $('div.preview').data('data');
			var alignment = $('input[name=alignment]:checked').val();
			var insertion = $('input[name="insertion"]:checked').val();
			var src = $('input[name="src"]').val();
			var title = $('input[name="title"]').val();
			var alt = $('input[name="alt"]').val();
			var width = $('input[name="width"]').val();
			var height = $('input[name="height"]').val();
			
			if (data != null) {
				var res = null;
				var opts_div = {
					class: 'media media-' + data.type
				};
				var opts_img = {
					src: data.thumbnail_url,
					alt: alt,
					title: title
				};
				var opts_a = {
					href: src,
					title: title
				};
				
				switch($('input[name="insertion"]:checked').val()) {
					case 'media':
						code = $(data.code).attr({
							'width': width,
							'height': height
						});
						res = ed.dom.create('div',opts_div,ed.dom.getOuterHTML(code.get(0)))
						break;
					case 'thumbnail':
						var img = ed.dom.create('img',opts_img);
						res = ed.dom.create('a',opts_a,ed.dom.getOuterHTML(img));
						break;
					case 'link':
						res = ed.dom.create('a',opts_a,alt);
						break;
				}
				
				if (alignment != 'none') {
					ed.dom.setAttribs(res,{style: media_align_grid[alignment]});
				}
				
				ed.execCommand('mceInsertContent',false,ed.dom.getOuterHTML(res) + ed.dom.createHTML('p'));
				ed.execCommand('mceRepaint');
				tinyMCEPopup.close();
			} else {
				alert(tinyMCEPopup.editor.getLang('dcControls_dlg.no_media_loaded'));
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
	}
};

function plop(data) {
	alert(data.type);
}

tinyMCEPopup.onInit.add(popup_web_media.init, popup_web_media);