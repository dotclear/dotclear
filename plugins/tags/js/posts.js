/*global $, dotclear */
'use strict';

dotclear.mergeDeep(dotclear.msg, dotclear.getData('posts_tags_msg'));

$(function () {
  $('#tag_delete').on('submit', function () {
    return window.confirm(dotclear.msg.confirm_tag_delete);
  });
});
