tinyMCEPopup.requireLangPack();

var popup_link = {
	init: function() {
		$('#div-entries h4').toggleWithLegend($('#div-entries').children().not('h4'),{
			cookie: 'dcx_div_entries'
		});
		
		$('#form-entries tr>td.maximal>a').click(function() {
			$('#href').val($(this).attr('title'));
			$('#title').val($(this).html());
			return false;
		});
		
		$('#link-insert-ok').click(function(){
			var ed = tinyMCEPopup.editor;
			var formatter = ed.getParam('formatter');
			var node = ed.selection.getNode();
			var href = $('#href').val();
			var title = $('#title').val();
			var hreflang = $('#hreflang').val();
			
			// href attribue is mandatory
			if (href == '') {
				$('#href').focus();
				$('#href').backgroundFade({sColor:'#ffffff',eColor:'#ff9999',steps:50},function() {
					$(this).backgroundFade({sColor:'#ff9999',eColor:'#ffffff'});
				});
				return false;
			}
			
			node = ed.dom.getParent(node, 'A');
			
			// Insert link
			if (node == null) {
				ed.getDoc().execCommand("unlink", false, null);
				tinyMCEPopup.execCommand("mceInsertLink", false, "#mce_temp_url#", {skip_undo : 1});
		
				elementArray = tinymce.grep(ed.dom.select("a"),function(n) {return ed.dom.getAttrib(n,'href') == '#mce_temp_url#';});
				for (i=0; i<elementArray.length; i++) {
					node = elementArray[i];
					ed.dom.setAttrib(node,'href',href);
					ed.dom.setAttrib(node,'title',title);
					ed.dom.setAttrib(node,'hreflang',hreflang);
				}
			}
			// Update link
			else {
				ed.dom.setAttrib(node,'href',href);
				ed.dom.setAttrib(node,'title',title);
				ed.dom.setAttrib(node,'hreflang',hreflang);
			}
			
			// Don't move caret if selection was image
			if (node.childNodes.length != 1 || node.firstChild.nodeName != 'IMG') {
				ed.focus();
				ed.selection.select(node);
				ed.selection.collapse(0);
				tinyMCEPopup.storeSelection();
			}
		
			tinyMCEPopup.execCommand("mceEndUndoLevel");
			tinyMCEPopup.close();
		});
		
		$('#link-insert-cancel').click(function(){
			tinyMCEPopup.close();
		});
	}
};

tinyMCEPopup.onInit.add(popup_link.init, popup_link);