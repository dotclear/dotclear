/*global $, dotclear */
'use strict';

Object.assign(dotclear.msg, dotclear.getData('ie_import_flat_msg'));

$(() => {
  $('#up_single_file').on('change', function () {
    if (this.value != '') {
      $('#public_single_file').val('');
    }
  });
  $('#public_single_file').on('change', function () {
    if (this.value != '') {
      $('#up_single_file').val('');
    }
  });
  $('#up_full_file').on('change', function () {
    if (this.value != '') {
      $('#public_full_file').val('');
    }
  });
  $('#public_full_file').on('change', function () {
    if (this.value != '') {
      $('#up_full_file').val('');
    }
  });
  $('#formfull').on('submit', () => window.confirm(dotclear.msg.confirm_full_import));
});
