/*global $ */
'use strict';

$(() => {
  $('#link-insert-cancel').on('click', () => {
    window.close();
  });

  $('#form-entries tr>td.maximal>a').on('click', function (e) {
    e.preventDefault();
    const editor = window.opener.CKEDITOR.instances[window.opener.$.getEditorName()];

    if (editor.mode == 'wysiwyg') {
      const selected_text = editor.getSelection().getNative().toString();
      const link = `<a class="ref-post" href="${window.opener.$.stripBaseURL($(this).attr('title'))}">${selected_text}</a>`;

      editor.insertElement(window.opener.CKEDITOR.dom.element.createFromHtml(link));
    }
    window.close();
  });
});
