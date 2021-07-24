/*global $, CodeMirror, dotclear */
'use strict';

$(function () {
  const current = dotclear.getData('theme_editor_current');
  const editor = CodeMirror.fromTextArea(document.getElementById('codemirror'), {
    mode: 'javascript',
    tabMode: 'indent',
    lineWrapping: 1,
    lineNumbers: 1,
    matchBrackets: 1,
    autoCloseBrackets: 1,
    theme: current.theme || 'default',
  });

  $('#part-tabs-user-options').on('click', function () {
    editor.refresh();
  });

  $('#colorsyntax_theme').on('change', function () {
    const input = document.getElementById('colorsyntax_theme');
    editor.setOption('theme', input.options[input.selectedIndex].value || 'default');
    editor.refresh();
  });
});
