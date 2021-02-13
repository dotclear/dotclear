/*global $, dotclear, CKEDITOR, getData */
'use strict';

Object.assign(dotclear.msg, getData('ck_editor_tags'));

(function () {
  CKEDITOR.plugins.add('dctags', {
    init: function (editor) {
      editor.addCommand('dcTagsCommand', {
        exec: function (editor) {
          if (editor.getSelection().getNative().toString().replace(/\s*/, '') != '') {
            const str = editor.getSelection().getNative().toString().replace(/\s*/, '');
            const url = dotclear.msg.tag_url;
            window.dc_tag_editor.addMeta(str);
            const link = `<a href="${$.stripBaseURL(url + '/' + str)}">${str}</a>`;
            const element = CKEDITOR.dom.element.createFromHtml(link);
            editor.insertElement(element);
          }
        },
      });

      editor.ui.addButton('dcTags', {
        label: dotclear.msg.tag_title,
        command: 'dcTagsCommand',
        toolbar: 'insert',
        icon: this.path + 'tag.png',
      });
    },
  });
})();
