/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready

  $.pageTabs(dotclear.getData('page_tabs').default);
});
