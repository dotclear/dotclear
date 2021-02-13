/*global dotclear, getData */
/*exported CKEDITOR_GETURL */
'use strict';

// Get context
Object.assign(dotclear, getData('ck_editor_ctx'));
// Get messages
Object.assign(dotclear.msg, getData('ck_editor_msg'));
// Get CK Editor variables
Object.assign(window, getData('ck_editor_var'));

var CKEDITOR_GETURL = function (resource) {
  // If this is not a full or absolute path.
  if (resource.indexOf(':/') == -1 && resource.indexOf('/') !== 0) {
    resource = this.basePath + resource;
  }
  return resource;
};
