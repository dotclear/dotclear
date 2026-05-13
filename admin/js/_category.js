/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  dotclear.hideLockable();

  if (typeof dotclear.ToolBar === 'function') {
    const tbCategory = new dotclear.ToolBar(document.getElementById('cat_desc'));
    tbCategory.draw('xhtml');
  }
});
