/*global CKEDITOR, dotclear, $ */
'use strict';

(() => {
  CKEDITOR.plugins.add('entrylink', {
    init(editor) {
      const popup_params = {
        width: 760,
        height: 500,
      };

      editor.addCommand('entryLinkCommand', {
        exec(editor) {
          $.toolbarPopup('index.php?process=PostsPopup&popup=1&plugin_id=dcCKEditor', popup_params);
        },
      });

      editor.ui.addButton('EntryLink', {
        label: dotclear.msg.post_link_title,
        command: 'entryLinkCommand',
        toolbar: 'insert',
        icon: `${this.path}icons/icon.svg`,
      });

      editor.on('doubleclick', (e) => {
        const element = CKEDITOR.plugins.link.getSelectedLink(editor) || e.data.element;
        if (!(!element.isReadOnly() && element.is('a') && !element.hasClass('media-link') && element.hasClass('ref-post'))) {
          return;
        }
        // link to original media @see js/popup_media.js
        editor.getSelection().selectElement(element);

        $.toolbarPopup('index.php?process=PostsPopup&popup=1&plugin_id=dcCKEditor', popup_params);
        return false;
      });
    },
  });
})();
