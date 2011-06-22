tinyMCEPopup.requireLangPack();

var popup_media = {
	init: function() {
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
			var url = $('input[name="url"]').val();
			var src = $('input[name="src"]:checked').val();
			var type = $('input[name="type"]').val();
			var title = $('input[name="title"]').val();
			var description = $('input[name="description"]').val();
			var alignment = $('input[name="alignment"]:checked').val();
			var insertion = $('input[name="insertion"]:checked').val();
			var player = $('#public_player').val();
			var width = $('#video_w').val();
			var height = $('#video_h').val();
			
			if (type == 'image') {
				var opts_img = {
					src: src,
					alt: (description || title),
					title: title
				};
				var opts_a = {
					href: url,
					title: opts_img.alt
				};
				
				if (alignment != 'none') {
					opts_img.style = media_align_grid[alignment];
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
				var res = null;
				if (alignment != 'none') {
					res = ed.dom.createHTML('div',{style: media_align_grid[alignment]},player);
				}
				else {
					res = player;
				}
				ed.execCommand('mceInsertContent',false,res,{skip_undo : 1});
			}
			else if (type == 'flv') {
				var res = null;
				var opt_div = {};
				var oplayer = $(player);
				var flashvars = $('[name="FlashVars"]',player).val();
				
				if (title) {
					flashvars = 'title='+title+'&amp;'+flashvars;
				}
				flashvars = flashvars.replace(/(width=\d*)/,'width='+width);
				flashvars = flashvars.replace(/(height=\d*)/,'height='+height);
				$('[name="FlashVars"]',oplayer).val(flashvars);
				
				oplayer.attr('width',width);
				oplayer.attr('height',height);
				
				if (alignment != 'none') {
					opt_div.style = media_align_grid[alignment];
				}
				
				res = ed.dom.createHTML('div',opt_div,ed.dom.getOuterHTML(oplayer.get(0)));
				
				ed.execCommand('mceInsertContent',false,res,{skip_undo : 1});
			}
			else {
				ed.execCommand('mceInsertLink', false, '#mce_temp_url#', {skip_undo : 1});
				
				elementArray = tinymce.grep(ed.dom.select('a'),function(n) {return ed.dom.getAttrib(n,'href') == '#mce_temp_url#';});
				for (i=0; i<elementArray.length; i++) {
					var node = elementArray[i];
					ed.dom.setAttrib(node,'href',href);
					ed.dom.setAttrib(node,'title',title);
				}
			}
			
			ed.execCommand('mceEndUndoLevel');
			ed.execCommand('mceRepaint');
			tinyMCEPopup.close();
		});
		
		$('#media-insert-cancel').click(function() {
			tinyMCEPopup.close();
		});
	}
};

tinyMCEPopup.onInit.add(popup_media.init, popup_media);