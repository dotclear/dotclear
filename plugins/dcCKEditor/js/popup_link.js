/*global $ */
'use strict';

$(function () {
  $('#link-insert-cancel').on('click', function () {
    window.close();
  });

  $('#link-insert-ok').on('click', function () {
    const insert_form = $('#link-insert-form').get(0);
    if (insert_form == undefined) {
      return;
    }

    const editor_name = window.opener.$.getEditorName();
    const editor = window.opener.CKEDITOR.instances[editor_name];
    let link = '';
    let selected_element;
    if (editor.getSelection().getSelectedElement() != null) {
      selected_element = editor.getSelection().getSelectedElement();
    } else {
      selected_element = editor.getSelection().getNative().toString();
    }

    if (editor.mode == 'wysiwyg') {
      link = editor.document.createElement('a');
      link.setAttribute('href', insert_form.elements.href.value);
      if (insert_form.elements.title.value != '') {
        link.setAttribute('title', window.opener.CKEDITOR.tools.htmlEncodeAttr(insert_form.elements.title.value));
      }
      if (insert_form.elements.hreflang.value != '') {
        link.setAttribute('hreflang', window.opener.CKEDITOR.tools.htmlEncodeAttr(insert_form.elements.hreflang.value));
      }
      if (editor.getSelection().getSelectedElement() != null) {
        selected_element.appendTo(link);
      } else {
        link.appendText(selected_element);
      }
      editor.insertElement(link);
    }
    window.close();
  });
});
