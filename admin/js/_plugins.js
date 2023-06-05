/*global $, dotclear */
'use strict';

dotclear.dbStoreUpdate = (store, url) => {
  if (url.length) {
    const params = {
      f: 'checkStoreUpdate',
      xd_check: dotclear.nonce,
      store,
    };
    $.post('services.php', params, (data) => {
      if ($('rsp[status=failed]', data).length === 0 && $('rsp>update', data).attr('new') == 1) {
        if ($('rsp>update', data).attr('check') == 1) {
          const nb = Number($('rsp>update', data).attr('nb'));
          if (nb) {
            $('#force-checking').replaceWith(
              '<p class="info"><a href="' + url + '" title="' + $('rsp>update', data).attr('ret') + '">' + $('rsp>update', data).attr('ret') + '</a></p>'
            );
          }
        } else {
          $('#force-checking p').prepend(
            '<span class="info">' + $('rsp>update', data).attr('ret') + '</span> '
          );
        }
      }
    });
  }
};

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

  $('.modules-search').each(function () {
    const m_search = $(this).find('input[name=m_search]');
    const m_submit = $(this).find('input[type=submit]');

    m_submit.attr('disabled', m_search.val().length < 2);
    if (m_search.val().length < 2) {
      m_submit.addClass('disabled');
    } else {
      m_submit.removeClass('disabled');
    }

    m_search.on('keyup', () => {
      m_submit.attr('disabled', m_search.val().length < 2);
      if (m_search.val().length < 2) {
        m_submit.addClass('disabled');
      } else {
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
            if (dotclear.debug) {
              alert(dotclear.msg.no_selection);
            }
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

  dotclear.dbStoreUpdate('plugins', dotclear.getData('module_update_url'));
});
