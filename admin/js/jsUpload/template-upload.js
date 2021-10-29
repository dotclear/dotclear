/*global tmpl, dotclear */
'use strict';

dotclear.jsUpload.template_upload = tmpl(
  `{% for (var i=0, file; file=o.files[i]; i++) { %}<li class="template-upload fade"><div class="upload-file"><div class="upload-fileinfo"><span class="upload-filename">{%=file.name%}</span><span class="upload-filesize">({%=o.formatFileSize(file.size)%})</span><span class="upload-filecancel cancel">${dotclear.jsUpload.msg.cancel}</span>{% if (!o.files.error && !i && !o.options.autoUpload) { %}<input type="submit" class="button start"  value="${dotclear.jsUpload.msg.send}"/>{% } %}<span class="upload-filemsg"></span></div>{% if (!o.files.error) { %}<div class="upload-progress progress progress-success progress-striped active"><div class="bar" style="width:0%;"></div></div>{% } %}</li>{% } %}`
);
