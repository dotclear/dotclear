/*global $, dotclear, confirmClosePage, getData, codemirror_instance */
'use strict';

// Get locales and setting
Object.assign(dotclear.msg, getData('theme_editor_msg'));
Object.assign(dotclear, getData('dotclear_colorsyntax'));

$(function() {
  // Cope with saving
  let msg = false;
  $('#file-form input[name="write"]').on('click', function(e) {
    const f = this.form;

    const data = {
      file_content: (!dotclear.colorsyntax ? $(f).find('#file_content').get(0).value : codemirror_instance.editor.getValue()),
      xd_check: $(f).find('input[name="xd_check"]').get(0).value,
      write: 1
    };

    if (msg == false) {
      msg = $('<p style="font-weight:bold; color:red;"></p>');
      $('#file_content').parent().after(msg);
    }

    msg.text(dotclear.msg.saving_document);

    $.post(document.location.href, data, function(res) {
      const err = $(res).find('div.error li:first');
      if (err.length > 0) {
        msg.text(dotclear.msg.error_occurred + ' ' + err.text());
        return;
      } else {
        msg.text(dotclear.msg.document_saved);
        $('#file-chooser').empty();
        $(res).find('#file-chooser').children().appendTo('#file-chooser');

        if (typeof confirmClosePage.getCurrentForms === 'function') {
          confirmClosePage.forms = [];
          confirmClosePage.getCurrentForms();
        }
      }
    });

    e.preventDefault();
  });

  // Confirm for deleting current file
  $('#file-form input[name="delete"]').on('click', function() {
    return window.confirm(dotclear.msg.confirm_reset_file);
  });

});
