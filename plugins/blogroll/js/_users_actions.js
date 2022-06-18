/*global $ */
'use strict';

$.fn.updateBlogrollPermissionsForm = function () {
  return this.each(function () {
    const permissions = {};
    const perm_reg_expr = /^perm\[(.+?)\]\[(.+?)\]$/;

    const admin = (dom_element) => {
      const matches = dom_element.name.match(perm_reg_expr);

      permissions[matches[1]].blogroll.checked = dom_element.checked;
      permissions[matches[1]].blogroll.disabled = dom_element.checked;
    };

    const doEventAdmin = (evt) => {
      admin(evt.data.form_element);
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

      // select related permissions for admin
      if (matches[2] == 'admin') {
        if (form_element.checked) {
          admin(form_element);
        }
        $(form_element).on('click', { form_element }, doEventAdmin);
      }
    }
  });
};

$(() => {
  $('#permissions-form').updateBlogrollPermissionsForm();
});
