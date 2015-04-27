$(function() {
	$('#media-insert-cancel').click(function() {
		window.close();
	});

	$('#media-insert-ok').click(function() {
		var insert_form = $('#media-insert-form').get(0);
		if (insert_form === undefined) {
			return;
		}

		var editor_name = window.opener.$.getEditorName(),
		editor = window.opener.CKEDITOR.instances[editor_name],
		type = insert_form.elements.type.value,
		img = '',
		media_align_grid = {
			left: 'float: left; margin: 0 1em 1em 0;',
			right: 'float: right; margin: 0 0 1em 1em;',
			center: 'margin: 0 auto; display: block;'
		};

		if (type=='image') {
			if (editor.mode=='wysiwyg') {
				img = '<img class="media" src="';
				img += window.opener.$.stripBaseURL($('input[name="src"]:checked',insert_form).val())+'"';
				var img_title = $('input[name="description"]',insert_form).val();
				img_title = window.opener.CKEDITOR.tools.htmlEncodeAttr(img_title);
				img += ' title="'+img_title+'"';
				var align = $('input[name="alignment"]:checked',insert_form).val();
				if (align!='' && align!='none') {
					img += ' style="'+media_align_grid[align]+'"';
				}

				var title = $('input[name="title"]',insert_form).val();
				img += ' alt="'+window.opener.CKEDITOR.tools.htmlEncodeAttr(title)+'"/>';

				var element;
				if ($('input[name="insertion"]:checked',insert_form).val() == 'link') {
					var link = '<a class="media-link" href="';
					link += window.opener.$.stripBaseURL($('input[name="url"]',insert_form).val());
					link += '">'+img+'</a>';

					element = window.opener.CKEDITOR.dom.element.createFromHtml(link);
				} else {
					element = window.opener.CKEDITOR.dom.element.createFromHtml(img);
				}

				editor.insertElement(element);
			}
		} else {
			var link = '<a href="';
			link += window.opener.$.stripBaseURL($('input[name="url"]',insert_form).val());
			link += '">'+window.opener.CKEDITOR.tools.htmlEncodeAttr(insert_form.elements.title.value)+'</a>';
			element = window.opener.CKEDITOR.dom.element.createFromHtml(link);

			editor.insertElement(element);
		}

		window.close();
	});
});
