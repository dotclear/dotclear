tinyMCEPopup.requireLangPack();

var popup_media = {
	init: function() {
		$('#media-insert').onetabload(function() {
			$('#media-insert-ok').click(function() {
				// Internal vars
				var ed = tinyMCEPopup.editor;
				var formatter = ed.getParam('formatter');
				var node = ed.selection.getNode();
				var media_align_grid = {
					left: 'float: left; margin: 0 1em 1em 0;',
					right: 'float: right; margin: 0 0 1em 1em;',
					center: 'text-align: center;'
				};
				// Form vars
				var url = $('input[name=url]').val();
				var src = $('input[name=src]:checked').val();
				var type = $('input[name=type]').val();
				var title = $('input[name=title]').val();
				var description = $('input[name=description]').val();
				var alignment = $('input[name=alignment]:checked').val();
				var insertion = $('input[name=insertion]:checked').val();
				
				if (type == 'image') {
					var opts_img = {src: src};
					if (alignment != 'none') {
						opts_img.style = media_align_grid[alignment];
					}
					if (description != '' || title != '') {
						opts_img.alt = (description || title).replace('&','&amp;').replace('>','&gt;').replace('<','&lt;').replace('"','&quot;');
					}
					if (title != '') {
						opts_img.title = title.replace('&','&amp;').replace('>','&gt;').replace('<','&lt;').replace('"','&quot;');
					}
					
					var opts_a = {href: url};
					if (opts_img.hasOwnProperty('alt')) {
						opts_a.title = opts_img.alt.replace('&','&amp;').replace('>','&gt;').replace('<','&lt;').replace('"','&quot;');
					}
					
					var res = null;
					var img = ed.dom.create('img',opts_img);
					var a = ed.dom.create('a',opts_a);
					 
					if (insertion == 'link') {
						res = ed.dom.add(a,img);
					}
					else {
						res = img;
					}
					ed.execCommand('mceReplaceContent',false,ed.dom.getOuterHTML(res),{skip_undo : 1});
				}
				else if (type == 'mp3') {
				
				}
				else if (type == 'flv') {
				
				}
				else {
				
				}
				
				tinyMCEPopup.close();
			});
			
			$('#media-insert-cancel').click(function() {
				tinyMCEPopup.close();
			});
		});
		
		function sendClose() {
			var insert_form = $('#media-insert-form').get(0);
			if (insert_form == undefined) { return; }
			
			var tb = window.opener.the_toolbar;
			var type = insert_form.elements.type.value;
			
			var media_align_grid = {
				left: 'float: left; margin: 0 1em 1em 0;',
				right: 'float: right; margin: 0 0 1em 1em;',
				center: 'text-align: center;'
			};
			
			if (type == 'image')
			{
				tb.elements.img_select.data.src = tb.stripBaseURL($('input[name="src"]:checked',insert_form).val());
				tb.elements.img_select.data.alignment = $('input[name="alignment"]:checked',insert_form).val();
				tb.elements.img_select.data.link = $('input[name="insertion"]:checked',insert_form).val() == 'link';
				
				tb.elements.img_select.data.title = insert_form.elements.title.value;
				tb.elements.img_select.data.description = $('input[name="description"]',insert_form).val();
				tb.elements.img_select.data.url = tb.stripBaseURL(insert_form.elements.url.value);
				tb.elements.img_select.fncall[tb.mode].call(tb);
			}
			else if (type == 'mp3')
			{
				var player = $('#public_player').val();
				var align = $('input[name="alignment"]:checked',insert_form).val();
				
				if (align != undefined && align != 'none') {
					player = '<div style="' + media_align_grid[align] + '">' + player + '</div>';
				}
				
				tb.elements.mp3_insert.data.player = player.replace(/>/g,'>\n');
				tb.elements.mp3_insert.fncall[tb.mode].call(tb);
			}
			else if (type == 'flv')
			{
				var oplayer = $('<div>'+$('#public_player').val()+'</div>');
				var flashvars = $("[name=FlashVars]",oplayer).val();
				
				var align = $('input[name="alignment"]:checked',insert_form).val();
				var title = insert_form.elements.title.value;
				
				if (title) {
					flashvars = 'title='+encodeURI(title)+'&amp;'+flashvars;
				}
				$('object',oplayer).attr('width',$('#video_w').val());
				$('object',oplayer).attr('height',$('#video_h').val());
				flashvars = flashvars.replace(/(width=\d*)/,'width='+$('#video_w').val());
				flashvars = flashvars.replace(/(height=\d*)/,'height='+$('#video_h').val());
				
				$("[name=FlashVars]",oplayer).val(flashvars);
				var player = oplayer.html();	
				
				if (align != undefined && align != 'none') {
					player = '<div style="' + media_align_grid[align] + '">' + player + '</div>';
				}
				
				tb.elements.flv_insert.data.player = player.replace(/>/g,'>\n');
				tb.elements.flv_insert.fncall[tb.mode].call(tb);
			}
			else
			{
				tb.elements.link.data.href = tb.stripBaseURL(insert_form.elements.url.value);
				tb.elements.link.data.content = insert_form.elements.title.value;
				tb.elements.link.fncall[tb.mode].call(tb);
			}
		};
		
		function playerFormat(s) {
			s = s.replace(/&lt;/g,'<');
			s = s.replace(/&gt;/g,'>\n');
			s = s.replace(/&amp;/g,'&');
			
			return s;
		};
		
		/*var alt = (str) ? str : d.title;
			var res = '<img src="'+d.src+'" alt="'+alt.replace('&','&amp;').replace('>','&gt;').replace('<','&lt;').replace('"','&quot;')+'"';
			
			if (d.alignment == 'left') {
				res += ' style="float: left; margin: 0 1em 1em 0;"';
			} else if (d.alignment == 'right') {
				res += ' style="float: right; margin: 0 0 1em 1em;"';
			} else if (d.alignment == 'center') {
				res += ' style="margin: 0 auto; display: block;"';
			}
			
			if (d.description) {
				res += ' title="'+d.description.replace('&','&amp;').replace('>','&gt;').replace('<','&lt;').replace('"','&quot;')+'"';
			}
			
			res += ' />';
			
			if (d.link) {
				var ltitle = (alt) ? ' title="'+alt.replace('&','&amp;').replace('>','&gt;').replace('<','&lt;').replace('"','&quot;')+'"' : '';
				res = '<a href="'+d.url+'"'+ltitle+'>'+res+'</a>';
			}
			
			return res;*/
	}
};

tinyMCEPopup.onInit.add(popup_media.init, popup_media);