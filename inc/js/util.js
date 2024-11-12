/*exported dotclear */
'use strict';

/* Dotclear common object */
var dotclear = dotclear || {};
Object.assign(dotclear, {
  /**
   * Wait for page fully loaded and then fire callback
   *
   * Instead of:
   * - document.addEventListener('load', fn()); // Vanilla JS flavor
   * - $(fn()); // jQuery flavor
   * Do:
   * - dotclear.ready(fn());
   *
   * @param      {Function}  fn      The callback
   */
  ready(fn) {
    if (document.readyState !== 'complete') {
      window.addEventListener('load', fn);
    } else {
      fn();
    }
  },

  /**
   * Gets application/json data (JSON format).
   * @param      {string}   id              element identifier
   * @param      {boolean}  [clear=true]    clear content
   * @param      {boolean}  [remove=true]   remove element
   * @return     {object}   data object
   */
  getData(id, clear = true, remove = true) {
    let data = {};
    // Read the JSON-formatted data from the DOM. (from https://mathiasbynens.be/notes/json-dom-csp)
    // To be use with: <script type="application/json" id="myid-data">{"key":value, …}</script>
    const element = document.getElementById(`${id}-data`);
    if (element) {
      try {
        data = JSON.parse(element.textContent);
        if (clear && remove) {
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

  isEmptyObject(item) {
    return this.isObject(item) && Object.keys(item).length === 0;
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
    const matches = document.cookie.match(new RegExp(`(?:^|; )${name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1')}=([^;]*)`));
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

    for (const optionKey in options) {
      updatedCookie += `; ${optionKey}`;
      const optionValue = options[optionKey];
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
