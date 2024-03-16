/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  $('#media-insert').on('onetabload', () => {
    $('#media-insert-cancel').on('click', () => {
      window.close();
    });

    $('#media-insert-ok').on('click', () => {
      sendClose();
      window.close();
    });
  });

  function sendClose() {
    const insert_form = $('#media-insert-form').get(0);
    if (insert_form == undefined) {
      return;
    }

    const tb = window.opener.the_toolbar;
    const type = insert_form.elements.type.value;
    const media_align_grid = {
      left: tb.style.left,
      right: tb.style.rigth,
      center: tb.style.center,
    };
    let align;
    let player;

    if (type == 'image') {
      tb.elements.img_select.data.src = tb.stripBaseURL($('input[name="src"]:checked', insert_form).val());
      tb.elements.img_select.data.alignment = $('input[name="alignment"]:checked', insert_form).val();
      tb.elements.img_select.data.link = $('input[name="insertion"]:checked', insert_form).val() == 'link';

      tb.elements.img_select.data.title = insert_form.elements.title.value;
      tb.elements.img_select.data.description = $('input[name="description"]', insert_form).val();
      tb.elements.img_select.data.url = tb.stripBaseURL(insert_form.elements.url.value);

      let media_legend = $('input[name="legend"]:checked', insert_form).val();
      if (media_legend != '' && media_legend != 'title' && media_legend != 'none') {
        media_legend = 'legend';
      }
      if (media_legend != 'legend') {
        tb.elements.img_select.data.description = '';
      }
      if (media_legend == 'none') {
        tb.elements.img_select.data.title = '';
      }

      tb.elements.img_select.fncall[tb.mode].call(tb);
      return;
    }
    if (type == 'mp3') {
      player = $('#public_player').val();
      align = $('input[name="alignment"]:checked', insert_form).val();

      const title = insert_form.elements.title.value;
      if (title) {
        player = `<figure><figcaption>${title}</figcaption>${player}</figure>`;
      }

      if (align != undefined && align != 'none') {
        player = `<div class="${media_align_grid[align]}">${player}</div>`;
      }

      tb.elements.mp3_insert.data.player = player.replace(/>/g, '>\n');
      tb.elements.mp3_insert.fncall[tb.mode].call(tb);
      return;
    }
    if (type == 'flv') {
      // may be all video media, not only flv
      const oplayer = $(`<div>${$('#public_player').val()}</div>`);

      align = $('input[name="alignment"]:checked', insert_form).val();

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

      player = oplayer.html();

      if (align != undefined && align != 'none') {
        player = `<div class="${media_align_grid[align]}">${player}</div>`;
      }

      tb.elements.flv_insert.data.player = player.replace(/>/g, '>\n');
      tb.elements.flv_insert.fncall[tb.mode].call(tb);
      return;
    }
    tb.elements.link.data.href = tb.stripBaseURL(insert_form.elements.url.value);
    tb.elements.link.data.content = insert_form.elements.title.value;
    tb.elements.link.fncall[tb.mode].call(tb);
  }
});
