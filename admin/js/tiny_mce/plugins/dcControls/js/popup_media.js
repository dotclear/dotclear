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
				var player = $('#public_player').val();
				
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
					 
					if (insertion == 'link') {
						res = ed.dom.createHTML('a',opts_a,ed.dom.getOuterHTML(img));
					}
					else {
						res = ed.dom.getOuterHTML(img);
					}
					ed.execCommand('mceInsertContent',false,res,{skip_undo : 1});
				}
				else if (type == 'mp3') {
					var opts_divs
					if (alignment != 'none') {
						res = ed.dom.createHTML('a',{style: media_align_grid[alignment]},player);
					}
					else {
						res = player;
					}
				}
				else if (type == 'flv') {
					/*player = ed.dom.create('a',{},player);
					ar oplayer = $('<div>'+$('#public_player').val()+'</div>');
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
					tb.elements.flv_insert.fncall[tb.mode].call(tb);*/
				}
				else {
					tinyMCEPopup.execCommand('mceInsertLink', false, '#mce_temp_url#', {skip_undo : 1});
		
					elementArray = tinymce.grep(ed.dom.select("a"),function(n) {return ed.dom.getAttrib(n,'href') == '#mce_temp_url#';});
					for (i=0; i<elementArray.length; i++) {
						var node = elementArray[i];
						ed.dom.setAttrib(node,'href',href);
						ed.dom.setAttrib(node,'title',title);
					}
				}
				
				tinyMCEPopup.execCommand('mceEndUndoLevel');
				tinyMCEPopup.close();
			});
			
			$('#media-insert-cancel').click(function() {
				tinyMCEPopup.close();
			});
		});
	}
};

tinyMCEPopup.onInit.add(popup_media.init, popup_media);