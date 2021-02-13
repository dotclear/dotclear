/*global $ */
'use strict';

$(function () {
  $('#link-insert-cancel').on('click', function () {
    window.close();
  });

  $('#form-entries tr>td.maximal>a').on('click', function (e) {
    e.preventDefault();
    const editor_name = window.opener.$.getEditorName();
    const editor = window.opener.CKEDITOR.instances[editor_name];
    let link = '';
    const selected_text = editor.getSelection().getNative().toString();

    if (editor.mode == 'wysiwyg') {
      link = '<a class="ref-post" href="' + window.opener.$.stripBaseURL($(this).attr('title')) + '">' + selected_text + '</a>';
      const element = window.opener.CKEDITOR.dom.element.createFromHtml(link);
      editor.insertElement(element);
    }
    window.close();
  });
});
