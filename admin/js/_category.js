/*global $, dotclear, jsToolBar */
'use strict';

$(function() {
  dotclear.hideLockable();

  if ($.isFunction(jsToolBar)) {
    const tbCategory = new jsToolBar(document.getElementById('cat_desc'));
    tbCategory.draw('xhtml');
  }
});
