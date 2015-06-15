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
		    figure = '',
		    fig_caption = '',
		    media_align_grid = {
			    left: 'float: left; margin: 0 1em 1em 0;',
			    right: 'float: right; margin: 0 0 1em 1em;',
			    center: 'margin: 0 auto; display: block;'
		    };

		if (type=='image') {
			if (editor.mode=='wysiwyg') {
				var figure_template = '<figure style="{figureStyle}"><img class="media" src="{imgSrc}" alt="{imgAlt}"/><figcaption>{figCaption}</figcaption></figure>',
				    a_figure_template = '<a class="media-link" href="{aHref}">'+figure_template+'</a>',
				    figure_block = new window.opener.CKEDITOR.template(figure_template),
				    a_figure_block = new window.opener.CKEDITOR.template( a_figure_template),
				    params = {},
				    templateBlock = null;

				var align = $('input[name="alignment"]:checked',insert_form).val();
				if (align!='' && align!='none') {
					params.figureStyle = media_align_grid[align];
				}

				var img_description = $('input[name="description"]',insert_form).val();
				params.figCaption = window.opener.CKEDITOR.tools.htmlEncodeAttr(img_description);
				params.imgAlt = 'alt for image';
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

				// figure = '<figure';
				// img = '<img class="media" src="';
				// img += window.opener.$.stripBaseURL($('input[name="src"]:checked',insert_form).val())+'"';

				// fig_caption = '<figcaption>'+img_description+'</figcaption>';


				// var title = $('input[name="title"]',insert_form).val();
				// img += ' alt="'+window.opener.CKEDITOR.tools.htmlEncodeAttr(title)+'"/>';

				// figure += img + fig_caption + '</figure>';

				// var element;
				// if ($('input[name="insertion"]:checked',insert_form).val() == 'link') {
				// 	var link = '<a class="media-link" href="';
				// 	link += window.opener.$.stripBaseURL($('input[name="url"]',insert_form).val());
				// 	link += '">'+figure+'</a>';

				// 	element = '<a>'+figure+'</a>';//link;
				// } else {
				// 	element = figure;
				// }

				// alert('element: '+element);

				// editor.insertElement(window.opener.CKEDITOR.dom.element.createFromHtml(element));
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
