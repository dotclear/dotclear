/*global dotclear, CodeMirror */
'use strict';

// Store all instances
const codemirror_instance = {};

// Launch all requested codemirror instance
for (const i of dotclear.getData('codemirror')) {
  const elt = document.getElementById(i.id);
  codemirror_instance[i.name] = CodeMirror.fromTextArea(elt, {
    mode: i.mode,
    tabMode: 'indent',
    lineWrapping: 1,
    lineNumbers: 1,
    matchBrackets: 1,
    autoCloseBrackets: 1,
    readOnly: elt.readOnly,
    extraKeys: {
      F11(cm) {
        cm.setOption('fullScreen', !cm.getOption('fullScreen'));
      },
    },
    theme: i.theme,
  });
}
