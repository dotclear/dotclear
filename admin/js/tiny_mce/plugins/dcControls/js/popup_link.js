$(function() {
	$('#link-insert-ok').click(function(){
		var ed = window.opener.tinymce.activeEditor;
		var node = ed.selection.getNode();
		var formatter = ed.activeFormatter;
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
		
		// Create link
		if (node == null) {
			var link = '';
			if (formatter == 'xhtml') {
				link += '<a href="' + href + '"';
				if (title != '') {
					link += ' title="' + title + '"';
				}
				if (hreflang != '') {
					link += ' hreflang="' + hreflang + '"';
				}
				link += '>{$selection}</a>';
			}
			if (formatter == 'wiki') {
				link += '[{$selection}|' + href;
				if (hreflang != '') {
					link += '|' + hreflang;
				}
				if (title != '') {
					link += '|' + title;
				}
				link += ']';
			}
			ed.execCommand('mceReplaceContent',false,link,{skip_undo : 1});
		}
		// Update link
		else {
			if (formatter == 'xhtml') {
				node.href = href;
				if (title != '') {
					node.title = title;
				}
				if (hreflang != '') {
					node.hreflang = hreflang;
				}
			}
		}
		
		ed.execCommand("mceEndUndoLevel");
		ed.windowManager.close(window);
	});
	
	$('#link-insert-cancel').click(function(){
		window.opener.tinymce.activeEditor.windowManager.close(window);
	});
});