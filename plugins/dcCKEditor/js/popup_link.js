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
		selected_text = editor.getSelection().getNative().toString();

		if (editor.mode=='wysiwyg') {
			link = '<a href="'+insert_form.elements.href.value+'"';
			if (insert_form.elements.title.value!='') {
				link += ' title="'+insert_form.elements.title.value+'"';
			}
			if (insert_form.elements.hreflang.value!='') {
				link += ' hreflang="'+insert_form.elements.hreflang.value+'"';
			}
			link += '>'+selected_text+'</a>';
			var element = window.opener.CKEDITOR.dom.element.createFromHtml(link);
			editor.insertElement(element);
		}
		window.close();
	});
});
