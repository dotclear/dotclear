/*global dotclear */
'use strict';

/* Get PreInit JSON data */
dotclear.data = dotclear.getData('dotclear_init');

/* Set some CSS variables here
-------------------------------------------------------- */
// set base font-size of body (62.5% default, usually : 50% to 75%)
if (typeof dotclear.data.htmlFontSize !== 'undefined') {
  document.documentElement.style.setProperty('--html-font-size', dotclear.data.htmlFontSize);
}
// Back to system font if necessary
if (typeof dotclear.data.systemFont !== 'undefined') {
  document.documentElement.style.setProperty('--dc-font', 'dotclear');
}
// set theme mode (dark/light/…)
dotclear.data.theme = 'light';
if (document.documentElement.getAttribute('data-theme') !== '') {
  dotclear.data.theme = document.documentElement.getAttribute('data-theme');
} else if (window?.matchMedia('(prefers-color-scheme: dark)').matches) {
  dotclear.data.theme = 'dark';
}
// Cope with low data requirement
dotclear.data.lowdata = false;
if (window?.matchMedia('(prefers-reduced-data: reduce)').matches) {
  dotclear.data.lowdata = true;
}
document.documentElement.style.setProperty('--dark-mode', dotclear.data.theme === 'dark' ? '1' : '0');

/* Dotclear common methods
-------------------------------------------------------- */

/**
 * Return a jQuery collection with the given element(s)
 *
 * @param      {(string|Element|NodeList|jQuery)}  elt    The element (selector string as in CSS, DOM Element, NodeList, jQuery object)
 * @return     {jQuery}
 */
dotclear.jQueryNodes = (elt) => {
  // jQuery instance
  if (elt instanceof jQuery) return elt;

  // NodeList
  if (elt instanceof NodeList) return jQuery(Array.from(elt));

  // String selector or DOM Element
  if (typeof elt === 'string' || elt instanceof Element) return jQuery(elt);

  // Return an empty collection (length === 0)
  return jQuery();
};

/**
 * Return a NodeList or an Array with the given element(s)
 *
 * @param      {(jQuery|NodeList|Element|string|array)}  elt  The element (selector string, NodeList, DOM Element, jQuery object, array)
 * @return     {NodeList|Array}
 */
dotclear.nodes = (elt) => {
  // NodeList
  if (elt instanceof NodeList) return elt;

  // Array
  if (Array.isArray(elt)) return elt;

  // String selector
  if (typeof elt === 'string') return document.querySelectorAll(elt);

  // DOM Element
  if (elt instanceof Element) return [elt];

  // jQuery instance
  if (elt instanceof jQuery) return elt.get();

  // Return an empty array (length === 0)
  return [];
};

/**
 * Return an DOM Element with the given element
 *
 * @param      {(jQuery|NodeList|Element|string|array)}  elt  The element (selector string, NodeList, DOM Element, jQuery object, array)
 * @return     {Element|null}
 */
dotclear.node = (elt) => {
  const list = dotclear.nodes(elt);

  return list.length ? list[0] : null;
};

/**
 * Return a NodeList created from an HTML string
 *
 * @param      {string}         html    The html
 * @return     {NodeList}
 */
dotclear.htmlToNodes = (html) => {
  const template = document.createElement('template');
  template.innerHTML = html;

  return template.content.childNodes;
};

/**
 * Return a DOM Element created from an HTML string, may return Null on error
 *
 * @param      {string}         html    The html
 * @return     {Element|null}
 */
dotclear.htmlToNode = (html) => {
  const list = dotclear.htmlToNodes(html);

  return list.length ? list[0] : null;
};

/**
 * Check if the current DOM element accept keybpard input
 *
 * @param      {Element}  element  The element
 * @return     {boolean}
 */
dotclear.acceptsKeyboardInput = (element) => {
  const nonTypingInputTypes = new Set(['checkbox', 'radio', 'button', 'reset', 'submit', 'file']);
  return (
    (element.tagName === 'INPUT' && !nonTypingInputTypes.has(element.type)) ||
    element.tagName === 'TEXTAREA' ||
    element.tagName === 'SELECT' ||
    element.tagName === 'BUTTON' ||
    element.isContentEditable
  );
};

/**
 * Ask a confirmation and return result, the optional event is stopped (default and propagation) if canceled
 *
 * Example:
 *
 * element.addEventListener('click', (event) => dotclear.confirm(dotclear.msg.confirm_doing_something, event));
 *
 * @param      {string}       message       The message
 * @param      {Event|null}   [event=null]  The event
 * @return     {boolean}      true if user confirm, else false
 */
dotclear.confirm = (message, event = null) => {
  if (window.confirm(message)) return true;
  event?.preventDefault();
  event?.stopPropagation();
  return false;
};

/**
 * Expands element using callback to get content.
 *
 * @param      {Object}           opts    The options
 */
dotclear.expandContent = (opts) => {
  const toggleArrow = (button, actionRequested = '') => {
    const actionDone =
      actionRequested === ''
        ? button.getAttribute('aria-label') === dotclear.img_plus_alt
          ? 'open'
          : 'close'
        : actionRequested;
    if (actionDone === 'open' && button.getAttribute('aria-expanded') === 'false') {
      button.firstChild.data = dotclear.img_minus_txt;
      button.setAttribute('value', dotclear.img_minus_txt);
      button.setAttribute('aria-label', dotclear.img_minus_alt);
      button.setAttribute('aria-expanded', true);
    } else if (actionDone === 'close' && button.getAttribute('aria-expanded') === 'true') {
      button.firstChild.data = dotclear.img_plus_txt;
      button.setAttribute('value', dotclear.img_plus_txt);
      button.setAttribute('aria-label', dotclear.img_plus_alt);
      button.setAttribute('aria-expanded', false);
    } else {
      // Nothing done
      return '';
    }
    return actionDone;
  };

  const singleExpander = (line, callback) => {
    const elt = dotclear.node(line);
    if (!elt) {
      return;
    }
    const button = dotclear.htmlToNode(
      `<button type="button" class="details-cmd" aria-expanded="false" aria-label="${dotclear.img_plus_alt}">${dotclear.img_plus_txt}</button>`,
    );
    button.addEventListener('click', (event) => {
      if (toggleArrow(button) !== '') callback(elt, '', event);
      event.preventDefault();
    });
    // Add button at the beginning of the first TD child of given line
    const td = line.firstChild;
    if (td) td.prepend(button);
  };

  const multipleExpander = (line, lines, callback) => {
    const elt = dotclear.node(line);
    if (elt) {
      const list = dotclear.nodes(lines);
      if (list.length) {
        const button = dotclear.htmlToNode(
          `<button type="button" class="details-cmd" aria-expanded="false" aria-label="${dotclear.img_plus_alt}">${dotclear.img_plus_txt}</button>`,
        );
        button.addEventListener('click', (event) => {
          const action = toggleArrow(button);
          for (const item of list) {
            if (toggleArrow(item.firstChild.firstChild, action) !== '') callback(item, action, event);
          }
          event.preventDefault();
        });
        // Add button at the beginning of the first TF child of given line
        const td = line.firstChild;
        if (td) td.prepend(button);
      }
    }
  };

  if (opts === undefined || opts.callback === undefined || typeof opts.callback !== 'function') {
    return;
  }
  if (opts.line !== undefined) {
    multipleExpander(opts.line, opts.lines, opts.callback);
  }
  for (const line of opts.lines) {
    singleExpander(line, opts.callback);
  }
};

