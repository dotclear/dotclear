/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  // Preview media
  $('.modal-image').magnificPopup({
    type: 'image',
  });

  // Display zip file content
  $('#file-unzip').each(function () {
    const a = document.createElement('a');
    const mediaId = $(this).find('input[name=id]').val();
    const This = $(this);

    a.href = '#';
    $(a).text(dotclear.msg.zip_file_content);
    This.before(a);
    $(a).wrap('<p></p>');

    $(a).on('click', () => {
      dotclear.jsonServicesGet(
        'getZipMediaContent',
        (data) => {
          const div = document.createElement('div');
          const list = document.createElement('ul');
          let expanded = false;

          $(div).css({
            overflow: 'auto',
            margin: '1em 0',
            padding: '1px 0.5em',
          });
          $(div).addClass('fieldset');
          $(div).append(list);
          This.before(div);
          $(a).hide();
          $(div).before(`<h3>${dotclear.msg.zip_file_content}</h3>`);

          for (const elt in data) {
            $(list).append(`<li>${elt}</li>`);
            if ($(div).height() > 200 && !expanded) {
              $(div).css({
                height: '200px',
              });
              expanded = true;
            }
          }
        },
        { id: mediaId },
        (error) => {
          window.alert(error);
        },
      );
      return false;
    });
  });

  // Confirm for inflating in current directory
  $('#file-unzip').on('submit', function (event) {
    if ($(this).find('#inflate_mode').val() === 'current') {
      if (window.confirm(dotclear.msg.confirm_extract_current)) return true;
      event.preventDefault();
      return false;
    }
    return true;
  });

  // Confirm for deleting current media
  $('#delete-form input[name="delete"]').on('click', () => {
    const m_name = $('#delete-form input[name="remove"]').val();
    return window.confirm(dotclear.msg.confirm_delete_media.replace('%s', m_name));
  });

  // Get current insertion settings
  $('#save_settings').on('submit', () => {
    $('input[name="pref_src"]').val($('input[name="src"][type=radio]:checked').attr('value'));
    $('input[name="pref_alignment"]').val($('input[name="alignment"][type=radio]:checked').attr('value'));
    $('input[name="pref_insertion"]').val($('input[name="insertion"][type=radio]:checked').attr('value'));
    $('input[name="pref_legend"]').val($('input[name="legend"][type=radio]:checked').attr('value'));
  });

  // Set focus if in popup mode
  $('#media-insert-form :input:visible:enabled:checked:first, #media-insert-form :input:visible:enabled:first').trigger(
    'focus',
  );

  // Deal with enter key on media insert popup form : every form element will be filtered but Cancel button
  dotclear.enterKeyInForm('#media-insert-form', '#media-insert-ok', '#media-insert-cancel');
});
