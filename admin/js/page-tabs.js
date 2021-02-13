/*global $, getData */
'use strict';

const page_tabs = getData('page_tabs');
$(function () {
  $.pageTabs(page_tabs.default);
});
