/*global $, dotclear */
'use strict';

$(() => {
  // expand a module line
  $('table.modules.expandable tr.line').each(function () {
    $('td.module-name, th.module-name', this).toggleWithLegend($(this).next('.module-more'), {
      img_on_txt: dotclear.img_plus_txt,
      img_on_alt: dotclear.img_plus_alt,
      img_off_txt: dotclear.img_minus_txt,
      img_off_alt: dotclear.img_minus_alt,
      legend_click: true,
    });
  });

  // checkboxes selection
  $('.checkboxes-helpers').each(function () {
    dotclear.checkboxesHelpers(this);
  });

  // actions tests
  $('.modules-form-actions').each(function () {
    const rxActionType = /^[^\[]+/;
    const rxActionValue = /([^\[]+)\]$/;
    const checkboxes = $(this).find('input[type=checkbox]');

    // check if submit is a global action or one line action
    $('input[type=submit]', this).on('click', function () {
      const keyword = $(this).attr('name');
      if (!keyword) {
        return true;
      }
      const maction = keyword.match(rxActionType);
      const action = maction[0];
      const mvalues = keyword.match(rxActionValue);

      // action on multiple modules
      if (mvalues) {
        // confirm delete
        if (action == 'delete') {
          return window.confirm(dotclear.msg.confirm_delete_plugin.replace('%s', mvalues[1]));
        }
      } else {
        let checked = false;

        // check if there is checkboxes in form
        if (checkboxes.length > 0) {
          // check if there is at least one checkbox checked
          $(checkboxes).each(function () {
            if (this.checked) {
              checked = true;
            }
          });
          if (!checked) {
            //alert(dotclear.msg.no_selection);
            return false;
          }
        }

        // confirm delete
        if (action == 'delete') {
          return window.confirm(dotclear.msg.confirm_delete_plugins);
        }

        // action on one module
      }

      return true;
    });
  });
});