/**
 * Add toggle mecanism for a target element
 *
 * @param      {Element}           target      The DOM Element
 * @param      {NodeList}          childs      The DOM Element childs to be hidden/shown
 * @param      {Object}            options     The options
 */
dotclear.toggleWithLegend = (target, childs, options) => {
  const defaults = {
    img_on_txt: dotclear.img_plus_txt,
    img_on_alt: dotclear.img_plus_alt,
    img_off_txt: dotclear.img_minus_txt,
    img_off_alt: dotclear.img_minus_alt,
    unfolded_sections: dotclear.unfolded_sections,
    hide: true,
    legend_click: false,
    fn: false, // A function called on first display,
    user_pref: false,
    reverse_user_pref: false, // Reverse user pref behavior
  };
  const parameters = Object.assign(defaults, options);
  if (!childs?.length) {
    return;
  }
  const set_user_pref = parameters.hide ^ parameters.reverse_user_pref;
  if (
    parameters.user_pref &&
    parameters.unfolded_sections !== undefined &&
    parameters.user_pref in parameters.unfolded_sections
  ) {
    parameters.hide = parameters.reverse_user_pref;
  }

  const toggle = (elt) => {
    if (parameters.hide) {
      elt.firstChild.data = parameters.img_on_txt;
      elt.setAttribute('value', parameters.img_on_txt);
      elt.setAttribute('aria-label', parameters.img_on_alt);
      elt.setAttribute('aria-expanded', false);
      for (const child of childs) child.classList.add('hide');
    } else {
      elt.firstChild.data = parameters.img_off_txt;
      elt.setAttribute('value', parameters.img_off_txt);
      elt.setAttribute('aria-label', parameters.img_off_alt);
      elt.setAttribute('aria-expanded', true);
      for (const child of childs) child.classList.remove('hide');
      if (parameters.fn) {
        for (const child of childs) parameters.fn.apply(child);
        parameters.fn = false;
      }
    }
    parameters.hide = !parameters.hide;
  };

  const button = dotclear.htmlToNode(
    `<button type="button" class="details-cmd" value="${parameters.img_on_txt}" aria-label="${parameters.img_on_alt}">${parameters.img_on_txt}</button>`,
  );

  const ctarget = parameters.legend_click ? target : button;
  target.style.cursor = 'pointer';
  if (parameters.legend_click) {
    const label = ctarget.querySelector('label');
    if (label) label.style.cursor = 'pointer';
  }
  target.addEventListener('click', (e) => {
    // Catch click only on summary child of details HTML element
    if (e.target !== target && e.target !== button) return;
    if (parameters.user_pref && set_user_pref) {
      dotclear.jsonServicesPost('setSectionFold', () => {}, {
        section: parameters.user_pref,
        value: parameters.hide ^ parameters.reverse_user_pref ? 1 : 0,
      });
    }
    toggle(button);
    e.preventDefault();
    return false;
  });
  toggle(button);
  target.prepend(button);
};

/**
 * Add toggle mecanism for a details element
 *
 * @param      {Element}           target      The DOM Element
 * @param      {Object}            options     The options
 */
dotclear.toggleWithDetails = (target, options) => {
  const defaults = {
    unfolded_sections: dotclear.unfolded_sections,
    hide: true, // Is section unfolded?
    fn: false, // A function called on first display,
    user_pref: false,
    reverse_user_pref: false, // Reverse user pref behavior
  };
  const parameters = Object.assign(defaults, options);
  if (
    parameters.user_pref &&
    parameters.unfolded_sections !== undefined &&
    parameters.user_pref in parameters.unfolded_sections
  ) {
    parameters.hide = parameters.reverse_user_pref;
  }

  const toggle = () => {
    if (!parameters.hide && parameters.fn) {
      parameters.fn.apply(target);
      parameters.fn = false;
    }
    parameters.hide = !parameters.hide;
    if (parameters.hide && target.getAttribute('open')) {
      target.removeAttribute('open');
    } else if (!parameters.hide && !target.getAttribute('open')) {
      target.setAttribute('open', 'open');
    }
  };

  target.addEventListener('click', (e) => {
    // Catch click only on summary child of details HTML element
    const summary = target.querySelector('summary');
    if (e.target !== summary) return;
    if (parameters.user_pref) {
      // Store current user choice
      dotclear.jsonServicesPost('setSectionFold', () => {}, {
        section: parameters.user_pref,
        value: parameters.hide ^ parameters.reverse_user_pref ? 1 : 0,
      });
    }
    toggle();
    e.preventDefault();
    return false;
  });
  toggle();
};

/**
 * Show help viewer
 *
 * @param      {string}  selector  The help box selector
 */
