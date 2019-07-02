/*exported getData, isObject, mergeDeep */
'use strict';

var getData = getData || function(id, clear = true) {
  let data = {};
  // Read the JSON-formatted data from the DOM. (from https://mathiasbynens.be/notes/json-dom-csp)
  // To be use with: <script type="application/json" id="myid-data">{"key":value, …}</script>
  const element = document.getElementById(`${id}-data`);
  if (element) {
    try {
      data = JSON.parse(element.textContent);
      if (clear) {
        // Clear the element’s contents
        element.innerHTML = '';
      }
    } catch (e) {}
  }
  return data;
};

var isObject = isObject || function isObject(item) {
  return (item && typeof item === 'object' && !Array.isArray(item));
};

/**
 * Deep merge two objects.
 * @param target
 * @param ...sources
 */
var mergeDeep = mergeDeep || function mergeDeep(target, ...sources) {
  if (!sources.length) return target;
  const source = sources.shift();
  if (isObject(target) && isObject(source)) {
    for (const key in source) {
      if (isObject(source[key])) {
        if (!target[key]) Object.assign(target, { [key]: {} });
        mergeDeep(target[key], source[key]);
      } else {
        Object.assign(target, { [key]: source[key] });
      }
    }
  }
  return mergeDeep(target, ...sources);
};
