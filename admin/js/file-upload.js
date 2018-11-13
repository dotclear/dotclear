/*global dotclear, getData */
'use strict';

// Get dotclear messages
Object.assign(dotclear.msg, getData('file_upload_msg'));

// Get jsUpload data
dotclear.jsUpload = {};
Object.assign(dotclear.jsUpload, getData('file_upload'));
