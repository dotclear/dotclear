/*global dotclear, CKEDITOR */
'use strict';

Object.assign(dotclear.msg, dotclear.getData('ck_editor_tags'));

CKEDITOR.plugins.add('dctags', {
  init(editor) {
    editor.addCommand('dcTagsCommand', {
      exec(e) {
        if (e.getSelection().getNative().toString().replace(/\s*/, '') === '') {
          return;
        }
        const str = e.getSelection().getNative().toString().replace(/\s*/, '');
        const url = dotclear.msg.tag_url;
        dotclear.meta_editor_tag.addMeta(str);
        const href = `${url}/${str}`;
        const link = `<a href="${$.stripBaseURL(href)}">${str}</a>`;
        const element = CKEDITOR.dom.element.createFromHtml(link);
        e.insertElement(element);
      },
    });

    editor.ui.addButton('dcTags', {
      label: dotclear.msg.tag_title,
      command: 'dcTagsCommand',
      toolbar: 'insert',
      icon: `${this.path}icon.svg`,
    });
  },
});
