/*global $, CodeMirror, dotclear */
'use strict';

$(() => {
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

  $('#part-tabs-user-options').on('click', () => {
    editor.refresh();
  });

  const themes_loaded = ['default'];
  if (current.theme !== 'default') {
    themes_loaded.push(current.theme);
  }
  $('#colorsyntax_theme').on('change', () => {
    const input = document.getElementById('colorsyntax_theme');
    const theme = input.options[input.selectedIndex].value || 'default';
    // Dynamically load theme if not default and not already loaded
    if (!themes_loaded.includes(theme)) {
      const style = document.createElement('link');
      style.setAttribute('rel', 'stylesheet');
      style.setAttribute('type', 'text/css');
      style.setAttribute('href', `js/codemirror/theme/${theme}.css`);
      document.getElementsByTagName('head')[0].append(style);
      themes_loaded.push(theme);
    }
    editor.setOption('theme', theme);
    editor.refresh();
  });
});
