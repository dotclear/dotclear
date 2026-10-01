/*global jQuery, dotclear */
'use strict';

// Get locales and setting
Object.assign(dotclear.msg, dotclear.getData('theme_editor_msg'));
Object.assign(dotclear, dotclear.getData('dotclear_colorsyntax'));

dotclear.ready(() => {
  // DOM ready and content loaded

  // Add message container
  const msg = dotclear.htmlToNode('<p id="action-msg"></p>');
  document.querySelector('p.form-buttons')?.before(msg);

  // Get delete (reset) button
  const delete_btn = document.querySelector('#file-form input[name="delete"]');

  // Confirm for deleting current file
  delete_btn?.addEventListener('click', (event) => dotclear.confirm(dotclear.msg.confirm_reset_file, event));

  // Check Codemirror instance event as Textarea is not updated until Codemirror lose focus
  if (dotclear.colorsyntax && dotclear.codemirror?.editor) {
    const content = document.querySelector('#file_content');
    dotclear.codemirror.editor.on('change', () => {
      if (dotclear.codemirror.editor.isClean() || content.value === dotclear.codemirror.editor.getValue())
        content.classList.remove('cm_dirty');
      else content.classList.add('cm_dirty');
    });
  }
});
