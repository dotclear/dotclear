/*global CKEDITOR, dotclear */
'use strict';

CKEDITOR.dialog.add('imgDialog', function (editor) {
  return {
    title: dotclear.msg.img_title,
    minWidth: 400,
    minHeight: 100,
    contents: [
      {
        id: 'main-tab',
        elements: [
          {
            id: 'url',
            type: 'text',
            label: 'URL',
            validate: CKEDITOR.dialog.validate.notEmpty(dotclear.msg.url_cannot_be_empty),
          },
        ],
      },
    ],
    onOk() {
      const src = this.getValueOf('main-tab', 'url');
      const img = editor.document.createElement('img');
      img.setAttribute('src', src);
      img.setAttribute('alt', src);
      editor.insertElement(img);
    },
  };
});
