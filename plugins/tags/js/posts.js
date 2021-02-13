/*global $, dotclear, mergeDeep, getData */
'use strict';

mergeDeep(dotclear.msg, getData('posts_tags_msg'));

$(function () {
  $('#tag_delete').on('submit', function () {
    return window.confirm(dotclear.msg.confirm_tag_delete);
  });
});
