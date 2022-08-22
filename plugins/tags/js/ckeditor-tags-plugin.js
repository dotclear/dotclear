/*global $, dotclear, CKEDITOR */
'use strict';

Object.assign(dotclear.msg, dotclear.getData('ck_editor_tags'));

{
  CKEDITOR.plugins.add('dctags', {
    init(editor) {
      editor.addCommand('dcTagsCommand', {
        exec(e) {
          if (e.getSelection().getNative().toString().replace(/\s*/, '') != '') {
            const str = e.getSelection().getNative().toString().replace(/\s*/, '');
            const url = dotclear.msg.tag_url;
            window.dc_tag_editor.addMeta(str);
            const link = `<a href="${$.stripBaseURL(`${url}/${str}`)}">${str}</a>`;
            const element = CKEDITOR.dom.element.createFromHtml(link);
            e.insertElement(element);
          }
        },
      });

      editor.ui.addButton('dcTags', {
        label: dotclear.msg.tag_title,
        command: 'dcTagsCommand',
        toolbar: 'insert',
        icon: `${this.path}tag.png`,
      });
    },
  });
}
