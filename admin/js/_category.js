/*global $, dotclear, jsToolBar */
'use strict';

$(() => {
  dotclear.hideLockable();

  if (typeof jsToolBar === 'function') {
    const tbCategory = new jsToolBar(document.getElementById('cat_desc'));
    tbCategory.draw('xhtml');
  }
});
