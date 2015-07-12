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
		    media_align_grid = {
			    left: 'float: left; margin: 0 1em 1em 0;',
			    right: 'float: right; margin: 0 0 1em 1em;',
			    center: 'margin: 0 auto; display: block;'
		    };

		if (type=='image') {
			if (editor.mode=='wysiwyg') {
				var figure_template = '<figure style="{figureStyle}"><img class="media" src="{imgSrc}" alt="{imgAlt}"/><figcaption>{figCaption}</figcaption></figure>',
				    a_figure_template = '<figure style="{figureStyle}"><a class="media-link" href="{aHref}"><img class="media" src="{imgSrc}" alt="{imgAlt}"/></a><figcaption>{figCaption}</figcaption></figure>',
				    figure_block = new window.opener.CKEDITOR.template(figure_template),
				    a_figure_block = new window.opener.CKEDITOR.template(a_figure_template),
				    params = {},
				    templateBlock = null;

				var align = $('input[name="alignment"]:checked',insert_form).val();
				if (align!='' && align!='none') {
					params.figureStyle = media_align_grid[align];
				}

				var img_description = $('input[name="description"]',insert_form).val();
				params.figCaption = window.opener.CKEDITOR.tools.htmlEncodeAttr(img_description);

				var selected_element = '';
				if (editor.getSelection().getSelectedElement() !=null ) {
					selected_element = editor.getSelection().getSelectedElement();
				} else {
					selected_element = editor.getSelection().getNative().toString();
				}
				if (selected_element == '') {
					selected_element = window.opener.$.stripBaseURL($('input[name="title"]',insert_form).val());
				}
				params.imgAlt = window.opener.CKEDITOR.tools.htmlEncodeAttr(selected_element);
				params.imgSrc = window.opener.$.stripBaseURL($('input[name="src"]:checked',insert_form).val());

				if ($('input[name="insertion"]:checked',insert_form).val() == 'link') {
					params.aHref = window.opener.$.stripBaseURL($('input[name="url"]',insert_form).val());
					templateBlock = a_figure_block;
				} else {
					templateBlock = figure_block;
				}

				var figure = window.opener.CKEDITOR.dom.element.createFromHtml(
					templateBlock.output(params), editor.document
				);

				editor.insertElement(figure);
			}
		} else if (type=='mp3') {
			var player = $('#public_player').val();
			var align = $('input[name="alignment"]:checked',insert_form).val();

			if (align != undefined && align != 'none') {
				player = '<div style="' + media_align_grid[align] + '">' + player + '</div>';
			}
			editor.insertElement(window.opener.CKEDITOR.dom.element.createFromHtml(player));
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
