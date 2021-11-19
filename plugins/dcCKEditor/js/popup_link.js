/*global $ */
'use strict';

$(() => {
  $('#link-insert-cancel').on('click', () => {
    window.close();
  });

  $('#link-insert-ok').on('click', () => {
    const insert_form = $('#link-insert-form').get(0);
    if (insert_form == undefined) {
      return;
    }

    const editor = window.opener.CKEDITOR.instances[window.opener.$.getEditorName()];

    if (editor.mode == 'wysiwyg') {
      const selected_element = editor.getSelection().getSelectedElement() ?? editor.getSelection().getNative().toString();
      const link = editor.document.createElement('a');

      link.setAttribute('href', insert_form.elements.href.value);
      if (insert_form.elements.title.value != '') {
        link.setAttribute('title', window.opener.CKEDITOR.tools.htmlEncodeAttr(insert_form.elements.title.value));
      }
      if (insert_form.elements.hreflang.value != '') {
        link.setAttribute('hreflang', window.opener.CKEDITOR.tools.htmlEncodeAttr(insert_form.elements.hreflang.value));
      }
      if (editor.getSelection().getSelectedElement() == null) {
        link.appendText(selected_element);
      } else {
        selected_element.appendTo(link);
      }
      editor.insertElement(link);
    }
    window.close();
  });
});
