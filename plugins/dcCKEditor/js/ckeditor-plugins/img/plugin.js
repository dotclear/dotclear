/*global CKEDITOR, dotclear */
'use strict';

(function () {
  CKEDITOR.plugins.add('img', {
    init(editor) {
      editor.addCommand('dcImgCommand', new CKEDITOR.dialogCommand('imgDialog'));

      CKEDITOR.dialog.add('imgDialog', `${this.path}dialogs/img.js`);

      editor.ui.addButton('img', {
        label: dotclear.msg.img_title,
        command: 'dcImgCommand',
        toolbar: 'insert',
        icon: `${this.path}icons/img.png`,
      });
    },
  });
})();
