/*global CodeMirror, getData */
'use strict';

window.CodeMirror.defineMode('dotclear', function (config) {
  const mode = getData('theme_editor_mode');

  return CodeMirror.multiplexingMode(
    CodeMirror.getMode(config, mode.mode),
    {
      open: '{{tpl:',
      close: '}}',
      mode: CodeMirror.getMode(config, 'text/plain'),
      delimStyle: 'delimit',
    },
    {
      open: '<tpl:',
      close: '>',
      mode: CodeMirror.getMode(config, 'text/plain'),
      delimStyle: 'delimit',
    },
    {
      open: '</tpl:',
      close: '>',
      mode: CodeMirror.getMode(config, 'text/plain'),
      delimStyle: 'delimit',
    }
  );
});
