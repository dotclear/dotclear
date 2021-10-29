/*global CKEDITOR, dotclear, $ */
'use strict';

(() => {
  CKEDITOR.plugins.add('dclink', {
    icons: 'dclink',
    init(editor) {
      editor.addCommand('dcLinkCommand', {
        exec(editor) {
          if (
            editor.getSelection().getSelectedElement() != null ||
            editor.getSelection().getNative().toString().replace(/\s*/, '') != ''
          ) {
            $.toolbarPopup('popup_link.php?plugin_id=dcCKEditor');
          }
        },
      });

      editor.ui.addButton('dcLink', {
        label: dotclear.msg.link_title,
        command: 'dcLinkCommand',
        toolbar: 'insert',
      });

      editor.on('doubleclick', (e) => {
        const element = CKEDITOR.plugins.link.getSelectedLink(editor) || e.data.element;
        if (
          !element.isReadOnly() &&
          element.is('a') &&
          !element.hasClass('media-link') && // link to original media @see js/popup_media.js
          !element.hasClass('ref-post')
        ) {
          // link to an entry @see js/popup_posts.js

          editor.getSelection().selectElement(element);

          let popup_url = 'popup_link.php?plugin_id=dcCKEditor';
          if (element.getAttribute('href')) {
            popup_url += `&href=${element.getAttribute('href')}`;
          }
          if (element.getAttribute('hreflang')) {
            popup_url += `&hreflang=${element.getAttribute('hreflang')}`;
          }
          if (element.getAttribute('title')) {
            popup_url += `&title=${element.getAttribute('title')}`;
          }

          $.toolbarPopup(popup_url);
          return false;
        }
      });
    },
  });
})();
