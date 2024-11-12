/*global dotclear */
'use strict';

if (typeof dotclear.post_remember_str === 'undefined' && typeof dotclear.getData !== 'undefined') {
  dotclear.post_remember_str = dotclear.getData('dc_post_remember_str').post_remember_str;
}

window.addEventListener('load', () => {
  const bloc = new DOMParser().parseFromString(
    `<p class="remember"><input type="checkbox" id="c_remember" name="c_remember"> <label for="c_remember">${dotclear.post_remember_str}</label></p>`,
    'text/html',
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

  const remember_me_name = 'comment_info';

  const info = readRememberInfo();

  if (info !== false) {
    document.getElementById('c_name').setAttribute('value', info.name);
    document.getElementById('c_mail').setAttribute('value', info.mail);
    document.getElementById('c_site').setAttribute('value', info.site);
    document.getElementById('c_remember').setAttribute('checked', 'checked');
  }

  document.getElementById('c_remember').onclick = (e) => {
    if (e.target.checked) {
      setRememberInfo();
    } else {
      dropRememberInfo();
    }
  };

  const copeWithModifiedInfo = () => {
    if (document.getElementById('c_remember').checked) {
      setRememberInfo();
    }
  };

  document.getElementById('c_name').onchange = copeWithModifiedInfo;
  document.getElementById('c_mail').onchange = copeWithModifiedInfo;
  document.getElementById('c_site').onchange = copeWithModifiedInfo;

  function setRememberInfo() {
    localStorage.setItem(
      remember_me_name,
      JSON.stringify({
        name: document.getElementById('c_name').value,
        mail: document.getElementById('c_mail').value,
        site: document.getElementById('c_site').value,
      }),
    );
  }

  function dropRememberInfo() {
    localStorage.removeItem(remember_me_name);
  }

  function readRememberInfo() {
    const data = localStorage.getItem(remember_me_name);

    if (data === null) {
      return false;
    }

    const result = JSON.parse(data);

    if (Object.keys(result).length !== 3) {
      dropRememberInfo();
      return false;
    }

    return result;
  }
});
