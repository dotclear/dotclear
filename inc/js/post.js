/*global dotclear */
'use strict';

if (typeof post_remember_str === 'undefined' && typeof getData !== 'undefined') {
  var post_remember_str = dotclear.getData('dc_post_remember_str').post_remember_str;
}

window.addEventListener('load', () => {
  let bloc = new DOMParser().parseFromString(
    `<p class="remember"><input type="checkbox" id="c_remember" name="c_remember" /> <label for="c_remember">${post_remember_str}</label></p>`,
    'text/html'
  ).body.firstChild;
  // Looks for a preview input
  let point = document.querySelector('#comment-form input[type=submit][name=preview]');
  if (!point) {
    // not found, looks for a preview button
    point = document.querySelector('#comment-form button[type=submit][name=preview]');
  }
  if (point) {
    // Preview found, insert remember me checkbox
    point = point.parentNode; // Seek to enclosed paragraphe which contains preview button
    point.parentNode.insertBefore(bloc, point);
  } else {
    // No preview button/input found, no more to do
    return;
  }

  const remember_cookie_name = 'comment_info';

  let cookie = readRememberCookie(dotclear.getCookie(remember_cookie_name));

  if (cookie != false) {
    document.getElementById('c_name').setAttribute('value', cookie[0]);
    document.getElementById('c_mail').setAttribute('value', cookie[1]);
    document.getElementById('c_site').setAttribute('value', cookie[2]);
    document.getElementById('c_remember').setAttribute('checked', 'checked');
  }

  document.getElementById('c_remember').onclick = function(e) {
    if (e.target.checked) {
      setRememberCookie();
    } else {
      dropRememberCookie();
    }
  };

  let copeWithModifiedInfo = function() {
    if (document.getElementById('c_remember').checked) {
      setRememberCookie();
    }
  };

  document.getElementById('c_name').onchange = copeWithModifiedInfo;
  document.getElementById('c_mail').onchange = copeWithModifiedInfo;
  document.getElementById('c_site').onchange = copeWithModifiedInfo;

  function setRememberCookie() {
    let name = document.getElementById('c_name').value;
    let mail = document.getElementById('c_mail').value;
    let site = document.getElementById('c_site').value;
    let cpath = document.querySelector('h1 a').getAttribute('href');

    cpath = !cpath ? '/' : cpath.replace(/.*:\/\/[^\/]*([^?]*).*/g, '$1');

    dotclear.setCookie(remember_cookie_name, `${name}\n${mail}\n${site}`, {
      expires: 60, // keep cookie for 2 months (60 days)
      path: cpath
    });
  }

  function dropRememberCookie() {
    dotclear.deleteCookie(remember_cookie_name);
  }

  function readRememberCookie(cookie) {
    if (!cookie) {
      return false;
    }

    let result = cookie.split('\n');

    if (result.length != 3) {
      dropRememberCookie();
      return false;
    }

    return result;
  }
});
