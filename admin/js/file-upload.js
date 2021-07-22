/*global dotclear */
'use strict';

// Get dotclear messages
Object.assign(dotclear.msg, dotclear.getData('file_upload_msg'));

// Get jsUpload data
dotclear.jsUpload = {};
Object.assign(dotclear.jsUpload, dotclear.getData('file_upload'));
