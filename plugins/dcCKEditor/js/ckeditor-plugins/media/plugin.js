/*global CKEDITOR, dotclear, $ */
'use strict';

(() => {
  CKEDITOR.plugins.add('media', {
    icons: 'media',
    init(editor) {
      const popup_params = {
        width: 760,
        height: 500,
      };

      editor.addCommand('mediaCommand', {
        exec() {
          $.toolbarPopup('index.php?process=Media&popup=1&plugin_id=dcCKEditor', popup_params);
        },
      });

      editor.ui.addButton('Media', {
        label: dotclear.msg.img_select_title,
        command: 'mediaCommand',
        toolbar: 'insert',
      });

      editor.on('doubleclick', (e) => {
        const element = CKEDITOR.plugins.link.getSelectedLink(editor) || e.data.element;
        if (!element.isReadOnly() && (element.is('img') || (element.is('a') && element.hasClass('media-link')))) {
          $.toolbarPopup('index.php?process=Media&popup=1&plugin_id=dcCKEditor', popup_params);
          return false;
        }
      });
    },
  });
})();
