/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  $('#media-insert-cancel').on('click', () => {
    window.close();
  });

  $('#media-insert-ok').on('click', () => {
    const insert_form = $('#media-insert-form').get(0);
    if (insert_form === undefined) {
      return;
    }

    const styles = dotclear.getData('ck_editor_media');

    const editor_name = window.opener.$.getEditorName();
    const editor = window.opener.CKEDITOR.instances[editor_name];
    const type = insert_form.elements.type.value;
    const alignments = {
      left: styles.left,
      right: styles.right,
      center: styles.center,
    };

    if (type === 'image') {
      if (editor.mode === 'wysiwyg') {
        const align = $('input[name="alignment"]:checked', insert_form).val();
        const media_legend = $('input[name="legend"]:checked', insert_form).val();
        const description = $('input[name="description"]', insert_form).val();
        let style = '';
        let template = '';
        const template_figure = ['', ''];
        const template_link = ['', ''];
        let template_image = '';

        const alt =
          media_legend !== 'none'
            ? $('input[name="title"]', insert_form)
                .val()
                .replace('&', '&amp;')
                .replace('>', '&gt;')
                .replace('<', '&lt;')
                .replace('"', '&quot;')
            : '';
        let legend =
          media_legend === 'legend' && description !== '' && alt.length // No legend if no alt
            ? description.replace('&', '&amp;').replace('>', '&gt;').replace('<', '&lt;').replace('"', '&quot;')
            : false;

        // Do not duplicate information
        if (alt === legend) legend = false;

        // Build template
        if (align !== '' && align !== 'none') {
          // Set alignment
          style = ' class="{figureStyle}"';
        }

        if (media_legend === 'legend' && legend) {
          // With a legend
          template_figure[0] = `<figure${style}>`;
          style = ''; // Do not use style further
          if (legend !== '') {
            template_figure[1] = '<figcaption>{figCaption}</figcaption>';
          }
          template_figure[1] = `${template_figure[1]}</figure$>`;
        }

        template_image = `<img class="media" src="{imgSrc}" alt="{imgAlt}"${style}>`;

        if ($('input[name="insertion"]:checked', insert_form).val() === 'link' && alt.length) {
          // Enclose image with link (only if non empty alt)
          template_link[0] = '<a class="media-link" href="{aHref}"';
          const ltitle = ` title="${styles.img_link_title
            .replace('&', '&amp;')
            .replace('>', '&gt;')
            .replace('<', '&lt;')
            .replace('"', '&quot;')}"`;
          template_link[0] = `${template_link[0] + ltitle}>`;
          template_link[1] = '</a>';
        }

        // Compose final template
        template = template_figure[0] + template_link[0] + template_image + template_link[1] + template_figure[1];

        const block = new window.opener.CKEDITOR.template(template);
        const params = {};

        // Set parameters for template
        params.imgAlt = window.opener.CKEDITOR.tools.htmlEncodeAttr(alt);
        params.imgSrc = window.opener.$.stripBaseURL($('input[name="src"]:checked', insert_form).val());
        if (align !== '' && align !== 'none') {
          params.figureStyle = alignments[align];
        }
        params.figCaption = window.opener.CKEDITOR.tools.htmlEncodeAttr(legend);
        if ($('input[name="insertion"]:checked', insert_form).val() === 'link') {
          params.aHref = window.opener.$.stripBaseURL($('input[name="url"]', insert_form).val());
        }

        // Insert element
        const figure = window.opener.CKEDITOR.dom.element.createFromHtml(block.output(params), editor.document);
        if (align !== '' && align !== 'none') {
          figure.addClass(alignments[align]);
        }
        editor.insertElement(figure);
      }
    } else if (type === 'mp3') {
      // Audio media
      let player_audio = $('#public_player').val();

      const align = $('input[name="alignment"]:checked', insert_form).val();
      const alignment = align !== undefined && align !== 'none' ? ` class="${media_align_grid[align]}"` : '';

      const title = insert_form.elements.real_title.value;
      if (title) {
        player_audio = `<figure${alignment}><figcaption>${title}</figcaption>${player_audio}</figure>`;
      }

      if (align !== undefined && align !== 'none') {
        player_audio = `<div${alignment}>${player_audio}</div>`;
      }

      const element = window.opener.CKEDITOR.dom.element.createFromHtml(player_audio);
      if (align !== '' && align !== 'none') {
        element.addClass(alignments[align]);
      }
      editor.insertElement(element);
    } else if (type === 'flv') {
      // Video media
      const oplayer = $(`<div>${$('#public_player').val()}</div>`);

      const align = $('input[name="alignment"]:checked', insert_form).val();
      const alignment = align !== undefined && align !== 'none' ? ` class="${media_align_grid[align]}"` : '';

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

      const title = insert_form.elements.real_title.value;
      if (title) {
        player_video = `<figure${alignment}><figcaption>${title}</figcaption>${player_video}</figure>`;
      }

      if (align !== undefined && align !== 'none') {
        player_video = `<div${alignment}>${player_video}</div>`;
      }
      const element = window.opener.CKEDITOR.dom.element.createFromHtml(player_video);
      if (align !== '' && align !== 'none') {
        element.addClass(alignments[align]);
      }
      editor.insertElement(element);
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
