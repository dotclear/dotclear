/*global $, CodeMirror, getData */
'use strict';

$(function () {
  //const input = document.getElementById("colorsyntax_theme");
  //var theme = input.options[input.selectedIndex].textContent;
  const current = getData('theme_editor_current');
  var editor = CodeMirror.fromTextArea(document.getElementById('codemirror'), {
    mode: 'javascript',
    tabMode: 'indent',
    lineWrapping: 1,
    lineNumbers: 1,
    matchBrackets: 1,
    autoCloseBrackets: 1,
    theme: current.theme != '' ? current.theme : 'default',
  });

  $('#part-tabs-user-options').on('click', function () {
    editor.refresh();
  });

  $('#colorsyntax_theme').on('change', function () {
    var input = document.getElementById('colorsyntax_theme');
    var theme = input.options[input.selectedIndex].value;
    if (theme == '') theme = 'default';
    editor.setOption('theme', theme);
    editor.refresh();
  });
});