dotclear.helpViewer = (selector) => {
  const helpBox = document.querySelector(selector);
  if (!helpBox) return;

  const p = {
    img_on_txt: dotclear.img_plus_txt,
    img_on_alt: dotclear.img_plus_alt,
    img_off_txt: dotclear.img_minus_txt,
    img_off_alt: dotclear.img_minus_alt,
  };

  // Buttons templates
  const helpButtonTemplate = dotclear.htmlToNode(`<p id="help-button"><span><a href="">${dotclear.msg.help}</a></span></p>`);
  const chapterButtonTemplate = dotclear.htmlToNode(
    `<button type="button" class="details-cmd" aria-label="${p.img_on_alt}">${p.img_on_txt}</button>`,
  );

  // Helpers

  // Toggle help button
  const toggle = () => {
    const content = document.getElementById('content');
    if (content) {
      content.classList.toggle('with-help');
      document.querySelector('p#help-button span a').innerText = content.classList.contains('with-help')
        ? dotclear.msg.help_hide
        : dotclear.msg.help;
      // Position button
      positionButton();
    }
    sizeBox();
    return false;
  };

  // Set height size of help box
  const sizeBox = () => {
    const wrapper = document.getElementById('wrapper');
    helpBox.style.height = 'auto';
    if (wrapper && wrapper.getBoundingClientRect().height > helpBox.getBoundingClientRect().height)
      helpBox.style.height = `${wrapper.getBoundingClientRect().height}px`;
  };

  // Cope with help chapters
  const chapterToggler = (chapterTitle) => {
    const button = chapterButtonTemplate.cloneNode(true);
    let hide = true;

    chapterTitle.style.cursor = 'pointer';
    chapterTitle.prepend(button);

    chapterTitle.addEventListener('click', function () {
      // Show/hide chapter content (finding all siblings until next h4 or end)
      const nextSiblings = [];
      let nextSibling = this.nextElementSibling;
      while (nextSibling) {
        nextSiblings.push(nextSibling);
        nextSibling = nextSibling.nextElementSibling;
      }
      for (const sibling of nextSiblings) {
        if (sibling.tagName.toLowerCase() === 'h4') {
          break;
        }
        sibling.style.display = sibling.style.display === 'none' ? '' : 'none';
        sizeBox();
      }

      // Change show/hide chapter button
      hide = !hide;
      const chapterButton = chapterTitle.querySelector('button.details-cmd');
      if (hide) {
        chapterButton.innerHTML = p.img_on_txt;
        chapterButton.setAttribute('value', p.img_on_txt);
        chapterButton.setAttribute('aria-label', p.img_on_alt);
        return;
      }
      chapterButton.innerHTML = p.img_off_txt;
      chapterButton.setAttribute('value', p.img_off_txt);
      chapterButton.setAttribute('aria-label', p.img_off_alt);
    });
  };

  // Compose help box

  helpBox.classList.add('help-box');
  for (const element of helpBox.querySelectorAll(':scope >hr')) {
    element.remove();
  }
  for (const chapter of helpBox.querySelectorAll(':scope h4')) {
    chapterToggler(chapter);
  }

  // Hide help chapters' content
  const firstH4 = helpBox.querySelector('h4');
  if (firstH4) {
    let nextSibling = firstH4.nextElementSibling;
    while (nextSibling) {
      if (nextSibling.tagName.toLowerCase() !== 'h4') {
        nextSibling.style.display = 'none';
      }
      nextSibling = nextSibling.nextElementSibling;
    }
  }

  sizeBox();

  // Compose help button
  const helpButton = helpButtonTemplate.cloneNode(true);
  helpButton.addEventListener('click', (event) => {
    event.preventDefault();
    return toggle();
  });
  document.getElementById('content')?.append(helpButton);

  // listen for scroll
  const helpButtonElement = document.getElementById('help-button');
  if (!helpButtonElement) {
    return;
  }
  const threshold = helpButtonElement.clientHeight / 2;

  if (window.scrollY >= threshold) {
    helpButtonElement.classList.add('floatable');
  } else {
    helpButtonElement.classList.remove('floatable');
  }

  const positionButton = () => {
    if (helpButtonElement.classList.contains('floatable')) {
      helpButtonElement.style.top = '0';
      return;
    }
    const bodyRect = document.body.getBoundingClientRect();
    const elemRect = helpBox.getBoundingClientRect();
    const offset = elemRect.top - bodyRect.top;
    helpButtonElement.style.top = `${offset}px`;
  };

  // Add scroll event listener
  window.addEventListener('scroll', () => {
    // Check if the scroll position is greater than or equal to the target position
    if (window.scrollY >= threshold) {
      helpButtonElement.classList.add('floatable');
    } else {
      helpButtonElement.classList.remove('floatable');
    }
    // Position button
    positionButton();
  });
};

/**
 * Enables the shift/alt click on selection of checkboxes.
 *
 * Shift modifier key will extend selection (on/off)
 * Alt modifier key will reverse selection
 *
 * @param      {string}  selector  The selector
 */
dotclear.enableShiftClick = (selector) => {
  // Inspired by https://codepen.io/danielhoppener/pen/xxKVbey
  const checkboxes = document.querySelectorAll(selector);
  let lastChecked;

  /**
   * @param      {PointerEvent}  event  The pointer event
   */
  const handleCheck = (event) => {
    let inBetween = false;
    if (event.shiftKey) {
      // Extend selection (on/off)
      for (const checkbox of checkboxes) {
        if (checkbox === event.currentTarget || checkbox === lastChecked) {
          inBetween = !inBetween;
        }
        if (inBetween) {
          checkbox.checked = event.currentTarget.checked;
        }
      }
    } else if (event.altKey) {
      // Reverse selection
      for (const checkbox of checkboxes) {
        checkbox.checked = !checkbox.checked;
      }
      event.currentTarget.checked = !event.currentTarget.checked;
    }

    lastChecked = event.currentTarget;
  };

  for (const checkbox of checkboxes) checkbox.addEventListener('click', handleCheck);
};

/**
 * Cope with the enter key in a form.
 *
 * @param      {string}  frm_id     The form identifier
 * @param      {string}  ok_id      The ok identifier
 * @param      {string}  cancel_id  The cancel identifier
 */
