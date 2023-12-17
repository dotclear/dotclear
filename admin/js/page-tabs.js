/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  $.pageTabs(dotclear.getData('page_tabs').default);
});
