$(function() {
	$('#link-insert-cancel').click(function() {
		window.close();
	});

	$('#form-entries tr>td.maximal>a').click(function(e) {
		e.preventDefault();
		var editor_name = window.opener.$.getEditorName(),
		editor = window.opener.CKEDITOR.instances[editor_name],
		link = '',
		selected_text = editor.getSelection().getNative().toString();

		if (editor.mode=='wysiwyg') {
			link = '<a class="post" href="'+window.opener.$.stripBaseURL($(this).attr('title'))+'">'+selected_text+'</a>';
			var element = window.opener.CKEDITOR.dom.element.createFromHtml(link);
			editor.insertElement(element);
		}
		window.close();
	});
});
