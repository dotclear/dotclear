/*global CodeMirror, dotclear */
'use strict';

window.CodeMirror.defineMode('dotclear', (config) => {
  const mode = dotclear.getData('theme_editor_mode');

  return CodeMirror.multiplexingMode(
    CodeMirror.getMode(config, mode.mode),
    {
      open: '{{tpl:',
      close: '}}',
      mode: CodeMirror.getMode(config, 'text/html'),
      parseDelimiters: true,
    },
    {
      open: '<tpl:',
      close: '>',
      mode: CodeMirror.getMode(config, 'text/html'),
      parseDelimiters: true,
    },
    {
      open: '</tpl:',
      close: '>',
      mode: CodeMirror.getMode(config, 'text/html'),
      parseDelimiters: true,
    },
  );
});
