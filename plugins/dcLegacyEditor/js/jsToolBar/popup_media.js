/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  document.getElementById('media-insert')?.addEventListener('onetabload', () => {
    document.getElementById('media-insert-cancel')?.addEventListener('click', () => {
      window.close();
    });

    document.getElementById('media-insert-ok')?.addEventListener('click', () => {
      sendClose();
      window.close();
    });
  });

  function sendClose() {
    const form = document.getElementById('media-insert-form');
    if (!form) {
      return;
    }

    const tb = window.opener.the_toolbar;
    const type = form.elements.type.value;
    const media_align_grid = {
      left: tb.style.left,
      right: tb.style.rigth,
      center: tb.style.center,
    };

    if (type === 'image') {
      tb.elements.img_select.data.src = tb.stripBaseURL(form.querySelector('input[name="src"]:checked').value);
      tb.elements.img_select.data.alignment = form.querySelector('input[name="alignment"]:checked').value;
      tb.elements.img_select.data.link = form.querySelector('input[name="insertion"]:checked').value === 'link';

      tb.elements.img_select.data.title = form.elements.title.value;
      tb.elements.img_select.data.description = form.querySelector('input[name="description"]').value;
      tb.elements.img_select.data.url = tb.stripBaseURL(form.elements.url.value);

      let media_legend = form.querySelector('input[name="legend"]:checked').value;
      if (media_legend !== '' && media_legend !== 'title' && media_legend !== 'none') {
        media_legend = 'legend';
      }
      if (media_legend !== 'legend') {
        tb.elements.img_select.data.description = '';
      }
      if (media_legend === 'none') {
        tb.elements.img_select.data.title = '';
      }

      tb.elements.img_select.fncall[tb.mode].call(tb);
      return;
    }
    if (type === 'mp3') {
      const mplayer = document.querySelector('#public_player').value;
      let player = mplayer;

      const align = form.querySelector('input[name="alignment"]:checked').value;
      const alignment = align !== undefined && align !== 'none' ? ` class="${media_align_grid[align]}"` : '';

      const title = form.elements.real_title.value;
      if (title) {
        player = `<figure${alignment}><figcaption>${title}</figcaption>${player}</figure>`;
      }

      if (align !== undefined && align !== 'none') {
        player = `<div${alignment}>${player}</div>`;
      }

      tb.elements.mp3_insert.data.player = player.replace(/>/g, '>\n');
      tb.elements.mp3_insert.fncall[tb.mode].call(tb);
      return;
    }
    if (type === 'flv') {
      // may be all video media, not only flv
      const vplayer = document.createElement('div');
      vplayer.innerHTML = document.querySelector('#public_player').value;

      const align = form.querySelector('input[name="alignment"]:checked').value;
      const alignment = align !== undefined && align !== 'none' ? ` class="${media_align_grid[align]}"` : '';

      const vw = document.querySelector('#video_w').value;
      const vh = document.querySelector('#video_h').value;

      if (vw > 0) {
        vplayer.querySelector('video')?.setAttribute('width', vw);
        vplayer.querySelector('object')?.setAttribute('width', vw);
      } else {
        vplayer.querySelector('video')?.removeAttribute('width');
        vplayer.querySelector('object')?.removeAttribute('width');
      }
      if (vh > 0) {
        vplayer.querySelector('video')?.setAttribute('height', vh);
        vplayer.querySelector('object')?.setAttribute('height', vh);
      } else {
        vplayer.querySelector('video')?.removeAttribute('height');
        vplayer.querySelector('object')?.removeAttribute('height');
      }
      let player = vplayer.innerHTML;

      const title = form.elements.real_title.value;
      if (title) {
        player = `<figure${alignment}><figcaption>${title}</figcaption>${player}</figure>`;
      }

      if (align !== undefined && align !== 'none') {
        player = `<div${alignment}>${player}</div>`;
      }

      tb.elements.flv_insert.data.player = player.replace(/>/g, '>\n');
      tb.elements.flv_insert.fncall[tb.mode].call(tb);
      return;
    }

    tb.elements.link.data.href = tb.stripBaseURL(form.elements.url.value);
    tb.elements.link.data.content = form.elements.title.value;
    tb.elements.link.fncall[tb.mode].call(tb);
  }
});
