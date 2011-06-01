$(function() {
	$('#form-entries tr>td.maximal>a').click(function() {
		var ed = window.opener.tinymce.activeEditor;
		var node = ed.selection.getNode();
		var formatter = ed.activeFormatter;
		var href = $(this).attr('title');
		var title = $(this).html();
		
		node = ed.dom.getParent(node, 'A');
		
		// Create link
		if (node == null) {
			var link = '';
			if (formatter == 'xhtml') {
				link += '<a href="' + href + '"';
				if (title != '') {
					link += ' title="' + title + '"';
				}
				link += '>{$selection}</a>';
			}
			if (formatter == 'wiki') {
				link += '[{$selection}|' + href;
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
			}
		}
		
		ed.execCommand("mceEndUndoLevel");
		ed.windowManager.close(window);
	});
	
	$('#link-insert-cancel').click(function() {
		window.opener.tinymce.activeEditor.windowManager.close(window);
	});
});