dotclear.enterKeyInForm = (frm_id, ok_id, cancel_id) => {
  const submitElement = document.querySelector(ok_id);
  if (submitElement) {
    document.querySelector(`${frm_id}:not(${cancel_id})`)?.addEventListener('keyup', (event) => {
      if (event.key !== 'Enter' || submitElement.disabled) return;
      event.preventDefault();
      event.stopPropagation();
      submitElement.click();
    });
  }
};

/**
 * Control the activation of a submit button depending on a list of checkboxes
 *
 * @param      {string}          chkboxes    The CSS string selector for checkboxes to control submit
 * @param      {string}          target      The CSS string selector of the submit button
 * @param      {boolean}         reset       Remove previous EventListener before adding new one
 */
dotclear.condSubmit = (chkboxes, target, reset = false) => {
  const checkboxes = Array.from(document.querySelectorAll(chkboxes));
  const submitButt = document.querySelector(target);
  if (checkboxes.length === 0 || submitButt === null) {
    return;
  }

  const setButtonState = () => {
    // Update target state
    submitButt.disabled = !checkboxes.some((checkbox) => checkbox.checked);
    if (submitButt.disabled) {
      submitButt.classList.add('disabled');
    } else {
      submitButt.classList.remove('disabled');
    }
  };

  // Set initial state
  setButtonState();

  for (const checkbox of checkboxes) {
    if (reset) {
      checkbox.removeEventListener('change', setButtonState);
    }
    checkbox.addEventListener('change', setButtonState);
  }
};

/**
 * Hides the lockable div in a page.
 *
 * The given div must have a `.lockable` class
 */
dotclear.hideLockable = () => {
  const lockableDivs = document.querySelectorAll('div.lockable');
  for (const lockableDiv of lockableDivs) {
    const formNotes = lockableDiv.querySelectorAll('p.form-note');
    for (const formNote of formNotes) formNote.style.display = 'none';
    const inputs = lockableDiv.querySelectorAll('input, textarea');
    for (const input of inputs) {
      // Prepare lock/unlock button
      const position = input.tagName === 'TEXTAREA' ? 'right: 4px' : `left: ${input.offsetWidth - 24}px`;
      const button = dotclear.htmlToNode(
        `<button type="button" style="position: absolute; ${position}; top: ${input.tagName === 'TEXTAREA' ? '4px' : '1.6em'}; border: none; background: transparent; padding: 0; margin: 0;"><img src="images/locker.svg" alt="${dotclear.msg.click_to_unlock}" style="width: 1.4em" class="mark mark-locked"></button>`,
      );
      button.addEventListener('click', () => {
        button.style.display = 'none';
        input.readOnly = false;
        for (const formNote of formNotes) formNote.style.display = 'block';
      });

      // Add button to input/textarea
      input.readOnly = true;
      input.parentElement.style.position = 'relative';
      input.before(button);
    }
  }
};

/**
 * Reverse the checked status of a list of elements
 *
 * @param      {(jQuery|NodeList|string|array)}  target  The target
 */
dotclear.toggleCheck = (target) => {
  for (const item of dotclear.nodes(target)) {
    if (item?.checked !== undefined) {
      item.checked = !item.checked;
    }
  }
};

/**
 * Set the checked status of a list of elements
 *
 * @param      {(jQuery|NodeList|string|array)}  target  The target
 * @param      {boolean}                         status  The checked status
 */
dotclear.setChecked = (target, status) => {
  for (const item of dotclear.nodes(target)) {
    if (item?.checked !== undefined) {
      item.checked = status;
    }
  }
};

/**
 * Set the checked status of a list of elements to false
 *
 * @param      {(jQuery|NodeList|string|array)}  target  The target
 */
dotclear.unCheck = (target) => dotclear.setChecked(target, false);

/**
 * Set the checked status of a list of elements to true
 *
 * @param      {(jQuery|NodeList|string|array)}  target  The target
 */
dotclear.check = (target) => dotclear.setChecked(target, true);

/**
 * Add checkboxes helper buttons at the end in an area
 *
 * @param      {(Element)}          area        The DOM Element for area element
 * @param      {(jQuery|NodeList)}  target      The jQuery objet or NodeList for target checkboxes
 * @param      {string}             checkboxes  The CSS string selector for checkboxes to control submit
 * @param      {string}             submit      The CSS string selector of the submit button
 */
dotclear.checkboxesHelpers = (area, target, checkboxes, submit) => {
  const form = area.closest('form');
  if (!form) return;

  // Prepare buttons
  const btn_all = dotclear.htmlToNode(
    `<button type="button" class="checkbox-helper select-all">${dotclear.msg.select_all}</button>`,
  );
  btn_all.addEventListener('click', (event) => {
    dotclear.check(target !== undefined ? target : form.querySelectorAll('input[type="checkbox"]:not(:disabled)'));
    if (checkboxes !== undefined && submit !== undefined) {
      dotclear.condSubmit(checkboxes, submit);
    }
    event.preventDefault();
    return false;
  });
  const btn_none = dotclear.htmlToNode(
    `<button type="button" class="checkbox-helper select-none">${dotclear.msg.no_selection}</button>`,
  );
  btn_none.addEventListener('click', (event) => {
    dotclear.unCheck(target !== undefined ? target : form.querySelectorAll('input[type="checkbox"]:not(:disabled)'));
    if (checkboxes !== undefined && submit !== undefined) {
      dotclear.condSubmit(checkboxes, submit);
    }
    event.preventDefault();
    return false;
  });
  const btn_invert = dotclear.htmlToNode(
    `<button type="button" class="checkbox-helper select-reverse">${dotclear.msg.invert_sel}</button>`,
  );
  btn_invert.addEventListener('click', (event) => {
    dotclear.toggleCheck(target !== undefined ? target : form.querySelectorAll('input[type="checkbox"]:not(:disabled)'));
    if (checkboxes !== undefined && submit !== undefined) {
      dotclear.condSubmit(checkboxes, submit);
    }
    event.preventDefault();
    return false;
  });

  // Add buttons
  area.classList.add('form-buttons');
  area.append(document.createTextNode(dotclear.msg.to_select));
  area.append(btn_all);
  area.append(btn_none);
  area.append(btn_invert);
};

/**
 * Ask confirmation before destructive operation (posts deletion)
 */
