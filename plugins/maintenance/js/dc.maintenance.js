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
      const params = {
        f: 'dcMaintenanceStep',
        xd_check: dotclear.nonce,
        task: $(box).attr('id'),
        code,
      };
      $.post('services.php', params, (data) => {
        if ($('rsp[status=failed]', data).length > 0) {
          $('.step-msg', box).text($('rsp', data).text());
          $('.step-wait', box).remove();
          $('.step-back', box).show();
        } else {
          $('.step-msg', box).text($('rsp>step', data).attr('title'));
          const code = $('rsp>step', data).attr('code');
          if (code > 0) {
            dcMaintenanceStep(box, code);
          } else {
            $('#content h2').after($('<div/>').addClass('success').append($('.step-msg', box)));
            $('.step-wait', box).remove();
            $('.step-back', box).show();
          }
        }
      });
    }
  });
});
