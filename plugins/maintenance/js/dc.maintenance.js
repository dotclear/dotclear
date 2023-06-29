/*global $, dotclear */
'use strict';

Object.assign(dotclear.msg, dotclear.getData('maintenance'));

$(() => {
  $('.step-box').each(function () {
    const code = $('input[name=code]', this).val();
    $('.step-submit', this).remove();
    $('.step-back', this).hide();
    $('.step-msg', this).after($('<p>').addClass('step-wait').text(dotclear.msg.wait));

    dcMaintenanceStep(this, code);

    function dcMaintenanceStep(box, code) {
      dotclear.jsonServicesPost(
        'dcMaintenanceStep',
        (data) => {
          $('.step-msg', box).text(data.title);
          const next = data.code;
          if (next > 0) {
            dcMaintenanceStep(box, next);
            return;
          }
          $('#content h2').after($('<div/>').addClass('success').append($('.step-msg', box)));
          $('.step-wait', box).remove();
          $('.step-back', box).show();
        },
        {
          task: $(box).attr('id'),
          code,
        },
        (error) => {
          $('.step-msg', box).text(error);
          $('.step-wait', box).remove();
          $('.step-back', box).show();
        },
      );
    }
  });
});
