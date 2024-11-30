/*global jQuery, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  if (jQuery.fn.nestedSortable !== undefined) {
    jQuery('#categories ul li').css('cursor', 'move');
    jQuery('#save-set-order').prop('disabled', true).addClass('disabled');
    jQuery('#categories ul').nestedSortable({
      listType: 'ul',
      items: 'li',
      placeholder: 'placeholder',
      update() {
        jQuery('#categories_order').attr('value', JSON.stringify(jQuery('#categories ul').nestedSortable('toArray')));
        jQuery('#save-set-order').prop('disabled', false).removeClass('disabled');
      },
    });
  }

  const deleteButtons = document.querySelectorAll('input[name^="delete"]');
  const parents = (target, selector) => {
    const parents = [];
    let element = target.parentNode;
    while (element && element !== document) {
      if (!selector || element.matches(selector)) {
        parents.push(element);
      }
      element = element.parentNode;
    }
    return parents;
  };
  for (const deleteButton of deleteButtons) {
    deleteButton.addEventListener('click', (event) => {
      const lines = parents(event.currentTarget, 'li');
      if (lines.length < 1) return false;
      const category = lines[0].querySelector('.cat-title a').textContent;

      return dotclear.confirm(dotclear.msg.confirm_delete_category.replace('%s', category), event);
    });
  }

  document
    .querySelector('input[name="reset"]')
    ?.addEventListener('click', (event) => dotclear.confirm(dotclear.msg.confirm_reorder_categories, event));
});
