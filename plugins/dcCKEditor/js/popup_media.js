/*global $ */
'use strict';

$(() => {
  $('#media-insert-cancel').on('click', () => {
    window.close();
  });

  $('#media-insert-ok').on('click', () => {
    const insert_form = $('#media-insert-form').get(0);
    if (insert_form === undefined) {
      return;
    }

    const editor_name = window.opener.$.getEditorName();
    const editor = window.opener.CKEDITOR.instances[editor_name];
    const type = insert_form.elements.type.value;
    const media_align_grid = {
      left: 'float: left; margin: 0 1em 1em 0;',
      right: 'float: right; margin: 0 0 1em 1em;',
      center: 'margin: 0 auto; display: table;',
    };

    if (type == 'image') {
      if (editor.mode == 'wysiwyg') {
        const align = $('input[name="alignment"]:checked', insert_form).val();
        let media_legend = $('input[name="legend"]:checked', insert_form).val();
        const img_description = $('input[name="description"]', insert_form).val();
        let style = '';
        let template = '';
        const template_figure = ['', ''];
        const template_link = ['', ''];
        let template_image = '';

        if (media_legend != '' && media_legend != 'title' && media_legend != 'none') {
          media_legend = 'legend';
        }

        // Build template
        if (align != '' && align != 'none') {
          // Set alignment
          style = ' style="{figureStyle}"';
        }
        if (media_legend == 'legend') {
          // With a legend
          template_figure[0] = `<figure${style}>`;
          style = ''; // Do not use style further
          if (img_description != '') {
            template_figure[1] = '<figcaption>{figCaption}</figcaption>';
          }
          template_figure[1] = `${template_figure[1]}</figure$>`;
        }
        template_image = `<img class="media" src="{imgSrc}" alt="{imgAlt}"${style}/>`;
        if ($('input[name="insertion"]:checked', insert_form).val() == 'link') {
          // With a link to original
          template_link[0] = '<a class="media-link" href="{aHref}">';
          template_link[1] = '</a>';
        }
        template = template_figure[0] + template_link[0] + template_image + template_link[1] + template_figure[1];

        const block = new window.opener.CKEDITOR.template(template);
        const params = {};

        // Set parameters for template
        params.imgAlt = media_legend != '' && media_legend != 'none' ? window.opener.CKEDITOR.tools.htmlEncodeAttr(
          window.opener.$.stripBaseURL($('input[name="title"]', insert_form).val())
        ) : '';
        params.imgSrc = window.opener.$.stripBaseURL($('input[name="src"]:checked', insert_form).val());
        if (align != '' && align != 'none') {
          params.figureStyle = media_align_grid[align];
        }
        params.figCaption = window.opener.CKEDITOR.tools.htmlEncodeAttr(img_description);
        if ($('input[name="insertion"]:checked', insert_form).val() == 'link') {
          params.aHref = window.opener.$.stripBaseURL($('input[name="url"]', insert_form).val());
        }

        // Insert element
        const figure = window.opener.CKEDITOR.dom.element.createFromHtml(block.output(params), editor.document);
        editor.insertElement(figure);
      }
    } else if (type == 'mp3') {
      // Audio media
      let player_audio = $('#public_player').val();
      const title = insert_form.elements.title.value;
      if (title) {
        player_audio = `<figure><figcaption>${title}</figcaption>${player_audio}</figure>`;
      }

      const align_audio = $('input[name="alignment"]:checked', insert_form).val();

      if (align_audio != undefined && align_audio != 'none') {
        player_audio = `<div style="${media_align_grid[align_audio]}">${player_audio}</div>`;
      }
      editor.insertElement(window.opener.CKEDITOR.dom.element.createFromHtml(player_audio));
    } else if (type == 'flv') {
      // Video media
      const oplayer = $(`<div>${$('#public_player').val()}</div>`);

      const align_video = $('input[name="alignment"]:checked', insert_form).val();

      const vw = $('#video_w').val();
      const vh = $('#video_h').val();

      if (vw > 0) {
        $('video', oplayer).attr('width', vw);
        $('object', oplayer).attr('width', vw);
      } else {
        $('video', oplayer).removeAttr('width');
        $('object', oplayer).removeAttr('width');
      }
      if (vh > 0) {
        $('video', oplayer).attr('height', vh);
        $('object', oplayer).attr('height', vh);
      } else {
        $('video', oplayer).removeAttr('height');
        $('object', oplayer).removeAttr('height');
      }

      let player_video = oplayer.html();

      if (align_video != undefined && align_video != 'none') {
        player_video = `<div style="${media_align_grid[align_video]}">${player_video}</div>`;
      }
      editor.insertElement(window.opener.CKEDITOR.dom.element.createFromHtml(player_video));
    } else {
      // Unknown media type
      const url = window.opener.$.stripBaseURL($('input[name="url"]', insert_form).val());
      const text = window.opener.CKEDITOR.tools.htmlEncodeAttr(insert_form.elements.title.value);
      const element = window.opener.CKEDITOR.dom.element.createFromHtml(`<a href="${url}">${text}</a>`);

      editor.insertElement(element);
    }

    window.close();
  });
});
