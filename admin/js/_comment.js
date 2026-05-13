/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  if (typeof dotclear.ToolBar === 'function') {
    const tbComment = new dotclear.ToolBar(document.getElementById('comment_content'));
    tbComment.draw('xhtml');
  }

  // Confirm backup deletion
  document.querySelector('#comment-form input[name="delete"]').addEventListener('click', (event) => {
    if (globalThis.confirm(dotclear.msg.confirm_delete_comment)) return true;
    event.preventDefault();
    return false;
  });
});
