/*global $, dotclear */
'use strict';

$(function () {
  const page_tabs = dotclear.getData('page_tabs');
  $.pageTabs(page_tabs.default);
});
