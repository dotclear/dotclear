/*exported dotclear, getData, isObject, mergeDeep, getCookie, setCookie, deleteCookie */
'use strict';

/* Dotclear common object */
var dotclear = dotclear || {};
Object.assign(dotclear, {
  /**
   * Gets application/json data (JSON format).
   * @param      {string}   id              element identifier
   * @param      {boolean}  [clear=true]    clear content
   * @param      {boolean}  [remove=false]  remove element
   * @return     {object}   data object
   */
  getData(id, clear = true, remove = false) {
    let data = {};
    // Read the JSON-formatted data from the DOM. (from https://mathiasbynens.be/notes/json-dom-csp)
    // To be use with: <script type="application/json" id="myid-data">{"key":value, …}</script>
    const element = document.getElementById(`${id}-data`);
    if (element) {
      try {
        data = JSON.parse(element.textContent);
        if (remove) {
          // Remove element
          element.remove();
        } else if (clear) {
          // Clear the element's contents
          element.innerHTML = '';
        }
      } catch (e) {}
    }
    return data;
  },

  isObject(item) {
    return item && typeof item === 'object' && !Array.isArray(item);
  },

  /**
   * Deep merge two objects.
   * @param target
   * @param ...sources
   */
  mergeDeep(target, ...sources) {
    if (!sources.length) return target;
    const source = sources.shift();
    if (this.isObject(target) && this.isObject(source)) {
      for (const key in source) {
        if (this.isObject(source[key])) {
          if (!target[key])
            Object.assign(target, {
              [key]: {},
            });
          this.mergeDeep(target[key], source[key]);
        } else {
          Object.assign(target, {
            [key]: source[key],
          });
        }
      }
    }
    return this.mergeDeep(target, ...sources);
  },

  // Returns the cookie with the given name or false if not found
  getCookie(name) {
    let matches = document.cookie.match(new RegExp(`(?:^|; )${name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1')}=([^;]*)`));
    return matches ? decodeURIComponent(matches[1]) : false; // may be undefined rather than false?
  },

  // Set a new cookie
  // usage: setCookie('user', 'John', {secure: true, 'expires': 60});
  setCookie(name, value, options = {}) {
    if (typeof options.expires === 'number') {
      // Cope with expires option given in number of days from now
      options.expires = new Date(Date.now() + options.expires * 864e5);
    }
    if (options.expires instanceof Date) {
      // Cope with expires option given as a Date object
      options.expires = options.expires.toUTCString();
    }

    let updatedCookie = `${encodeURIComponent(name)}=${encodeURIComponent(value)}`;

    for (let optionKey in options) {
      updatedCookie += `; ${optionKey}`;
      let optionValue = options[optionKey];
      if (optionValue !== true) {
        updatedCookie += `=${optionValue}`;
      }
    }

    // Add sameSite=Lax if not present in options
    if (options.sameSite === undefined) {
      updatedCookie += '; sameSite=Lax';
    }

    document.cookie = updatedCookie;
  },

  // Delete a cookie
  deleteCookie(name) {
    this.setCookie(name, '', {
      expires: -1,
    });
  },
});

// for compatibility

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