dotclear.postsActionsHelper = () => {
  document.getElementById('form-entries')?.addEventListener('submit', function (event) {
    if (this.querySelector('select[name="action"]')?.value === 'delete') {
      const nb = this.querySelectorAll('input[name="entries[]"]:checked')?.length;
      if (nb) {
        if (window.confirm(dotclear.msg.confirm_delete_posts.replace('%s', nb))) return true;
        event.preventDefault();
        return false;
      }
    }
  });
};

/**
 * Ask confirmation before destructive operation (comments deletion)
 */
dotclear.commentsActionsHelper = () => {
  document.getElementById('form-comments')?.addEventListener('submit', function (event) {
    if (this.querySelector('select[name="action"]')?.value === 'delete') {
      const nb = this.querySelectorAll('input[name="comments[]"]:checked')?.length;
      if (nb) {
        if (window.confirm(dotclear.msg.confirm_delete_comments.replace('%s', nb))) return true;
        event.preventDefault();
        return false;
      }
    }
  });
};

/**
 * Add outgoing link indicators
 *
 * @param      {string}  target  The CSS selector target
 */
dotclear.outgoingLinks = (target) => {
  const elements = document.querySelectorAll(target);
  for (const element of elements) {
    if (
      !(
        (element.hostname &&
          element.hostname !== location.hostname &&
          !element.classList.contains('modal') &&
          !element.classList.contains('modal-image')) ||
        element.classList.contains('outgoing')
      )
    ) {
      continue;
    }
    element.title = `${element.title} (${dotclear.msg.new_window})`;
    if (!element.classList.contains('outgoing')) {
      element.innerHTML += '&nbsp;<img class="outgoing-js" src="images/outgoing-link.svg" alt="">';
      element.classList.add('outgoing');
    }
    element.addEventListener('click', (e) => {
      e.preventDefault();
      window.open(element.href);
    });
  }
};

/**
 * Add headers on each cells (responsive tables)
 *
 * @param      {Element}   table         The table
 * @param      {string}    selector      The selector
 * @param      {number}    [offset=0]    The offset = number of firsts columns to ignore
 * @param      {boolean}   [thead=false] True if titles are in thead rather than in the first tr of the body
 */
dotclear.responsiveCellHeaders = (table, selector, offset = 0, thead = false) => {
  if (table) {
    try {
      const THarray = [];
      const ths = table.getElementsByTagName('th');
      for (const th of ths) {
        for (let colspan = th.colSpan; colspan > 0; colspan--) {
          THarray.push(th.innerText.replace('▶', ''));
        }
      }
      const styleElm = document.createElement('style');
      let styleSheet;
      document.head.appendChild(styleElm);
      styleSheet = styleElm.sheet;
      for (let i = offset; i < THarray.length; i++) {
        styleSheet.insertRule(
          `${selector} td:nth-child(${i + 1})::before {content:"${THarray[i]} ";}`,
          styleSheet.cssRules.length,
        );
      }
      table.className += `${table.className === '' ? '' : ' '}rch${thead ? ' rch-thead' : ''}`;
    } catch (e) {
      if (dotclear.debug) console.log(`responsiveCellHeaders(): ${e}`);
    }
  }
};

// Badge helper

/**
 * Badge helper
 *
 * @param      {(string|Element|NodeList|jQuery)}  elt    The element (selector string as in CSS, DOM Element, NodeList, jQuery object)
 * @param      {{sibling: boolean,
 *               id: string,
 *               remove: boolean,
 *               value: string|number,
 *               inline: boolean,
 *               icon: boolean,
 *               type: string,
 *               left: boolean,
 *               noborder: boolean,
 *               small: boolean,
 *               classes: string}}        [options=null]  The options
 *
 * @param      [options.sibling=false]    Define if the given element is a sibling of the badge or it's parent
 *                                        true: use elt.after() to add badge
 *                                        false: use elt.parent().append() to add badge (default)
 * @param      [options.id='default']     Badge unique class
 *                                        this class will be used to delete all
 *                                        corresponding badge (used for removing and updating)
 * @param      [options.remove=false]     Will remove the badge if set to true
 * @param      [options.value=null]       Badge value
 * @param      [options.inline=false]     If set to true, the badge is an inline element (useful for menu item)
 *                                        rather than a block
 * @param      [options.icon=false]       If set to true, the badge is attached to a dashboard icon
 *                                        (needed for correct positionning)
 * @param      [options.type='']          Override default background (which may vary)
 *                                        by default badge background are soft grey for dashboard icons (see opt.icon) and
 *                                        bright red for all other elements, possible values:
 *                                        'std':  bright red
 *                                        'info': blue
 *                                        'soft': soft grey
 * @param      [options.left=false]       Display badge on the left rather than on the right (unused for inline badge)
 * @param      [options.noborder=false]   Do not display the badge border
 * @param      [options.small=false]      Use a smaller font-size
 * @param      [options.classes='']       Additionnal badge classes
 */
dotclear.badge = (elt, options = null) => {
  const target = dotclear.nodes(elt);

  if (!target.length) return;

  // Cope with options
  const opt = Object.assign(
    {
      sibling: false,
      id: 'default',
      remove: false,
      value: null,
      inline: false,
      icon: false,
      type: '',
      left: false,
      noborder: false,
      small: false,
      classes: '',
    },
    options,
  );

  // Set some constants
  const classid = `span.badge.badge-${opt.id}`; // Pseudo unique class

  for (const item of target) {
    // Set badgeable class to target parent's (if sibling) or target itself, if it is necessary
    const parent = options.sibling ? item.parentNode : item;
    if (!opt.inline && !opt.remove && !parent.classList.contains('badgeable')) parent.classList.add('badgeable');

    // Remove existing badge if exists
    const badge = opt.sibling ? parent.querySelector(classid) : item.querySelector(classid);
    if (badge) badge.remove();
  }

  // Return if no new badge to add
  if (!(!opt.remove && opt.value !== null)) return;

  // Compose badge classes
  const classes = ['badge'];
  classes.push(`badge-${opt.id}`);
  classes.push(opt.inline ? 'badge-inline' : 'badge-block');
  if (opt.icon) classes.push('badge-icon');
  if (opt.type) classes.push(`badge-${opt.type}`);
  if (opt.left) classes.push('badge-left');
  if (opt.noborder) classes.push('badge-noborder');
  if (opt.small) classes.push('badge-small');
  if (opt.classes) classes.push(`${opt.classes}`);

  // Compose badge
  const template = dotclear.htmlToNode(`<span class="${classes.join(' ')}" aria-hidden="true">${opt.value}</span>`);
  for (const item of target) {
    const element = template.cloneNode(true);
    if (opt.sibling) item.after(element);
    else item.append(element);
  }
};

