/*global dotclear */
/*exported CKEDITOR_GETURL */
'use strict';

// Get context
Object.assign(dotclear, dotclear.getData('ck_editor_ctx'));
// Get messages
Object.assign(dotclear.msg, dotclear.getData('ck_editor_msg'));
// Get CK Editor variables
Object.assign(window, dotclear.getData('ck_editor_var'));

const CKEDITOR_GETURL = function (resource) {
  // If this is not a full or absolute path.
  if (resource.indexOf(':/') == -1 && resource.indexOf('/') !== 0) {
    return this.basePath + resource;
  }
  return resource;
};
