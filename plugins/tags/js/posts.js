/*global dotclear */
'use strict';

dotclear.mergeDeep(dotclear.msg, dotclear.getData('posts_tags_msg'));

dotclear.ready(() => {
  // DOM ready and content loaded

  document.getElementById('tag_delete')?.addEventListener('submit', (event) => {
    if (window.confirm(dotclear.msg.confirm_tag_delete)) return true;
    event.preventDefault();
    return false;
  });
});
