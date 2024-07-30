/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  // Get some DATA
  Object.assign(dotclear.msg, dotclear.getData('filter_controls'));

  const filter_reset_url = dotclear.getData('filter_reset_url');
  const reset_url = dotclear.isEmptyObject(filter_reset_url) ? '?' : filter_reset_url;

  // Create details container

  const form = document.getElementById('filters-form');

  const details = document.createElement('details');
  details.id = 'filter-details';

  const summary = document.createElement('summary');
  summary.innerText = dotclear.msg.filter_posts_list;
  summary.id = 'filter-control';
  summary.classList.add('form-control');

  details.appendChild(summary);
  form.parentNode.insertBefore(details, form);
  details.appendChild(form);

  if (dotclear.msg.show_filters) {
    details.setAttribute('open', 'open');
    summary.classList.add('open');
    summary.innerText = dotclear.msg.cancel_the_filter;
  } else {
    details.removeAttribute('open');
    summary.classList.remove('open');
    summary.innerText = dotclear.msg.filter_posts_list;
  }

  if (dotclear.getData('filter_options').auto_filter) {
    const submits = document.querySelectorAll('#filters-form input[type="submit"]');
    for (const submit of submits) {
      submit.parentNode.style.display = 'none';
    }
    const selects = document.querySelectorAll('#filters-form select');
    for (const select of selects) {
      select.addEventListener('input', () => {
        form.submit();
      });
    }
    const inputs = document.querySelectorAll('#filters-form input:not([type="submit"])');
    for (const input of inputs) {
      input.addEventListener('focusin', () => {
        input.dataset.value = input.value;
      });
      input.addEventListener('focusout', () => {
        if (input.dataset.value !== input.value) {
          form.submit();
        }
      });
    }
  }

  // Deal with enter key on filters form : every form element will be filtered but Cancel button
  dotclear.enterKeyInForm('#filters-form', '#filters-form input[type="submit"]', '#filter-control');

  // Cope with open/close on details (close = reset all filters if not already the case)
  summary.addEventListener('click', () => {
    if (summary.classList.contains('open')) {
      if (dotclear.msg.show_filters) {
        if (reset_url !== '?' && !window.location.href.endsWith(reset_url)) window.location.href = reset_url;
        return true;
      }
      if (reset_url !== '?' && !window.location.href.endsWith(reset_url)) window.location.href = reset_url;
      summary.classList.remove('open');
      summary.innerText = dotclear.msg.filter_posts_list;
      return;
    }
    summary.classList.add('open');
    summary.innerText = dotclear.msg.cancel_the_filter;
  });

  const save = document.getElementById('filter-options-save');
  save?.addEventListener('click', () => {
    // Save list options (via services)
    dotclear.jsonServicesPost(
      'setListsOptions',
      (data) => {
        window.alert(data.msg);
      },
      {
        id: document.getElementById('filters-options-id')?.value,
        sort: document.getElementById('sortby')?.value,
        order: document.getElementById('order')?.value,
        nb: document.getElementById('nb')?.value,
      },
      (error) => {
        window.alert(error);
      },
    );
  });
});
