/*global getData, isObject, mergeDeep */
/*exported dotclear */
'use strict';

/**
 * Initialize dotclear global object and add some useful tools when common.js is not loaded
 * Warning: common.js MUST NO BE LOADED before of after this file
 */

/* Dotclear common object
-------------------------------------------------------- */
const dotclear = {};

/* On document ready
-------------------------------------------------------- */
document.addEventListener("DOMContentLoaded", function() {
  // Function's aliases (from prepend.js)
  if (typeof getData === 'function') {
    dotclear.getData = dotclear.getData || getData;
  }
  if (typeof isObject === 'function') {
    dotclear.isObject = dotclear.isObject || isObject;
  }
  if (typeof mergeDeep === 'function') {
    dotclear.mergeDeep = dotclear.mergeDeep || mergeDeep;
  }
});