/**
 * Password helper
 *
 * Add a show/hide button to each password field in a page
 */
dotclear.passwordHelpers = () => {
  const togglePasswordHelper = (e) => {
    e.preventDefault();
    const button = e.currentTarget;
    const isPasswordShown = button.classList.contains('pw-hide');
    const buttonContent = isPasswordShown ? dotclear.msg.show_password : dotclear.msg.hide_password;

    button.classList.toggle('pw-hide', !isPasswordShown);
    button.classList.toggle('pw-show', isPasswordShown);

    button.previousElementSibling.setAttribute('type', isPasswordShown ? 'password' : 'text');
    button.setAttribute('title', buttonContent);
    button.querySelector('span').textContent = buttonContent;
  };

  // Compose button
  const buttonTemplate = dotclear.htmlToNode(
    `<button type="button" class="pw-show" title="${dotclear.msg.show_password}"><span class="sr-only">${dotclear.msg.show_password}</span></button>`,
  );

  const passwordFields = document.querySelectorAll('input[type=password]');

  for (const passwordField of passwordFields) {
    const button = buttonTemplate.cloneNode(true);
    passwordField.after(button);
    passwordField.classList.add('pwd_helper');
    button.addEventListener('click', togglePasswordHelper);
  }
};

// REST services helper
dotclear.servicesOff = dotclear.data.servicesOff || false;
dotclear.servicesUri = dotclear.data.servicesUri || 'index.php?process=Rest';

/**
 * Call REST service function
 *
 * @param      {string}            fn                                                            The REST function name
 * @param      {Function}          [onSuccess=(_data)=>{}]                                       On success
 * @param      {Function}          [onError=(_error)=>{if(dotclear.debug)console.log(_error);}]  On error
 * @param      {(boolean|string)}  [get=true]                                                    True if GET, false if POST method
 * @param      {Object}            [params={}]                                                   The parameters
 */
dotclear.services = (
  fn, // REST method
  onSuccess = (_data) => {
    // Used when fetch is successful
  },
  onError = (_error) => {
    // Used when fetch failed
    if (dotclear.debug) console.log(_error);
  },
  get = true, // Use GET method if true, POST if false
  params = {}, // Optional parameters
) => {
  if (dotclear.servicesOff) return;
  const service = new URL(dotclear.servicesUri, window.location.origin + window.location.pathname);
  dotclear.mergeDeep(params, { f: fn, xd_check: dotclear.nonce });
  const init = { method: get ? 'GET' : 'POST' };
  // Cope with parameters
  // --------------------
  // Warning: cope only with single level object (key → value)
  // Use JSON.stringify to push complex object in Javascript
  // Use json_decode(, [true]) to decode complex object in PHP (use true as 2nd param if key-array)
  if (get) {
    // Add parameters to query part of URL
    const data = new URLSearchParams(service.search);
    for (const key of Object.keys(params)) data.append(key, params[key]);
    service.search = data.toString();
  } else {
    // Add parameters to body part of request
    const data = new FormData();
    for (const key of Object.keys(params)) data.append(key, params[key]);
    init.body = data;
  }
  fetch(service, init)
    .then((promise) => {
      if (!promise.ok) {
        throw Error(promise.statusText);
      }
      // Return a promise of text representation of body -> response
      return promise.text();
    })
    .then((response) => onSuccess(response))
    .catch((error) => onError(error));
};

/**
 * Call REST service function, using GET method
 *
 * @param      {string}      fn                                                            The function
 * @param      {Function}    [onSuccess=(_payload)=>{}]                                    On success
 * @param      {Object}      [params={}]                                                   The parameters
 * @param      {Function}    [onError=(_error)=>{if(dotclear.debug)console.log(_error);}]  On error
 */
dotclear.servicesGet = (
  fn, // REST method
  onSuccess = (_payload) => {
    // Used when fetch is successful
  },
  params = {}, // Optional parameters
  onError = (_error) => {
    // Used when fetch failed
    if (dotclear.debug) console.log(_error);
  },
) => {
  dotclear.services(fn, onSuccess, onError, true, params);
};

/**
 * Call REST service function, using POST method
 *
 * @param      {string}      fn                                                            The function
 * @param      {Function}    [onSuccess=(_payload)=>{}]                                    On success
 * @param      {Object}      [params={}]                                                   The parameters
 * @param      {Function}    [onError=(_error)=>{if(dotclear.debug)console.log(_error);}]  On error
 */
dotclear.servicesPost = (
  fn, // REST method
  onSuccess = (_payload) => {
    // Used when fetch is successful
  },
  params = {}, // Optional parameters
  onError = (_error) => {
    // Used when fetch failed
    if (dotclear.debug) console.log(_error);
  },
) => {
  dotclear.services(fn, onSuccess, onError, false, params);
};

// REST services helpers, JSON only aliases

/**
 * Call REST service function, using JSON format
 *
 * @param      {string}            fn                                                            The REST function name
 * @param      {Function}          [onSuccess=(_data)=>{}]                                       On success
 * @param      {Function}          [onError=(_error)=>{if(dotclear.debug)console.log(_error);}]  On error
 * @param      {(boolean|string)}  [get=true]                                                    True if GET, false if POST method
 * @param      {Object}            [params={}]                                                   The parameters
 */
