/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  Object.assign(dotclear.msg, dotclear.getData('maintenance'));

  function doStep(box, code, count) {
    dotclear.jsonServicesPost(
      'dcMaintenanceStep',
      (data) => {
        // Display step message
        const msg = document.querySelector('.step-msg');
        if (msg) {
          msg.textContent = data.title;
        }

        const next = data.code;
        if (next > 0) {
          doStep(box, next, data.count);
          return;
        }

        // Display success message
        const title = document.querySelector('#content h2');
        if (title && msg) {
          const div = document.createElement('div');
          div.classList.add('success');
          div.appendChild(msg);
          title.after(msg);
        }

        // Remove waiting message
        const wait = document.querySelector('.step-wait');
        if (wait) wait.remove();

        // Show go back button
        const back = document.querySelector('.step-back');
        if (back) back.style.display = '';
      },
      {
        task: box.getAttribute('id'),
        code,
        count,
      },
      (error) => {
        // Display error message
        const msg = document.querySelector('.step-msg');
        if (msg) msg.textContent = error;

        // Remove waiting message
        const wait = document.querySelector('.step-wait');
        if (wait) wait.remove();

        // Show go back button
        const back = document.querySelector('.step-back');
        if (back) back.style.display = '';
      },
    );
  }

  const box = document.querySelector('.step-box');
  if (box) {
    const code = box.querySelector('input[name=code]')?.value;
    const count = box.querySelector('input[name=count]')?.value;

    // Remove submit button and hidden fields
    const submit = document.querySelector('.step-submit');
    if (submit) submit.remove();

    // Hide go back button
    const back = document.querySelector('.step-back');
    if (back) back.style.display = 'none';

    // Add waiting message
    const msg = document.querySelector('.step-msg');
    if (msg) {
      const para = document.createElement('p');
      para.classList.add('step-wait');
      para.textContent = dotclear.msg.wait;
      msg.after(para);
    }

    doStep(box, code, count);
  }
});
