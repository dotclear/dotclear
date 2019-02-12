/*global $, dotclear, datePicker */
'use strict';

$(function() {
  // Add datePicker if possible
  const media_dt = document.getElementById('media_dt');
  if (media_dt != undefined) {
    const post_dtPick = new datePicker(media_dt);
    post_dtPick.img_top = '1.5em';
    post_dtPick.draw();
  }

  // Preview media
  $('.modal-image').magnificPopup({
    type: 'image'
  });

  // Display zip file content
  $('#file-unzip').each(function() {
    const a = document.createElement('a');
    const mediaId = $(this).find('input[name=id]').val();
    const This = $(this);

    a.href = '#';
    $(a).text(dotclear.msg.zip_file_content);
    This.before(a);
    $(a).wrap('<p></p>');

    $(a).click(function() {
      $.get('services.php', {
        f: 'getZipMediaContent',
        id: mediaId
      }, function(data) {
        const rsp = $(data).children('rsp')[0];

        if (rsp.attributes[0].value == 'ok') {
          const div = document.createElement('div');
          const list = document.createElement('ul');
          let expanded = false;

          $(div).css({
            overflow: 'auto',
            margin: '1em 0',
            padding: '1px 0.5em'
          });
          $(div).addClass('color-div');
          $(div).append(list);
          This.before(div);
          $(a).hide();
          $(div).before('<h3>' + dotclear.msg.zip_file_content + '</h3>');

          $(rsp).find('file').each(function() {
            $(list).append('<li>' + $(this).text() + '</li>');
            if ($(div).height() > 200 && !expanded) {
              $(div).css({
                height: '200px'
              });
              expanded = true;
            }
          });
        } else {
          window.alert($(rsp).find('message').text());
        }
      });
      return false;
    });
  });

  // Confirm for inflating in current directory
  $('#file-unzip').submit(function() {
    if ($(this).find('#inflate_mode').val() == 'current') {
      return window.confirm(dotclear.msg.confirm_extract_current);
    }
    return true;
  });

  // Confirm for deleting current medoa
  $('#delete-form input[name="delete"]').click(function() {
    let m_name = $('#delete-form input[name="remove"]').val();
    return window.confirm(dotclear.msg.confirm_delete_media.replace('%s', m_name));
  });

  // Get current insertion settings
  $('#save_settings').submit(function() {
    $('input[name="pref_src"]').val($('input[name="src"][type=radio]:checked').attr('value'));
    $('input[name="pref_alignment"]').val($('input[name="alignment"][type=radio]:checked').attr('value'));
    $('input[name="pref_insertion"]').val($('input[name="insertion"][type=radio]:checked').attr('value'));
    $('input[name="pref_legend"]').val($('input[name="legend"][type=radio]:checked').attr('value'));
  });

  // Set focus if in popup mode
  $('#media-insert-form :input:visible:enabled:checked:first, #media-insert-form :input:visible:enabled:first').focus();

  // Deal with enter key on media insert popup form : every form element will be filtered but Cancel button
  dotclear.enterKeyInForm('#media-insert-form', '#media-insert-ok', '#media-insert-cancel');
});
