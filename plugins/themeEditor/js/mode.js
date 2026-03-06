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
      delimStyle: 'tpl-var',
      innerStyle: 'tpl-inner',
    },
    {
      open: '<tpl:',
      close: '>',
      mode: CodeMirror.getMode(config, 'text/html'),
      delimStyle: 'tpl-block',
      innerStyle: 'tpl-inner',
    },
    {
      open: '</tpl:',
      close: '>',
      mode: CodeMirror.getMode(config, 'text/html'),
      delimStyle: 'tpl-block',
      innerStyle: 'tpl-inner',
    },
  );
});
