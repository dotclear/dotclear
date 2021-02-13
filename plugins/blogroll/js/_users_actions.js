/*global $, jQuery */
'use strict';

jQuery.fn.updateBlogrollPermissionsForm = function () {
  return this.each(function () {
    let perms = {};
    const re = /^perm\[(.+?)\]\[(.+?)\]$/;
    let e;
    let prop;

    // Building a nice object of form elements
    for (let i = 0; i < this.elements.length; i++) {
      e = this.elements[i];

      if (e.name == undefined) {
        continue;
      }
      prop = e.name.match(re);
      if (!prop) {
        continue;
      }
      if (perms[prop[1]] == undefined) {
        perms[prop[1]] = {};
      }
      perms[prop[1]][prop[2]] = e;

      // select related permissions for admin
      if (prop[2] == 'admin') {
        if (e.checked) {
          admin(e, perms, re);
        }
        $(e).on('click', function () {
          admin(this, perms, re);
        });
      }
    }

    function admin(E, perms, re) {
      const P = E.name.match(re);

      perms[P[1]].blogroll.checked = E.checked;
      perms[P[1]].blogroll.disabled = E.checked;
    }
  });
};

$(function () {
  $('#permissions-form').updateBlogrollPermissionsForm();
});
