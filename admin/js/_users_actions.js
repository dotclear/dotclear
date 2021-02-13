/*global $, jQuery, dotclear */
'use strict';

jQuery.fn.updatePermissionsForm = function () {
  return this.each(function () {
    let perms = {};
    const re = /^perm\[(.+?)\]\[(.+?)\]$/;
    let e, prop;

    const admin = function (E, perms, re) {
      const P = E.name.match(re);

      perms[P[1]].usage.checked = E.checked;
      perms[P[1]].publish.checked = E.checked;
      perms[P[1]].delete.checked = E.checked;
      perms[P[1]].contentadmin.checked = E.checked;
      perms[P[1]].categories.checked = E.checked;
      perms[P[1]].media.checked = E.checked;
      perms[P[1]].media_admin.checked = E.checked;

      perms[P[1]].usage.disabled = E.checked;
      perms[P[1]].publish.disabled = E.checked;
      perms[P[1]].delete.disabled = E.checked;
      perms[P[1]].contentadmin.disabled = E.checked;
      perms[P[1]].categories.disabled = E.checked;
      perms[P[1]].media.disabled = E.checked;
      perms[P[1]].media_admin.disabled = E.checked;
    };

    const contentadmin = function (E, perms, re) {
      const P = E.name.match(re);

      perms[P[1]].usage.checked = E.checked;
      perms[P[1]].publish.checked = E.checked;
      perms[P[1]].delete.checked = E.checked;

      perms[P[1]].usage.disabled = E.checked;
      perms[P[1]].publish.disabled = E.checked;
      perms[P[1]].delete.disabled = E.checked;
    };

    const mediaadmin = function (E, perms, re) {
      const P = E.name.match(re);

      perms[P[1]].media.checked = E.checked;

      perms[P[1]].media.disabled = E.checked;
    };

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
    }
    // Populate states
    for (let blog in perms) {
      // Loop on blog
      for (let element in perms[blog]) {
        // Loop on permission
        e = perms[blog][element];
        prop = e.name.match(re);
        if (prop[2] == 'admin') {
          // select related permissions for admin
          if (e.checked) {
            admin(e, perms, re);
          }
          $(e).on('click', function () {
            admin(this, perms, re);
          });
        } else if (prop[2] == 'contentadmin') {
          // select related permissions for content admin
          if (e.checked) {
            contentadmin(e, perms, re);
          }
          $(e).on('click', function () {
            contentadmin(this, perms, re);
          });
        } else if (prop[2] == 'media_admin') {
          // select related permissions for media admin
          if (e.checked) {
            mediaadmin(e, perms, re);
          }
          $(e).on('click', function () {
            mediaadmin(this, perms, re);
          });
        }
      }
    }
  });
};

$(function () {
  $('.checkboxes-helpers').each(function () {
    dotclear.checkboxesHelpers(this, undefined, '#form-blogs input[type="checkbox"]', '#form-blogs #do-action');
  });
  dotclear.condSubmit('#form-blogs input[type="checkbox"]', '#form-blogs #do-action');
  $('#permissions-form').updatePermissionsForm();
});
