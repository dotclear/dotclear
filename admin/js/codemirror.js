/*global dotclear, CodeMirror */
'use strict';

// Store all instances
const codemirror_instance = {};

// Launch all requested codemirror instance
for (const i of dotclear.getData('codemirror')) {
  const elt = document.getElementById(i.id);
  if (elt) {
    // Get current height of textarea
    const max = elt.clientHeight;
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
        Esc(cm) {
          if (cm.getOption('fullScreen')) {
            // Exit from fullscreen mode
            cm.setOption('fullScreen', false);
          } else {
            // the user pressed the escape key, now tab will tab to the next element for accessibility
            if (!cm.state.keyMaps.some((x) => x.name == 'tabAccessibility')) {
              cm.addKeyMap({
                name: 'tabAccessibility',
                Tab: false,
                'Shift-Tab': false,
              });
            }
          }
        },
      },
      theme: i.theme,
    });
    // Set CM same height as textarea
    const cm = codemirror_instance[i.name].getWrapperElement();
    if (cm) {
      cm.style.height = `${max}px`;
    }
    const editor = codemirror_instance[i.name];
    if (editor) {
      editor.on('focus', (cm) => {
        // On focus, make tab add tab in editor
        cm.removeKeyMap('tabAccessibility');
      });
    }
  }
}
