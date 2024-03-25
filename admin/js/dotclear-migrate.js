/*global dotclear */
/*exported storeLocalData, dropLocalData, readLocalData, getData, isObject, mergeDeep, trimHtml */
'use strict';

// jQuery extensions

/**
 * @name jQuery
 * @class
 * @typedef {jQuery} $
 * @external "jQuery"
 */

/**
 * @name fn
 * @class
 * @memberOf jQuery
 * @external "jQuery.fn"
 */

/**
 * Enables the shift click of a list of elements
 *
 * @function
 * @memberof    external:"jQuery.fn"
 *
 * @deprecated    use dotclear.enableShiftClick(<selector>)
 */
$.fn.enableShiftClick = function () {
  console.warn('Dotclear: $.mergeDeep() is deprecated. Use dotclear.enableShiftClick().');
  const group = this;
  group.data('esc_lastclicked', '');
  group.data('esc_lastclickedstatus', false);

  this.on('click', function (event) {
    if (event.shiftKey) {
      if (group.data('esc_lastclicked') !== '') {
        let range;
        const trparent = $(this).parents('tr');
        const id = `#${group.data('esc_lastclicked')}`;
        range = trparent.nextAll(id).length == 0 ? trparent.prevUntil(id) : trparent.nextUntil(id);
        dotclear.setChecked(range.find('input[type=checkbox]').get(), group.data('esc_lastclickedstatus'));
        this.checked = group.data('esc_lastclickedstatus');
      }
    } else {
      group.data('esc_lastclicked', $(this).parents('tr')[0].id);
      group.data('esc_lastclickedstatus', this.checked);
    }
    return true;
  });
};

// Vanilla

/* Obsolete global functions, for compatibility purpose, will be removed in a future release */
const storeLocalData = (id, value = null) => {
  console.warn('Dotclear: storeLocalData() is deprecated. Use dotclear.storeLocalData().');
  dotclear.storeLocalData(id, value);
};
const dropLocalData = (id) => {
  console.warn('Dotclear: dropLocalData() is deprecated. Use dotclear.dropLocalData().');
  dotclear.dropLocalData(id);
};
const readLocalData = (id) => {
  console.warn('Dotclear: readLocalData() is deprecated. Use dotclear.readLocalData().');
  return dotclear.readLocalData(id);
};
const getData = (id, clear = true, remove = false) => {
  console.warn('Dotclear: getData() is deprecated. Use dotclear.getData().');
  return dotclear.getData(id, clear, remove);
};
const isObject = (item) => {
  console.warn('Dotclear: isObject() is deprecated. Use dotclear.isObject().');
  return dotclear.isObject(item);
};
const mergeDeep = (target, ...sources) => {
  console.warn('Dotclear: mergeDeep() is deprecated. Use dotclear.mergeDeep().');
  return dotclear.mergeDeep(target, ...sources);
};
