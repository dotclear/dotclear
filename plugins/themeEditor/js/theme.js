/*global CodeMirror, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  const current = dotclear.getData('theme_editor_current');
  const elt = document.getElementById('codemirror');
  const editor = CodeMirror.fromTextArea(elt, {
    mode: 'javascript',
    tabMode: 'indent',
    lineWrapping: 1,
    lineNumbers: 1,
    matchBrackets: 1,
    autoCloseBrackets: 1,
    readOnly: elt.readOnly,
    theme: current.theme || 'default',
  });

  document.getElementById('part-tabs-user-options')?.addEventListener('click', () => {
    editor.refresh();
  });

  const themes_loaded = ['default'];
  if (current.theme !== 'default') {
    themes_loaded.push(current.theme);
  }

  document.getElementById('colorsyntax_theme')?.addEventListener('change', (event) => {
    const theme = event.target.options[event.target.selectedIndex].value || 'default';
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
