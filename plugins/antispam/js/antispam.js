/*global $, dotclear */
'use strict';

Object.assign(dotclear.msg, dotclear.getData('antispam'));

dotclear.ready(() => {
  // DOM ready and content loaded

  if ($('#filters-list').length) {
    $('#filters-list').sortable();
    $('#filters-list-form').on('submit', () => {
      const order = [];
      $('#filters-list tr td input.position').each(function () {
        order.push(this.name.replace(/^f_order\[([^\]]+)\]$/, '$1'));
      });
      $('input[name=filters_order]')[0].value = order.join(',');
      return true;
    });
    $('#filters-list tr td input.position').hide();
    $('#filters-list tr td.handle').addClass('handler');

    $('form input[type=submit][name=delete_all]').on('click', () => window.confirm(dotclear.msg.confirm_spam_delete));

    // Prepare mobile display for tables
    dotclear.responsiveCellHeaders(document.querySelector('#filters-list-form table'), '#filters-list-form table', 1, true);
  }
});
