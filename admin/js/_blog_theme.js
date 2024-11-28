/*global jQuery, dotclear */
'use strict';

dotclear.dbStoreUpdate = (/** @type {any} */ store, /** @type {string} */ url) => {
  if (url.length) {
    dotclear.jsonServicesPost(
      'checkStoreUpdate',
      (/** @type {{ new: boolean; check: boolean; nb: number; ret: string; }} */ data) => {
        if (data.new) {
          if (data.check) {
            if (data.nb) {
              const form = document.getElementById('force-checking');
              form?.replaceWith(
                dotclear.htmlToNode(`<p class="info"><a href="${url}" title="${data.ret}">${data.ret}</a></p>`),
              );
            }
          } else {
            const target = document.querySelector('#force-checking p');
            target?.prepend(dotclear.htmlToNode(`<span class="info">${data.ret}</span>`));
          }
        }
      },
      { store },
    );
  }
};

dotclear.ready(() => {
  // DOM ready and content loaded

  // Expand theme info helper
  for (const theme of document.querySelectorAll('.module-sshot:not(.current-theme .module-sshot)')) {
    const img = theme.querySelector('img');
    img?.addEventListener('click', (event) => {
      // Click on theme thumbnail
      const details = event.currentTarget.parentNode.parentNode.querySelector('details');
      details.open = details.open ? null : 'true';
    });
  }

  // Theme search helper
  const search = document.querySelector('.modules-search');
  if (search) {
    const searchInput = search.querySelector(':scope input[name=m_search]');
    const searchSubmit = search.querySelector(':scope input[type=submit]');

    const condSubmit = () => {
      if (searchInput.value.length < 2) {
        searchSubmit.classList.add('disabled');
        searchSubmit.setAttribute('disabled', 'true');
      } else {
        searchSubmit.classList.remove('disabled');
        searchSubmit.removeAttribute('disabled');
      }
    };

    condSubmit();
    searchInput?.addEventListener('keyup', () => {
      condSubmit();
    });
  }

  // checkboxes selection
  for (const helper of document.querySelectorAll('.checkboxes-helpers')) {
    dotclear.checkboxesHelpers(helper);
  }

  // actions tests
  for (const action of document.querySelectorAll('.modules-form-actions')) {
    const rxActionType = /^[^[]+/;
    const rxActionValue = /([^[]+)\]$/;
    const checkboxes = action.querySelectorAll(':scope input[type=checkbox]');

    for (const submit of action.querySelectorAll(':scope input[type=submit]')) {
      submit?.addEventListener('click', (event) => {
        const keyword = event.currentTarget?.name;
        if (!keyword) {
          return true;
        }
        const maction = keyword.match(rxActionType);
        const action = maction ? maction[0] : '';
        const mvalues = keyword.match(rxActionValue);

        // action on multiple modules
        if (mvalues) {
          const module = mvalues[1];

          // confirm delete
          if (action === 'delete') {
            return window.confirm(dotclear.msg.confirm_delete_theme.replace('%s', module));
          }
        } else {
          let checked = false;

          // check if there is checkboxes in form
          if (checkboxes.length > 0) {
            // check if there is at least one checkbox checked
            for (const checkbox of checkboxes) {
              if (checkbox.checked) {
                checked = true;
                break;
              }
            }
            if (!checked) {
              if (dotclear.debug) {
                alert(dotclear.msg.no_selection);
              }
              event.preventDefault();
              return false;
            }
          }

          // confirm delete
          if (action === 'delete') {
            return window.confirm(dotclear.msg.confirm_delete_themes);
          }
        }

        return true;
      });
    }
  }

  // Theme preview
  for (const preview of document.querySelectorAll('.theme-preview')) {
    const url = preview.href;
    if (url) {
      const modal = preview.classList.contains('modal');
      // Check if admin and blog have same protocol (ie not mixed-content)
      if (modal && window.location.protocol === url.substring(0, window.location.protocol.length)) {
        // Open preview in a modal iframe
        jQuery(preview).magnificPopup({
          type: 'iframe',
          iframe: {
            patterns: {
              dotclear_preview: {
                index: url,
                src: url,
              },
            },
          },
        });
      } else {
        // If has not modal class, the preview is cope by direct link with target="blank" in HTML
        if (modal) {
          // Open preview on antother window
          preview.addEventListener('click', (event) => {
            event.preventDefault();
            window.open(event.currentTarget.href);
          });
        }
      }
    }
  }

  // Prevent history back if currently previewing Theme (with magnificPopup)
  history.pushState(null, '', null);
  window.addEventListener('popstate', () => {
    if (document.querySelector('.mfp-ready')) {
      // Prevent history back
      history.go(1);
      // Close current preview
      jQuery.magnificPopup.close();
    }
  });

  dotclear.dbStoreUpdate('themes', dotclear.getData('module_update_url'));
});
