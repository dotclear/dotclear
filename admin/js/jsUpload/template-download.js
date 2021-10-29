/*global tmpl, dotclear */
'use strict';

dotclear.jsUpload.template_download = tmpl(
  `{% for (var i=0, file; file=o.files[i]; i++) { %}<li class="template-download fade"><div class="upload-file"><div class="upload-fileinfo"><span class="upload-filename">{%=file.name%}</span><span class="upload-filesize">({%=o.formatFileSize(file.size)%})</span><span class="upload-filemsg{% if (file.error) { %} upload-error{% } %}">{% if (file.error) { %}${dotclear.jsUpload.msg.error} {%=file.error%}{% } else { %}${dotclear.jsUpload.msg.file_successfully_uploaded}{% } %}</span></div><div class="upload-progress">{% if (!file.error) { %}<div class="bar" style="width:100%;">100%</div>{% } %}</div></li>{% } %}`
);
