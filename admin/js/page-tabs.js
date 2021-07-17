/*global $, getData */
'use strict';

$(function () {
  const page_tabs = getData('page_tabs');
  $.pageTabs(page_tabs.default);
});
