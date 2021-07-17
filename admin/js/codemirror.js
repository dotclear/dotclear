/*global getData, CodeMirror */
'use strict';

// Store all instances
let codemirror_instance = {};

// Launch all requested codemirror instance
// We use getData() rather than dotclear.getData() as DOM content ready has not been fired yet
for (let i of getData('codemirror')) {
  codemirror_instance[i.name] = CodeMirror.fromTextArea(document.getElementById(i.id), {
    mode: i.mode,
    tabMode: 'indent',
    lineWrapping: 1,
    lineNumbers: 1,
    matchBrackets: 1,
    autoCloseBrackets: 1,
    extraKeys: {
      F11: function (cm) {
        cm.setOption('fullScreen', !cm.getOption('fullScreen'));
      },
    },
    theme: i.theme,
  });
}
