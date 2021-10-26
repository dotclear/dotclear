/*global $, jQuery, dotclear */
'use strict';

jQuery.fn.updatePermissionsForm = function () {
  return this.each(function () {
    let permissions = {};
    const perm_reg_expr = /^perm\[(.+?)\]\[(.+?)\]$/;

    const admin = (dom_element) => {
      const matches = dom_element.name.match(perm_reg_expr);

      permissions[matches[1]].usage.checked = dom_element.checked;
      permissions[matches[1]].publish.checked = dom_element.checked;
      permissions[matches[1]].delete.checked = dom_element.checked;
      permissions[matches[1]].contentadmin.checked = dom_element.checked;
      permissions[matches[1]].categories.checked = dom_element.checked;
      permissions[matches[1]].media.checked = dom_element.checked;
      permissions[matches[1]].media_admin.checked = dom_element.checked;

      permissions[matches[1]].usage.disabled = dom_element.checked;
      permissions[matches[1]].publish.disabled = dom_element.checked;
      permissions[matches[1]].delete.disabled = dom_element.checked;
      permissions[matches[1]].contentadmin.disabled = dom_element.checked;
      permissions[matches[1]].categories.disabled = dom_element.checked;
      permissions[matches[1]].media.disabled = dom_element.checked;
      permissions[matches[1]].media_admin.disabled = dom_element.checked;
    };

    const doEventAdmin = (evt) => {
      admin(evt.data.dom_element);
    };

    const contentadmin = (dom_element) => {
      const matches = dom_element.name.match(perm_reg_expr);

      permissions[matches[1]].usage.checked = dom_element.checked;
      permissions[matches[1]].publish.checked = dom_element.checked;
      permissions[matches[1]].delete.checked = dom_element.checked;

      permissions[matches[1]].usage.disabled = dom_element.checked;
      permissions[matches[1]].publish.disabled = dom_element.checked;
      permissions[matches[1]].delete.disabled = dom_element.checked;
    };

    const doEventContentAdmin = (evt) => {
      contentadmin(evt.data.dom_element);
    };

    const mediaadmin = (dom_element) => {
      const matches = dom_element.name.match(perm_reg_expr);

      permissions[matches[1]].media.checked = dom_element.checked;

      permissions[matches[1]].media.disabled = dom_element.checked;
    };

    const doEventMediaAdmin = (evt) => {
      mediaadmin(evt.data.dom_element);
    };

    // Building a nice object of form elements
    for (let i = 0; i < this.elements.length; i++) {
      const form_element = this.elements[i];
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
      // Add some hierarchical level for known permissions
      if (matches[2] != 'admin') {
        form_element.classList.add('perm-second-level');
        if (['usage', 'publish', 'delete', 'media'].includes(matches[2])) {
          form_element.classList.add('perm-third-level');
        }
      }
    }

    // Populate states
    for (let blog in permissions) {
      // Loop on blog
      for (let element in permissions[blog]) {
        // Loop on permission
        const dom_element = permissions[blog][element];
        const matches = dom_element.name.match(perm_reg_expr);
        if (matches[2] == 'admin') {
          // select related permissions for admin
          if (dom_element.checked) {
            admin(dom_element);
          }
          $(dom_element).on('click', { dom_element }, doEventAdmin);
        } else if (matches[2] == 'contentadmin') {
          // select related permissions for content admin
          if (dom_element.checked) {
            contentadmin(dom_element);
          }
          $(dom_element).on('click', { dom_element }, doEventContentAdmin);
        } else if (matches[2] == 'media_admin') {
          // select related permissions for media admin
          if (dom_element.checked) {
            mediaadmin(dom_element);
          }
          $(dom_element).on('click',  { dom_element }, doEventMediaAdmin);
        }
      }
    }
  });
};

$(() => {
  $('.checkboxes-helpers').each(function () {
    dotclear.checkboxesHelpers(this, undefined, '#form-blogs input[type="checkbox"]', '#form-blogs #do-action');
  });
  dotclear.condSubmit('#form-blogs input[type="checkbox"]', '#form-blogs #do-action');
  $('#permissions-form').updatePermissionsForm();
});
