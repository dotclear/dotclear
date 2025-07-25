/*global $, dotclear, confirmClosePage, codemirror_instance */
'use strict';

// Get locales and setting
Object.assign(dotclear.msg, dotclear.getData('theme_editor_msg'));
Object.assign(dotclear, dotclear.getData('dotclear_colorsyntax'));

dotclear.ready(() => {
  // DOM ready and content loaded

  // Cope with saving
  let msg = false;
  $('#file-form input[name="write"]').on('click', function (e) {
    const f = this.form;

    const data = {
      file_content: dotclear.colorsyntax ? codemirror_instance.editor.getValue() : $(f).find('#file_content').get(0).value,
      xd_check: $(f).find('input[name="xd_check"]').get(0).value,
      write: 1,
    };

    if (!msg) {
      msg = $('<p class="info"></p>');
      $('#file_content').parent().after(msg);
    }

    msg.addClass('info').removeClass('error success');
    msg.text(dotclear.msg.saving_document);

    $.post(document.location.href, data, (res) => {
      const err = $(res).find('div.error li:first');
      if (err.length > 0) {
        msg.removeClass('info').addClass('error');
        msg.text(`${dotclear.msg.error_occurred} ${err.text()}`);
        return;
      }
      msg.removeClass('info').addClass('success');
      msg.text(dotclear.msg.document_saved);
      $('#file-chooser').empty();
      $(res).find('#file-chooser').children().appendTo('#file-chooser');
      $('input[name="delete"]').removeClass('hide');
      if (typeof dotclear.confirmClosePage.getCurrentForms === 'function') {
        dotclear.confirmClosePage.forms = [];
        dotclear.confirmClosePage.getCurrentForms();
      }
    });

    e.preventDefault();
  });

  // Confirm for deleting current file
  $('#file-form input[name="delete"]').on('click', () => window.confirm(dotclear.msg.confirm_reset_file));
});
