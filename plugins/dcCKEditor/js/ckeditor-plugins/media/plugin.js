/*global CKEDITOR, dotclear, $ */
'use strict';

(function () {
  CKEDITOR.plugins.add('media', {
    icons: 'media',
    init: function (editor) {
      const popup_params = {
        width: 760,
        height: 500,
      };

      editor.addCommand('mediaCommand', {
        exec: function () {
          $.toolbarPopup('media.php?popup=1&plugin_id=dcCKEditor', popup_params);
        },
      });

      editor.ui.addButton('Media', {
        label: dotclear.msg.img_select_title,
        command: 'mediaCommand',
        toolbar: 'insert',
      });

      editor.on('doubleclick', function (e) {
        const element = CKEDITOR.plugins.link.getSelectedLink(editor) || e.data.element;
        if (!element.isReadOnly()) {
          if (element.is('img') || (element.is('a') && element.hasClass('media-link'))) {
            $.toolbarPopup('media.php?popup=1&plugin_id=dcCKEditor', popup_params);
            return false;
          }
        }
      });
    },
  });
})();
