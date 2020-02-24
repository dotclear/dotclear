/*exported getData, isObject, mergeDeep, getCookie, setCookie, deleteCookie */
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

// Returns the cookie with the given name or false if not found
function getCookie(name) {
  let matches = document.cookie.match(new RegExp(
    "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
  ));
  return matches ? decodeURIComponent(matches[1]) : false;  // may be undefined rather than false?
}

// Set a new cookie
// usage: setCookie('user', 'John', {secure: true, 'expires': 60});
function setCookie(name, value, options = {}) {

  if (typeof options.expires === 'number') {
    // Cope with expires option given in number of days from now
      options.expires = new Date(Date.now() + options.expires * 864e5);
  }
  if (options.expires instanceof Date) {
    // Cope with expires option given as a Date object
    options.expires = options.expires.toUTCString();
  }

  let updatedCookie = encodeURIComponent(name) + "=" + encodeURIComponent(value);

  for (let optionKey in options) {
    updatedCookie += "; " + optionKey;
    let optionValue = options[optionKey];
    if (optionValue !== true) {
      updatedCookie += "=" + optionValue;
    }
  }

  document.cookie = updatedCookie;
}

// Delete a cookie
function deleteCookie(name) {
  setCookie(name, "", {
    'expires': -1
  });
}
