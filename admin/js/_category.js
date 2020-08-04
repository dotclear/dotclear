/*global $, dotclear, jsToolBar */
'use strict';

$(function() {
  dotclear.hideLockable();

  if (typeof jsToolBar === 'function') {
    const tbCategory = new jsToolBar(document.getElementById('cat_desc'));
    tbCategory.draw('xhtml');
  }
});
