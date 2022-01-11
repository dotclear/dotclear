/*global $, dotclear */
'use strict';

$(() => {
  // expend theme info
  $('.module-sshot')
    .not('.current-theme .module-sshot')
    .each(function () {
      $(this)
        .children('img')
        .on('click', function () {
          // Click on theme thumbnail
          const details_element = $(this).parent().parent().children('details');
          details_element.attr('open', details_element.attr('open') ? null : 'true');
        });
    });

  $('.modules-search').each(function () {
    const m_search = $(this).find('input[name=m_search]');
    const m_submit = $(this).find('input[type=submit]');

    m_submit.attr('disabled', m_search.val().length < 2);
    if (m_search.val().length < 2) {
        m_submit.addClass('disabled');
      }else{
        m_submit.removeClass('disabled');
      }

    m_search.keyup(() => {
      m_submit.attr('disabled', m_search.val().length < 2);
      if (m_search.val().length < 2) {
          m_submit.addClass('disabled');
        }else{
          m_submit.removeClass('disabled');
        }
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
        const module = mvalues[1];

        // confirm delete
        if (action == 'delete') {
          return window.confirm(dotclear.msg.confirm_delete_theme.replace('%s', module));
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
          return window.confirm(dotclear.msg.confirm_delete_themes);
        }

        // action on one module
      }

      return true;
    });
  });
});
