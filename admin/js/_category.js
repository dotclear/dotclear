/*global $, dotclear, jsToolBar */
'use strict';

$(function() {
  dotclear.hideLockable();

  if (typeof jsToolBar !== 'undefined' && $.isFunction(jsToolBar)) {
    const tbCategory = new jsToolBar(document.getElementById('cat_desc'));
    tbCategory.draw('xhtml');
  }
});