dotclear.jsonServices = (
  fn, // REST method
  onSuccess = (_payload) => {
    // Used when fetch is successful
  },
  onError = (_error) => {
    // Used when fetch failed
    if (dotclear.debug) console.log(_error);
  },
  get = true, // Use GET method if true, POST if false
  params = {}, // Optional parameters
) => {
  params.json = 1;
  dotclear.services(
    fn,
    (data) => {
      try {
        const response = JSON.parse(data);
        if (response?.success) {
          onSuccess(response.payload);
        } else {
          const msg = dotclear.debug && response?.message ? response.message : 'Dotclear REST server error';
          if (dotclear.debug) console.log(`jsonServices(): ${msg}`);
          onError(msg);
          return;
        }
      } catch (error) {
        if (dotclear.debug) console.log(`jsonServices(): ${error}`, fn, data);
        onError(error);
      }
    },
    (error) => onError(error),
    get,
    params,
  );
};

/**
 * Call REST service function, using GET method and JSON format
 *
 * @param      {string}      fn                                                            The function
 * @param      {Function}    [onSuccess=(_payload)=>{}]                                    On success
 * @param      {Object}      [params={}]                                                   The parameters
 * @param      {Function}    [onError=(_error)=>{if(dotclear.debug)console.log(_error);}]  On error
 */
dotclear.jsonServicesGet = (
  fn, // REST method
  onSuccess = (_payload) => {
    // Used when fetch is successful
  },
  params = {}, // Optional parameters
  onError = (_error) => {
    // Used when fetch failed
    if (dotclear.debug) console.log(_error);
  },
) => {
  dotclear.jsonServices(fn, onSuccess, onError, true, params);
};

/**
 * Call REST service function, using POST method and JSON format
 *
 * @param      {string}      fn                                                            The function
 * @param      {Function}    [onSuccess=(_payload)=>{}]                                    On success
 * @param      {Object}      [params={}]                                                   The parameters
 * @param      {Function}    [onError=(_error)=>{if(dotclear.debug)console.log(_error);}]  On error
 */
dotclear.jsonServicesPost = (
  fn, // REST method
  onSuccess = (_payload) => {
    // Used when fetch is successful
  },
  params = {}, // Optional parameters
  onError = (_error) => {
    // Used when fetch failed (any reason)
    if (dotclear.debug) console.log(_error);
  },
) => {
  dotclear.jsonServices(fn, onSuccess, onError, false, params);
};

// Debug mode
dotclear.debug = dotclear.data.debug || false;

// Get other DATA
Object.assign(dotclear, dotclear.getData('dotclear'));
Object.assign(dotclear.msg, dotclear.getData('dotclear_msg'));

