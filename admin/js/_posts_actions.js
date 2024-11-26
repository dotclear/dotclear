/*global jQuery, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  const new_auth_id = jQuery('#new_auth_id');
  if (new_auth_id.length) {
    // Add jQuery autocomplete on user
    const usersList = dotclear.getData('users_list');
    new_auth_id.autocomplete(usersList, {
      delay: 1000,
      matchSubset: true,
      matchContains: true,
    });
  }

  const new_cat = document.getElementById('new_cat');
  if (new_cat) {
    // Hide complementary fields for new catagory
    const siblings = new_cat.parentNode.querySelectorAll(':not(#new_cat)');
    dotclear.toggleWithLegend(new_cat, siblings, {
      legend_click: true,
    });
  }

  dotclear.condSubmit('table.posts-list td input[type=checkbox]', 'input[type=submit]');
});
