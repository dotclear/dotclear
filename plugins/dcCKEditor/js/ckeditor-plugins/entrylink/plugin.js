/*global CKEDITOR, dotclear, $ */
'use strict';

(function () {
  CKEDITOR.plugins.add('entrylink', {
    icons: 'entrylink',
    init: function (editor) {
      const popup_params = {
        width: 760,
        height: 500,
      };

      editor.addCommand('entryLinkCommand', {
        exec: function (editor) {
          if (editor.getSelection().getNative().toString().replace(/\s*/, '') != '') {
            $.toolbarPopup('popup_posts.php?popup=1&plugin_id=dcCKEditor', popup_params);
          }
        },
      });

      editor.ui.addButton('EntryLink', {
        label: dotclear.msg.post_link_title,
        command: 'entryLinkCommand',
        toolbar: 'insert',
      });

      editor.on('doubleclick', function (e) {
        const element = CKEDITOR.plugins.link.getSelectedLink(editor) || e.data.element;
        if (!element.isReadOnly()) {
          if (element.is('a') && !element.hasClass('media-link') && element.hasClass('ref-post')) {
            // link to original media @see js/popup_media.js
            editor.getSelection().selectElement(element);

            $.toolbarPopup('popup_posts.php?popup=1&plugin_id=dcCKEditor', popup_params);
            return false;
          }
        }
      });
    },
  });
})();
