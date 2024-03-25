/*global dotclear */
/*exported getData, isObject, mergeDeep, getCookie, setCookie, deleteCookie */
'use strict';

/**
 * @deprecated use dotclear.getData
 */
var getData =
  getData ||
  function getData(id, clear = true, remove = false) {
    console.warn('getData() is deprecated. Use dotclear.getData');
    return dotclear.getData(id, clear, remove);
  };

/**
 * @deprecated use dotclear.isObject
 */
var isObject =
  isObject ||
  function isObject(item) {
    console.warn('isObject() is deprecated. Use dotclear.isObject()');
    return dotclear.isObject(item);
  };

/**
 * @deprecated use dotclear.mergeDeep
 */
var mergeDeep =
  mergeDeep ||
  function mergeDeep(target, ...sources) {
    console.warn('mergeDeep() is deprecated. Use dotclear.mergeDeep()');
    return dotclear.mergeDeep(target, ...sources);
  };

/**
 * @deprecated use dotclear.getCookie
 */
var getCookie =
  getCookie ||
  function getCookie(name) {
    console.warn('getCookie() is deprecated. Use dotclear.getCookie()');
    return dotclear.getCookie(name);
  };

/**
 * @deprecated use dotclear.setCookie
 */
var setCookie =
  setCookie ||
  function setCookie(name, value, options = {}) {
    console.warn('setCookie() is deprecated. Use dotclear.setCookie()');
    return dotclear.setCookie(name, value, options);
  };

/**
 * @deprecated use dotclear.deleteCookie
 */
var deleteCookie =
  deleteCookie ||
  function deleteCookie(name) {
    console.warn('deleteCookie() is deprecated. Use dotclear.deleteCookie()');
    return dotclear.deleteCookie(name);
  };
