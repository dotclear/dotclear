$(function() {
	$('#link-insert-cancel').click(function() {
		window.close();
	});

	$('#link-insert-ok').click(function() {
		var insert_form = $('#link-insert-form').get(0);
		if (insert_form == undefined) {
			return;
		}

		var editor_name = window.opener.$.getEditorName(),
		editor = window.opener.CKEDITOR.instances[editor_name],
		link = '',
		selected_element;
		if (editor.getSelection().getSelectedElement()!=null) {
			selected_element = editor.getSelection().getSelectedElement();
		} else {
			selected_element = editor.getSelection().getNative().toString();
		}

		if (editor.mode=='wysiwyg') {
			var link = editor.document.createElement('a');
			link.setAttribute('href', insert_form.elements.href.value);
			if (insert_form.elements.title.value!='') {
				link.setAttribute(
					'title',
					window.opener.CKEDITOR.tools.htmlEncodeAttr(insert_form.elements.title.value)
				);
			}
			if (insert_form.elements.hreflang.value!='') {
				link.setAttribute(
					'hreflang',
					window.opener.CKEDITOR.tools.htmlEncodeAttr(insert_form.elements.hreflang.value)
				);
			}
			if (editor.getSelection().getSelectedElement()!=null) {
				selected_element.appendTo(link);
			} else {
				link.appendText(selected_element);
			}
			editor.insertElement(link);
		}
		window.close();
	});
});
