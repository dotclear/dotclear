/*global dotclear */
/*exported CKEDITOR_GETURL */
'use strict';

if (!globalThis.CKEDITOR_GETURL) {
  // Get context
  Object.assign(dotclear, dotclear.getData('ck_editor_ctx'));
  // Get messages
  Object.assign(dotclear.msg, dotclear.getData('ck_editor_msg'));
  // Get CK Editor variables
  Object.assign(globalThis, dotclear.getData('ck_editor_var'));

  globalThis.CKEDITOR_GETURL = function (resource) {
    // If this is not a full or absolute path.
    if (!resource.includes(':/') && resource.indexOf('/') !== 0) {
      return this.basePath + resource;
    }
    return resource;
  };
}
