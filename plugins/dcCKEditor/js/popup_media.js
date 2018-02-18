/*global $ */
'use strict';

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
        center: 'margin: 0 auto; display: table;'
      };

    if (type == 'image') {
      if (editor.mode == 'wysiwyg') {

        var align = $('input[name="alignment"]:checked', insert_form).val();
        var media_legend = $('input[name="legend"]:checked', insert_form).val();
        var img_description = $('input[name="description"]', insert_form).val();
        var style = '';
        var template = '';
        var template_figure = [
          '',
          ''
        ];
        var template_link = [
          '',
          ''
        ];
        var template_image = '';

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
          template_figure[0] = '<figure' + style + '>';
          style = ''; // Do not use style further
          if (img_description != '') {
            template_figure[1] = '<figcaption>{figCaption}</figcaption>';
          }
          template_figure[1] = template_figure[1] + '</figure>';
        }
        template_image = '<img class="media" src="{imgSrc}" alt="{imgAlt}"' + style + '/>';
        if ($('input[name="insertion"]:checked', insert_form).val() == 'link') {
          // With a link to original
          template_link[0] = '<a class="media-link" href="{aHref}">';
          template_link[1] = '</a>';
        }
        template = template_figure[0] + template_link[0] + template_image + template_link[1] + template_figure[1];

        var block = new window.opener.CKEDITOR.template(template);
        var params = {};

        // Set parameters for template
        if (media_legend != '' && media_legend != 'none') {
          params.imgAlt = window.opener.CKEDITOR.tools.htmlEncodeAttr(
            window.opener.$.stripBaseURL($('input[name="title"]', insert_form).val()));
        } else {
          params.imgAlt = '';
        }
        params.imgSrc = window.opener.$.stripBaseURL($('input[name="src"]:checked', insert_form).val());
        if (align != '' && align != 'none') {
          params.figureStyle = media_align_grid[align];
        }
        params.figCaption = window.opener.CKEDITOR.tools.htmlEncodeAttr(img_description);
        if ($('input[name="insertion"]:checked', insert_form).val() == 'link') {
          params.aHref = window.opener.$.stripBaseURL($('input[name="url"]', insert_form).val());
        }

        // Insert element
        var figure = window.opener.CKEDITOR.dom.element.createFromHtml(
          block.output(params), editor.document
        );
        editor.insertElement(figure);
      }
    } else if (type == 'mp3') {
      // Audio media
      var player_audio = $('#public_player').val();
      var align_audio = $('input[name="alignment"]:checked', insert_form).val();

      if (align_audio != undefined && align_audio != 'none') {
        player_audio = '<div style="' + media_align_grid[align_audio] + '">' + player_audio + '</div>';
      }
      editor.insertElement(window.opener.CKEDITOR.dom.element.createFromHtml(player_audio));
    } else if (type == 'flv') {
      // Video media
      var oplayer = $('<div>' + $('#public_player').val() + '</div>');
      var flashvars = $('[name=FlashVars]', oplayer).val();

      var align_video = $('input[name="alignment"]:checked', insert_form).val();
      var title = insert_form.elements.title.value;

      $('video', oplayer).attr('width', $('#video_w').val());
      $('video', oplayer).attr('height', $('#video_h').val());

      if (title) {
        flashvars = 'title=' + encodeURI(title) + '&amp;' + flashvars;
      }
      $('object', oplayer).attr('width', $('#video_w').val());
      $('object', oplayer).attr('height', $('#video_h').val());
      flashvars = flashvars.replace(/(width=\d*)/, 'width=' + $('#video_w').val());
      flashvars = flashvars.replace(/(height=\d*)/, 'height=' + $('#video_h').val());

      $('[name=FlashVars]', oplayer).val(flashvars);
      var player_video = oplayer.html();

      if (align_video != undefined && align_video != 'none') {
        player_video = '<div style="' + media_align_grid[align_video] + '">' + player_video + '</div>';
      }
      editor.insertElement(window.opener.CKEDITOR.dom.element.createFromHtml(player_video));
    } else {
      // Unknown media type
      var link = '<a href="';
      link += window.opener.$.stripBaseURL($('input[name="url"]', insert_form).val());
      link += '">' + window.opener.CKEDITOR.tools.htmlEncodeAttr(insert_form.elements.title.value) + '</a>';
      var element = window.opener.CKEDITOR.dom.element.createFromHtml(link);

      editor.insertElement(element);
    }

    window.close();
  });
});
