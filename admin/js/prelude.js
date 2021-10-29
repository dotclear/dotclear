/*global $ */
'use strict';

$(() => {
  if ($('#prelude').length > 0) {
    $('#prelude a')
      .addClass('hidden')
      .on('focus', () => {
        $('#prelude a').removeClass('hidden');
        $('#wrapper, #help-button, #collapser').addClass('with-prelude');
      });

    $('body').on('click', '#prelude a[href="#help"]', (e) => {
      e.preventDefault();
      $('#help-button a').trigger('focus');
    });
  }
});
