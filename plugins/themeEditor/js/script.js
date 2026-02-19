/*global jQuery, dotclear, confirmClosePage, codemirror_instance */
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

  // Cope with saving
  document.querySelector('#file-form input[name="write"]')?.addEventListener('click', (event) => {
    const form = event.currentTarget.form;

    const content = form.querySelector('#file_content');
    const xd_check = form.querySelector('input[name="xd_check"]');

    const data = {
      file_content: dotclear.colorsyntax ? codemirror_instance.editor.getValue() : content.value,
      xd_check: xd_check.value,
      write: 1,
    };

    msg.classList.add('info');
    msg.classList.remove('error', 'success');
    msg.textContent = dotclear.msg.saving_document;

    jQuery.post(document.location.href, data, (res) => {
      // res is a string with all html code of displayed page (after saving file)
      const err = jQuery(res).find('div.error li:first');

      if (err.length > 0) {
        msg.classList.remove('info');
        msg.classList.add('error');
        msg.textContent = `dotclear.msg.error_occurred ${err.text()}`;

        return;
      }

      msg.classList.remove('info');
      msg.classList.add('success');
      msg.textContent = dotclear.msg.document_saved;

      const chooser = document.querySelector('#file-chooser');
      chooser.replaceChildren();

      jQuery(res).find('#file-chooser').children().appendTo('#file-chooser');

      // Show delete (reset) button
      delete_btn.classList.remove('hide');

      // Remove cm_dirty class from textarea (not removed by Codemirror)
      content.classList.remove('cm_dirty');

      if (typeof dotclear.confirmClosePage.getCurrentForms === 'function') {
        dotclear.confirmClosePage.forms = [];
        dotclear.confirmClosePage.getCurrentForms();
      }
    });

    event.preventDefault();
  });
});
