/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  const globalRadio = document.getElementById('settings_recall_all');
  const separateRadio = document.getElementById('settings_recall_separate');

  if (globalRadio && separateRadio) {
    const setStatus = (generic = true) => {
      if (generic) document.getElementById('settings_recall_time').removeAttribute('disabled');
      else document.getElementById('settings_recall_time').setAttribute('disabled', 'disabled');

      for (const choice of document.querySelectorAll('.recall-per-task')) {
        if (generic) choice.setAttribute('disabled', 'disabled');
        else choice.removeAttribute('disabled');
      }
    };

    // 1st pass, set initial status
    setStatus(globalRadio.getAttribute('checked') !== null);

    // Listen change on radio choice
    globalRadio.addEventListener('click', () => {
      setStatus(true);
    });
    separateRadio.addEventListener('click', () => {
      setStatus(false);
    });

    dotclear.condSubmit('#part-maintenance input[type="radio"]', '#part-maintenance input[type="submit"]');
    dotclear.condSubmit('#part-backup input[type="radio"]', '#part-backup input[type="submit"]');
    dotclear.condSubmit('#part-dev input[type="radio"]', '#part-dev input[type="submit"]');
  }
});
