/*global $, dotclear */
'use strict';

$(() => {
  $('.checkboxes-helpers').each(function () {
    dotclear.checkboxesHelpers(this, undefined, '#form-users input[type="checkbox"]', '#form-users #do-action');
  });
  dotclear.condSubmit('#form-users input[type="checkbox"]', '#form-users #do-action');
  dotclear.responsiveCellHeaders(document.querySelector('#form-users table'), '#form-users table', 1);
  $('#form-users').submit(function () {
    const action = $(this).find('select[name="action"]').val();
    let user_ids = [];
    let nb_posts = [];
    let i;
    let msg_cannot_delete = false;

    $(this)
      .find('input[name="users[]"]')
      .each(function () {
        user_ids.push(this);
      });
    $(this)
      .find('input[name="nb_post[]"]')
      .each(function () {
        nb_posts.push(this.value);
      });

    if (action == 'deleteuser') {
      for (i = 0; i < user_ids.length; i++) {
        if (nb_posts[i] > 0 && user_ids[i].checked == true) {
          msg_cannot_delete = true;
          user_ids[i].checked = false;
        }
      }
      if (msg_cannot_delete == true) {
        window.alert(dotclear.msg.cannot_delete_users);
      }
    }

    let selectfields = 0;
    for (i = 0; i < user_ids.length; i++) {
      selectfields += user_ids[i].checked;
    }

    if (selectfields == 0) {
      return false;
    }

    if (action == 'deleteuser') {
      return window.confirm(dotclear.msg.confirm_delete_user.replace('%s', $('input[name="users[]"]:checked').length));
    }

    return true;
  });
});
