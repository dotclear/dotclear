/*global $, dotclear */
'use strict';

dotclear.mergeDeep(dotclear.msg, dotclear.getData('posts_tags_msg'));

$(() => {
  $('#tag_delete').on('submit', () => window.confirm(dotclear.msg.confirm_tag_delete));
});