/* On document ready
-------------------------------------------------------- */
dotclear.ready(() => {
  // DOM ready and content loaded

  const body = document.querySelector('body');
  const header = document.querySelector('#header') ? document.querySelector('#header') : document.querySelector('h1');

  // Set theme class
  body.classList.add(`${dotclear.data.theme}-mode`);
  dotclear.data.darkMode = dotclear.data.theme === 'dark' ? 1 : 0;
  if (document.documentElement.getAttribute('data-theme') === '') {
    // Theme is set to automatic, keep an eye on system change
    dotclear.theme_OS = window.matchMedia('(prefers-color-scheme: dark)');
    const switchScheme = (e) => {
      const theme = e.matches ? 'dark' : 'light';
      if (theme === dotclear.data.theme) {
        return;
      }
      body.classList.remove(`${dotclear.data.theme}-mode`);
      dotclear.data.theme = theme;
      body.classList.add(`${dotclear.data.theme}-mode`);
      document.documentElement.style.setProperty('--dark-mode', dotclear.data.theme === 'dark' ? 1 : 0);
    };
    try {
      dotclear.theme_OS.addEventListener('change', (e) => switchScheme(e));
    } catch (e) {
      if (dotclear.debug) console.log(`matchMedia/prefers-color-scheme: ${e}`);
      try {
        dotclear.theme_OS.addListener((e) => switchScheme(e));
      } catch (e) {
        if (dotclear.debug) console.log(`matchMedia/prefers-color-scheme: ${e}`);
        try {
          dotclear.theme_OS.onchange((e) => switchScheme(e));
        } catch (e) {
          if (dotclear.debug) console.log(`matchMedia/prefers-color-scheme: ${e}`);
        }
      }
    }
  }

  // Accssibility flags
  dotclear.animationisReduced =
    window.matchMedia('(prefers-reduced-motion: reduce)') === true ||
    window.matchMedia('(prefers-reduced-motion: reduce)').matches === true;
  const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
  mediaQuery.onchange = (event) => {
    dotclear.animationisReduced = event.matches;
  };

  // Watch data-theme attribute modification
  const observer = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
      let theme;
      if (mutation.target.getAttribute('data-theme') === '') {
        theme = window.matchMedia('(prefers-color-scheme: dark)') ? 'dark' : 'light';
      } else {
        theme = mutation.target.getAttribute('data-theme');
      }
      body.classList.remove(`${dotclear.data.theme}-mode`);
      dotclear.data.theme = theme;
      body.classList.add(`${dotclear.data.theme}-mode`);
      document.documentElement.style.setProperty('--dark-mode', dotclear.data.theme === 'dark' ? 1 : 0);
    }
  });
  observer.observe(document.documentElement, {
    attributeFilter: ['data-theme'],
  });

  // Header double-click event
  if (dotclear.debug) {
    // debug mode: double click on header switch current theme
    header.addEventListener('dblclick', (_e) => {
      let { theme } = document.documentElement.dataset;
      if (theme == null || theme === '') {
        theme = window.matchMedia('(prefers-color-scheme: dark)') ? 'dark' : 'light';
      }
      // Set new theme, the application will be cope by the mutation observer (see above)
      document.documentElement.dataset.theme = theme === 'dark' ? 'light' : 'dark';
    });
  } else {
    // production mode: double click on header do ... nothing yet :-p
  }

  // Remove class no-js from html tag; cf style/default.css for examples
  body.classList.remove('no-js');
  body.classList.add('with-js');

  // Special management for first HTML comment found in markup (popover in footer), 1st level only
  for (const child of body.childNodes) {
    if (child.nodeType !== Node.COMMENT_NODE) {
      continue;
    }
    const data = child.data.replace(/ /g, '&nbsp;').replace(/\n/g, '<br>').replace(/\n/g, '<br>');
    const dcnet = document.querySelector('#footer a');
    if (!dcnet) {
      continue;
    }
    const tooltip = dotclear.htmlToNode(
      `<span class="tooltip" aria-hidden="true">${dcnet.getAttribute('title') || ''}${data}</span>`,
    );
    dcnet.append(tooltip);
  }

  // manage outgoing links
  dotclear.outgoingLinks('a');

  // Popups: dealing with Escape key fired
  document.querySelector('#dotclear-admin.popup')?.addEventListener('keyup', (event) => {
    if (event.key !== 'Escape') {
      return;
    }
    event.preventDefault();
    window.close();
    return false;
  });

  // Blog switcher
  document.getElementById('switchblog')?.addEventListener('change', function () {
    this.form.submit();
  });

  // Menu state
  for (const menu of ['blog', 'system', 'plugins', 'favorites']) {
    const fav = menu === 'favorites';
    dotclear.toggleWithLegend(document.querySelector(`#${menu}-menu h3`), document.querySelectorAll(`#${menu}-menu ul`), {
      legend_click: true,
      user_pref: `dc_${menu}_menu`,
      hide: !fav,
      reverse_user_pref: fav,
    });
  }

  // Help viewer
  dotclear.helpViewer('#help');

  // Password helpers
  dotclear.passwordHelpers();

  // Cope with ellipsis'ed cells
  for (const element of document.querySelectorAll('table .maximal')) {
    if (element.offsetWidth < element.scrollWidth && element.title === '') {
      element.title = element.innerText;
      element.classList.add('ellipsis');
    }
  }
  for (const element of document.querySelectorAll('table .maximal.ellipsis a')) {
    if (element.title === '') element.title = element.innerText;
  }

  // Advanced users, hide secondary information
  if (dotclear.data.hideMoreInfo) {
    for (const element of document.querySelectorAll('.more-info,.form-note:not(.warn,.warning,.info)'))
      element.classList.add('no-more-info');
  }

  // Main menu collapser
  const wrapper = document.getElementById('wrapper');
  const hideMainMenu = 'hide_main_menu';

  if (wrapper) {
    // Sidebar separator
    document.getElementById('collapser')?.addEventListener('click', (event) => {
      event.preventDefault();
      if (wrapper.classList.contains('hide-mm')) {
        // Show sidebar
        wrapper.classList.remove('hide-mm');
        dotclear.dropLocalData(hideMainMenu);
        return;
      }
      // Hide sidebar
      wrapper.classList.add('hide-mm');
      dotclear.storeLocalData(hideMainMenu, true);
    });
    // Cope with current stored state of collapser
    if (dotclear.readLocalData(hideMainMenu) === true) {
      wrapper.classList.add('hide-mm');
    } else {
      wrapper.classList.remove('hide-mm');
    }
  }

  // Scroll to top management
  document.addEventListener('scroll', () => {
    const gototopButton = document.getElementById('gototop');
    if (gototopButton) {
      gototopButton.style.display = document.querySelector('html').scrollTop === 0 ? 'none' : 'block';
    }
  });
  document.getElementById('gototop')?.addEventListener('click', (event) => {
    if (dotclear.animationisReduced) {
      // Scroll to top instantly
      document.querySelector('html').scrollTop = 0;
    } else {
      // Scroll to top smoothly
      const scrollToTop = (duration) => {
        // cancel if already on top
        if (document.scrollingElement.scrollTop === 0) return;

        // if duration is zero, no animation
        if (duration === 0) {
          document.scrollingElement.scrollTop = 0;
          return;
        }

        const cosParameter = document.scrollingElement.scrollTop / 2;
        let scrollCount = 0;
        let oldTimestamp = null;

        const step = (newTimestamp) => {
          if (oldTimestamp !== null) {
            scrollCount += (Math.PI * (newTimestamp - oldTimestamp)) / duration;
            if (scrollCount >= Math.PI) {
              document.scrollingElement.scrollTop = 0;
              return;
            }
            document.scrollingElement.scrollTop = cosParameter + cosParameter * Math.cos(scrollCount);
          }
          oldTimestamp = newTimestamp;
          window.requestAnimationFrame(step);
        };
        window.requestAnimationFrame(step);
      };
      scrollToTop(800);
    }
    event.preventDefault();
  });

  // Menu command
  const searchinput = document.getElementById('qx');
  if (searchinput) {
    // Intercept quick menu prefix key
    const quickMenuPrefix = dotclear.data.quickMenuPrefix || ':';
    window.addEventListener('keyup', (event) => {
      if (!document.activeElement.nodeName || dotclear.acceptsKeyboardInput(document.activeElement)) {
        return;
      }
      if (event.key !== quickMenuPrefix) return;
      if (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey || event.isComposing) return;
      event.preventDefault();
      searchinput.setAttribute('value', quickMenuPrefix);
      searchinput.setSelectionRange(1, 1);
      searchinput.focus();
    });
    // Add direct submit on menu choice
    const menuList = document.querySelectorAll('#menulist option');
    if (menulist) {
      searchinput.addEventListener('change', (event) => {
        const found = [...menuList].find((opt) => opt.value === searchinput.value);
        if (found) {
          event.preventDefault();
          searchinput.form?.submit();
        }
      });
    }
  }

  // Go back (aka Cancel) button
  for (const back of document.querySelectorAll('.go-back'))
    back.addEventListener('click', () => {
      history.back();
    });

  // Navigation arrow keys (left/right)
  const goprev = document.querySelector('.nav_prevnext > .prev');
  if (goprev) {
    window.addEventListener('keyup', (e) => {
      if (!document.activeElement.nodeName || dotclear.acceptsKeyboardInput(document.activeElement)) {
        return;
      }
      if (e.key !== 'ArrowLeft') return;
      if (e.altKey || e.ctrlKey || e.metaKey || e.shiftKey || e.isComposing) return;
      e.preventDefault();
      goprev.click();
    });
  }
  const gonext = document.querySelector('.nav_prevnext > .next');
  if (gonext) {
    window.addEventListener('keyup', (e) => {
      if (!document.activeElement.nodeName || dotclear.acceptsKeyboardInput(document.activeElement)) {
        return;
      }
      if (e.key !== 'ArrowRight') return;
      if (e.altKey || e.ctrlKey || e.metaKey || e.shiftKey || e.isComposing) return;
      e.preventDefault();
      gonext.click();
    });
  }
});
