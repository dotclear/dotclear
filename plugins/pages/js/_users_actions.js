/*global $ */
'use strict';

$.fn.updatePagesPermissionsForm = function () {
  return this.each(function () {
    const permissions = {};
    const perm_reg_expr = /^perm\[(.+?)\]\[(.+?)\]$/;

    const admin = (dom_element) => {
      const matches = dom_element.name.match(perm_reg_expr);

      permissions[matches[1]].pages.checked = dom_element.checked;
      permissions[matches[1]].pages.disabled = dom_element.checked;
    };

    const doEventAdmin = (evt) => {
      admin(evt.data.dom_element);
    };

    // Building a nice object of form elements
    for (const form_element of this.elements) {
      if (form_element.name == undefined) {
        continue;
      }
      const matches = form_element.name.match(perm_reg_expr);
      if (!matches) {
        continue;
      }
      if (permissions[matches[1]] == undefined) {
        permissions[matches[1]] = {};
      }
      permissions[matches[1]][matches[2]] = form_element;
    }

    // Populate states
    for (const blog in permissions) {
      // Loop on blog
      for (const element in permissions[blog]) {
        // Loop on permission
        const dom_element = permissions[blog][element];
        const matches = dom_element.name.match(perm_reg_expr);
        if (matches[2] == 'admin') {
          // select related permissions for admin
          if (dom_element.checked) {
            admin(dom_element);
          }
          $(dom_element).on('click', { dom_element }, doEventAdmin);
        }
      }
    }
  });
};

$(() => {
  $('#permissions-form').updatePagesPermissionsForm();
});
