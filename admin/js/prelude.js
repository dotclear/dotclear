/*global $ */
'use strict';

$(function () {
  if ($('#prelude').length > 0) {
    $('#prelude a')
      .addClass('hidden')
      .on('focus', function () {
        $('#prelude a').removeClass('hidden');
        $('#wrapper, #help-button, #collapser').addClass('with-prelude');
      });

    $('body').on('click', '#prelude a[href="#help"]', function (e) {
      e.preventDefault();
      $('#help-button a').trigger('focus');
    });
  }
});
