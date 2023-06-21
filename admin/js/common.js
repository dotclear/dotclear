/*global $, dotclear */
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
document.documentElement.style.setProperty('--dark-mode', dotclear.data.theme === 'dark' ? 1 : 0);

/* jQuery extensions
-------------------------------------------------------- */
$.fn.check = function () {
  return this.each(function () {
    if (this.checked != undefined) {
      this.checked = true;
    }
  });
};

$.fn.unCheck = function () {
  return this.each(function () {
    if (this.checked != undefined) {
      this.checked = false;
    }
  });
};

$.fn.setChecked = function (status) {
  return this.each(function () {
    if (this.checked != undefined) {
      this.checked = status;
    }
  });
};

$.fn.toggleCheck = function () {
  return this.each(function () {
    if (this.checked != undefined) {
      this.checked = !this.checked;
    }
  });
};

$.fn.enableShiftClick = function () {
  this.on('click', function (event) {
    if (event.shiftKey) {
      if (dotclear.lastclicked != '') {
        let range;
        const trparent = $(this).parents('tr');
        const id = `#${dotclear.lastclicked}`;
        range = trparent.nextAll(id).length == 0 ? trparent.prevUntil(id) : trparent.nextUntil(id);
        range.find('input[type=checkbox]').setChecked(dotclear.lastclickedstatus);
        this.checked = dotclear.lastclickedstatus;
      }
    } else {
      dotclear.lastclicked = $(this).parents('tr')[0].id;
      dotclear.lastclickedstatus = this.checked;
    }
    return true;
  });
};

$.fn.toggleWithLegend = function (target, s) {
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
  const p = $.extend(defaults, s);
  if (!target) {
    return this;
  }
  const set_user_pref = p.hide ^ p.reverse_user_pref;
  if (p.user_pref && p.unfolded_sections !== undefined && p.user_pref in p.unfolded_sections) {
    p.hide = p.reverse_user_pref;
  }
  const toggle = (i) => {
    const b = $(i).get(0);
    if (p.hide) {
      b.firstChild.data = p.img_on_txt;
      b.setAttribute('value', p.img_on_txt);
      b.setAttribute('aria-label', p.img_on_alt);
      b.setAttribute('aria-expanded', false);
      target.addClass('hide');
    } else {
      b.firstChild.data = p.img_off_txt;
      b.setAttribute('value', p.img_off_txt);
      b.setAttribute('aria-label', p.img_off_alt);
      b.setAttribute('aria-expanded', true);
      target.removeClass('hide');
      if (p.fn) {
        p.fn.apply(target);
        p.fn = false;
      }
    }
    p.hide = !p.hide;
  };
  return this.each(function () {
    const b = document.createElement('button');
    b.setAttribute('type', 'button');
    b.className = 'details-cmd';
    b.value = p.img_on_txt;
    b.setAttribute('aria-label', p.img_on_alt);
    const t = document.createTextNode(p.img_on_txt);
    b.appendChild(t);

    const ctarget = p.legend_click ? this : b;
    $(ctarget).css('cursor', 'pointer');
    if (p.legend_click) {
      $(ctarget).find('label').css('cursor', 'pointer');
    }
    $(ctarget).on('click', (e) => {
      if (p.user_pref && set_user_pref) {
        $.post(
          dotclear.servicesUri,
          {
            f: 'setSectionFold',
            section: p.user_pref,
            value: p.hide ^ p.reverse_user_pref ? 1 : 0,
            xd_check: dotclear.nonce,
          },
          () => {
            /* void */
          },
        );
      }
      toggle(b);
      e.preventDefault();
      return false;
    });
    toggle($(b).get(0));
    $(this).prepend(b);
  });
};

(() => {
  $.expandContent = (opts) => {
    if (opts == undefined || opts.callback == undefined || typeof opts.callback !== 'function') {
      return;
    }
    if (opts.line != undefined) {
      multipleExpander(opts.line, opts.lines, opts.callback);
    }
    opts.lines.each(function () {
      singleExpander(this, opts.callback);
    });
  };
  const singleExpander = (line, callback) => {
    $(
      `<button type="button" class="details-cmd" aria-expanded="false" aria-label="${dotclear.img_plus_alt}">${dotclear.img_plus_txt}</button>`,
    )
      .on('click', function (e) {
        if (toggleArrow(this) !== '') {
          callback(line, '', e);
        }
        e.preventDefault();
      })
      .prependTo($(line).children().get(0)); // first td
  };
  const multipleExpander = (line, lines, callback) => {
    $(
      `<button type="button" class="details-cmd" aria-expanded="false" aria-label="${dotclear.img_plus_alt}">${dotclear.img_plus_txt}</button>`,
    )
      .on('click', function (e) {
        const action = toggleArrow(this);
        lines.each(function () {
          if (toggleArrow(this.firstChild.firstChild, action) !== '') {
            callback(this, action, e);
          }
        });
        e.preventDefault();
      })
      .prependTo($(line).children().get(0)); // first td
  };
  const toggleArrow = (button, action = '') => {
    if (action == '') {
      action = button.getAttribute('aria-label') == dotclear.img_plus_alt ? 'open' : 'close';
    }
    if (action == 'open' && button.getAttribute('aria-expanded') == 'false') {
      button.firstChild.data = dotclear.img_minus_txt;
      button.setAttribute('value', dotclear.img_minus_txt);
      button.setAttribute('aria-label', dotclear.img_minus_alt);
      button.setAttribute('aria-expanded', true);
    } else if (action == 'close' && button.getAttribute('aria-expanded') == 'true') {
      button.firstChild.data = dotclear.img_plus_txt;
      button.setAttribute('value', dotclear.img_plus_txt);
      button.setAttribute('aria-label', dotclear.img_plus_alt);
      button.setAttribute('aria-expanded', false);
    } else {
      return '';
    }
    return action;
  };
})();

$.fn.helpViewer = function () {
  if (this.length < 1) {
    return this;
  }
  const p = {
    img_on_txt: dotclear.img_plus_txt,
    img_on_alt: dotclear.img_plus_alt,
    img_off_txt: dotclear.img_minus_txt,
    img_off_alt: dotclear.img_minus_alt,
  };
  const toggle = () => {
    $('#content').toggleClass('with-help');
    $('p#help-button span a').text($('#content').hasClass('with-help') ? dotclear.msg.help_hide : dotclear.msg.help);
    sizeBox();
    return false;
  };
  const sizeBox = () => {
    this.css('height', 'auto');
    if ($('#wrapper').height() > this.height()) {
      this.css('height', `${$('#wrapper').height()}px`);
    }
  };
  const textToggler = (o) => {
    const b = $(`<button type="button" class="details-cmd" aria-label="${p.img_on_alt}">${p.img_on_txt}</button>`);
    o.css('cursor', 'pointer');
    let hide = true;
    o.prepend(' ').prepend(b);
    o.on('click', function () {
      $(this)
        .nextAll()
        .each(function () {
          if ($(this).is('h4')) {
            return false;
          }
          $(this).toggle();
          sizeBox();
          return true;
        });
      hide = !hide;
      const image = $(this).find('button.details-cmd');
      if (hide) {
        image.html(p.img_on_txt);
        image.attr('value', p.img_on_txt);
        image.attr('aria-label', p.img_on_alt);
        return;
      }
      image.html(p.img_off_txt);
      image.attr('value', p.img_off_txt);
      image.attr('aria-label', p.img_off_alt);
    });
  };
  this.addClass('help-box');
  this.find('>hr').remove();
  this.find('h4').each(function () {
    textToggler($(this));
  });
  this.find('h4:first').nextAll('*:not(h4)').hide();
  sizeBox();
  const img = $(`<p id="help-button"><span><a href="">${dotclear.msg.help}</a></span></p>`);
  img.on('click', (e) => {
    e.preventDefault();
    return toggle();
  });
  $('#content').append(img);
  // listen for scroll
  const peInPage = $('#help-button').offset().top;
  $('#help-button').addClass('floatable');
  const peInFloat = $('#help-button').offset().top - $(window).scrollTop();
  $('#help-button').removeClass('floatable');
  $(window).on('scroll', () => {
    if ($(window).scrollTop() >= peInPage - peInFloat) {
      $('#help-button').addClass('floatable');
    } else {
      $('#help-button').removeClass('floatable');
    }
  });
  return this;
};

/* Dotclear common methods
-------------------------------------------------------- */
dotclear.enterKeyInForm = (frm_id, ok_id, cancel_id) => {
  $(`${frm_id}:not(${cancel_id})`).on('keyup', (e) => {
    if (e.key == 'Enter' && $(ok_id).prop('disabled') !== true) {
      e.preventDefault();
      e.stopPropagation();
      $(ok_id).trigger('click');
    }
  });
};

dotclear.condSubmit = (chkboxes, target) => {
  const checkboxes = Array.from(document.querySelectorAll(chkboxes));
  const submitButt = document.querySelector(target);
  if (checkboxes.length === 0 || submitButt === null) {
    return;
  }

  // Set initial state
  submitButt.disabled = !checkboxes.some((checkbox) => checkbox.checked);
  if (submitButt.disabled) {
    submitButt.classList.add('disabled');
  } else {
    submitButt.classList.remove('disabled');
  }

  checkboxes.forEach((checkbox) => {
    checkbox.addEventListener('click', () => {
      // Update target state
      submitButt.disabled = !checkboxes.some((checkbox) => checkbox.checked);
      if (submitButt.disabled) {
        submitButt.classList.add('disabled');
      } else {
        submitButt.classList.remove('disabled');
      }
    });
  });
};

dotclear.hideLockable = () => {
  const lockableDivs = document.querySelectorAll('div.lockable');
  lockableDivs.forEach((lockableDiv) => {
    const formNotes = lockableDiv.querySelectorAll('p.form-note');
    formNotes.forEach((formNote) => (formNote.style.display = 'none'));
    const inputs = lockableDiv.querySelectorAll('input');
    inputs.forEach((input) => {
      input.disabled = true;
      input.style.width = `${input.offsetWidth - 14}px`;
      const image = document.createElement('img');
      image.src = 'images/locker.png';
      image.style.position = 'absolute';
      image.style.top = '1.8em';
      image.style.left = `${input.offsetWidth + 14}px`;
      image.alt = dotclear.msg.click_to_unlock;
      image.style.cursor = 'pointer';
      image.addEventListener('click', () => {
        image.style.display = 'none';
        input.disabled = false;
        input.style.width = `${input.offsetWidth + 14}px`;
        formNotes.forEach((formNote) => (formNote.style.display = 'block'));
      });
      input.parentElement.style.position = 'relative';
      input.after(image);
    });
  });
};

dotclear.checkboxesHelpers = (e, target, c, s) => {
  $(e).append(document.createTextNode(dotclear.msg.to_select));
  $(e).append(document.createTextNode(' '));
  $(`<button type="button" class="checkbox-helper select-all">${dotclear.msg.select_all}</button>`)
    .on('click', () => {
      if (target === undefined) {
        $(e).parents('form').find('input[type="checkbox"]:not(:disabled)').check();
      } else {
        target.check();
      }
      if (c !== undefined && s !== undefined) {
        dotclear.condSubmit(c, s);
      }
      return false;
    })
    .appendTo($(e));
  $(e).append(document.createTextNode(' '));
  $(`<button type="button" class="checkbox-helper select-none">${dotclear.msg.no_selection}</button>`)
    .on('click', () => {
      if (target === undefined) {
        $(e).parents('form').find('input[type="checkbox"]:not(:disabled)').unCheck();
      } else {
        target.unCheck();
      }
      if (c !== undefined && s !== undefined) {
        dotclear.condSubmit(c, s);
      }
      return false;
    })
    .appendTo($(e));
  $(e).append(document.createTextNode(' '));
  $(`<button type="button" class="checkbox-helper select-reverse">${dotclear.msg.invert_sel}</button>`)
    .on('click', () => {
      if (target === undefined) {
        $(e).parents('form').find('input[type="checkbox"]:not(:disabled)').toggleCheck();
      } else {
        target.toggleCheck();
      }
      if (c !== undefined && s !== undefined) {
        dotclear.condSubmit(c, s);
      }
      return false;
    })
    .appendTo($(e));
};

dotclear.postsActionsHelper = () => {
  $('#form-entries').on('submit', function () {
    const action = $(this).find('select[name="action"]').val();
    if (action === undefined) {
      return;
    }
    let checked = false;
    $(this)
      .find('input[name="entries[]"]')
      .each(function () {
        if (this.checked) {
          checked = true;
        }
      });
    if (!checked) {
      return false;
    }
    if (action == 'delete') {
      return window.confirm(dotclear.msg.confirm_delete_posts.replace('%s', $('input[name="entries[]"]:checked').length));
    }
    return true;
  });
};

dotclear.commentsActionsHelper = () => {
  $('#form-comments').on('submit', function () {
    const action = $(this).find('select[name="action"]').val();
    let checked = false;
    $(this)
      .find('input[name="comments[]"]')
      .each(function () {
        if (this.checked) {
          checked = true;
        }
      });
    if (!checked) {
      return false;
    }
    if (action == 'delete') {
      return window.confirm(dotclear.msg.confirm_delete_comments.replace('%s', $('input[name="comments[]"]:checked').length));
    }
    return true;
  });
};

// Outgoing links
dotclear.outgoingLinks = (target) => {
  const elements = document.querySelectorAll(target);
  elements.forEach((element) => {
    if (
      (element.hostname &&
        element.hostname != location.hostname &&
        !element.classList.contains('modal') &&
        !element.classList.contains('modal-image')) ||
      element.classList.contains('outgoing')
    ) {
      element.title = `${element.title} (${dotclear.msg.new_window})`;
      if (!element.classList.contains('outgoing')) {
        element.innerHTML += '&nbsp;<img class="outgoing-js" src="images/outgoing-link.svg" alt=""/>';
      }
      element.addEventListener('click', (e) => {
        e.preventDefault();
        window.open(element.href);
      });
    }
  });
};

/**
 * Add headers on each cells (responsive tables)
 *
 * @param      DOM elt   table         The table
 * @param      string    selector      The selector
 * @param      number    [offset=0]    The offset = number of firsts columns to ignore
 * @param      boolean   [thead=false] True if titles are in thead rather than in the first tr of the body
 */
dotclear.responsiveCellHeaders = (table, selector, offset = 0, thead = false) => {
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
    console.log(`responsiveCellHeaders(): ${e}`);
  }
};

// Badge helper
dotclear.badge = ($elt, options = null) => {
  // Cope with selector given as string or DOM element rather than a jQuery object
  if (typeof $elt === 'string' || $elt instanceof Element) {
    $elt = $($elt);
  }

  // Return if elt does not exist
  if (!$elt.length) return;

  // Cope with options
  const opt = $.extend(
    {
      /* sibling: define if the given element is a sibling of the badge or it's parent
       *  true: use $elt.after() to add badge
       *  false: use $elt.parent().append() to add badge (default)
       */
      sibling: false,
      /* id: badge unique class
       *  this class will be used to delete all corresponding badge (used for removing and updating)
       */
      id: 'default',
      /* remove: will remove the badge if set to true */
      remove: false,
      /* value: badge value */
      value: null,
      /* inline: if set to true, the badge is an inline element (useful for menu item) rather than a block */
      inline: false,
      /* icon: if set to true, the badge is attached to a dashboard icon (needed for correct positionning) */
      icon: false,
      /* type: Override default background (which may vary)
       *  by default badge background are soft grey for dashboard icons (see opt.icon) and bright red for all other elements
       *  possible values:
       *    'std':  bright red
       *    'info': blue
       *    'soft': soft grey
       */
      type: '',
      /* left: display badge on the left rather than on the right (unused for inline badge) */
      left: false,
      /* noborder: do not display the badge border */
      noborder: false,
      /* small: use a smaller font-size */
      small: false,
      /* classes: additionnal badge classes */
      classes: '',
    },
    options,
  );

  // Set some constants
  const classid = `span.badge.badge-${opt.id}`; // Pseudo unique class

  // Set badgeable class to elt parent's (if sibling) or elt itself, if it is necessary
  const $parent = opt.sibling ? $elt.parent() : $elt;
  if (!opt.inline && !opt.remove && !$parent.hasClass('badgeable')) {
    $parent.addClass('badgeable');
  }

  // Remove existing badge if exists
  const $badge = opt.sibling ? $parent.children(classid) : $elt.children(classid);
  if ($badge.length) {
    $badge.remove();
  }

  // Add the new badge if any
  if (!opt.remove && opt.value !== null) {
    // Compose badge classes
    const classes = ['badge'];
    classes.push(`badge-${opt.id}`);
    classes.push(opt.inline ? 'badge-inline' : 'badge-block');
    if (opt.icon) {
      classes.push('badge-icon');
    }
    if (opt.type) {
      classes.push(`badge-${opt.type}`);
    }
    if (opt.left) {
      classes.push('badge-left');
    }
    if (opt.noborder) {
      classes.push('badge-noborder');
    }
    if (opt.small) {
      classes.push('badge-small');
    }
    if (opt.classes) {
      classes.push(`${opt.classes}`);
    }
    const cls = classes.join(' ');
    // Compose badge
    const xml = `<span class="${cls}" aria-hidden="true">${opt.value}</span>`;
    if (opt.sibling) {
      // Add badge after it's sibling
      $elt.after(xml);
    } else {
      // Append badge to the elt
      $elt.append(xml);
    }
  }
};

// Password helper
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
  const buttonTemplate = new DOMParser().parseFromString(
    `<button type="button" class="pw-show" title="${dotclear.msg.show_password}"><span class="sr-only">${dotclear.msg.show_password}</span></button>`,
    'text/html',
  ).body.firstChild;

  const passwordFields = document.querySelectorAll('input[type=password]');

  for (const passwordField of passwordFields) {
    const button = buttonTemplate.cloneNode(true);
    passwordField.after(button);
    passwordField.classList.add('pwd_helper');
    button.addEventListener('click', togglePasswordHelper);
  }
};

// REST services helper
dotclear.servicesUri = dotclear.data.servicesUri || 'index.php?process=Rest';
dotclear.services = (
  fn, // REST method
  onSuccess = (_data) => {
    // Used when fetch is successful
  },
  onError = (_error) => {
    // Used when fetch failed
  },
  get = true, // Use GET method if true, POST if false
  params = {}, // Optional parameters
) => {
  const service = new URL(dotclear.servicesUri, window.location.origin + window.location.pathname);
  dotclear.mergeDeep(params, { f: fn, xd_check: dotclear.nonce });
  const init = { method: get ? 'GET' : 'POST' };
  if (get) {
    const data = new URLSearchParams(service.search);
    // Warning: cope only with single level object (key → value)
    Object.keys(params).forEach((key) => data.append(key, params[key]));
    service.search = data.toString();
  } else {
    const data = new FormData();
    // Warning: cope only with single level object (key → value)
    Object.keys(params).forEach((key) => data.append(key, params[key]));
    init.body = data;
  }
  fetch(service, init)
    .then((p) => {
      if (!p.ok) {
        throw Error(p.statusText);
      }
      return p.text();
    })
    .then((data) => onSuccess(data))
    .catch((error) => onError(error));
};
dotclear.servicesGet = (
  fn, // REST method
  onSuccess = (_payload) => {
    // Used when fetch is successful
  },
  params = {}, // Optional parameters
) => {
  dotclear.services(fn, onSuccess, (error) => console.log(error), true, params);
};
dotclear.servicesPost = (
  fn, // REST method
  onSuccess = (_payload) => {
    // Used when fetch is successful
  },
  params = {}, // Optional parameters
) => {
  dotclear.services(fn, onSuccess, (error) => console.log(error), false, params);
};
// REST services helpers, JSON only aliases
dotclear.jsonServices = (
  fn, // REST method
  onSuccess = (_payload) => {
    // Used when fetch is successful
  },
  onError = (_error) => {
    // Used when fetch failed
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
          console.log(dotclear.debug && response?.message ? response.message : 'Dotclear REST server error');
          return;
        }
      } catch (e) {
        if (dotclear.debug) {
          console.log(fn, data);
        }
        console.log(e);
      }
    },
    (error) => onError(error),
    get,
    params,
  );
};
dotclear.jsonServicesGet = (
  fn, // REST method
  onSuccess = (_payload) => {
    // Used when fetch is successful
  },
  params = {}, // Optional parameters
) => {
  dotclear.jsonServices(fn, onSuccess, (error) => console.log(error), true, params);
};
dotclear.jsonServicesPost = (
  fn, // REST method
  onSuccess = (_payload) => {
    // Used when fetch is successful
  },
  params = {}, // Optional parameters
) => {
  dotclear.jsonServices(fn, onSuccess, (error) => console.log(error), false, params);
};

/* On document ready
-------------------------------------------------------- */
$(() => {
  // Debug mode
  dotclear.debug = dotclear.data.debug || false;

  // Get other DATA
  Object.assign(dotclear, dotclear.getData('dotclear'));
  Object.assign(dotclear.msg, dotclear.getData('dotclear_msg'));

  // set theme class
  $('body').addClass(`${dotclear.data.theme}-mode`);
  dotclear.data.darkMode = dotclear.data.theme === 'dark' ? 1 : 0;
  if (document.documentElement.getAttribute('data-theme') === '') {
    // Theme is set to automatic, keep an eye on system change
    dotclear.theme_OS = window.matchMedia('(prefers-color-scheme: dark)');
    const switchScheme = (e) => {
      const theme = e.matches ? 'dark' : 'light';
      if (theme !== dotclear.data.theme) {
        $('body').removeClass(`${dotclear.data.theme}-mode`);
        dotclear.data.theme = theme;
        $('body').addClass(`${dotclear.data.theme}-mode`);
        document.documentElement.style.setProperty('--dark-mode', dotclear.data.theme === 'dark' ? 1 : 0);
      }
    };
    try {
      dotclear.theme_OS.addEventListener('change', (e) => switchScheme(e));
    } catch (e) {
      try {
        dotclear.theme_OS.addListener((e) => switchScheme(e));
      } catch (e) {
        try {
          dotclear.theme_OS.onchange((e) => switchScheme(e));
        } catch (e) {
          console.log(e);
        }
      }
    }
  }

  // Watch data-theme attribute modification
  const observer = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
      let theme;
      if (mutation.target.getAttribute('data-theme') === '') {
        theme = window.matchMedia('(prefers-color-scheme: dark)') ? 'dark' : 'light';
      } else {
        theme = mutation.target.getAttribute('data-theme');
      }
      $('body').removeClass(`${dotclear.data.theme}-mode`);
      dotclear.data.theme = theme;
      $('body').addClass(`${dotclear.data.theme}-mode`);
      document.documentElement.style.setProperty('--dark-mode', dotclear.data.theme === 'dark' ? 1 : 0);
    }
  });
  observer.observe(document.documentElement, {
    attributeFilter: ['data-theme'],
  });

  if (dotclear.debug) {
    // debug mode: double click on header switch current theme
    const header = document.querySelector('#header') ? document.querySelector('#header') : document.querySelector('h1');
    header.addEventListener('dblclick', (_e) => {
      const elt = document.documentElement;
      let { theme } = elt.dataset;
      if (theme == null || theme === '') {
        theme = window.matchMedia('(prefers-color-scheme: dark)') ? 'dark' : 'light';
      }
      // Set new theme, the application will be cope by the mutation observer (see above)
      elt.dataset.theme = theme === 'dark' ? 'light' : 'dark';
    });
  }

  // remove class no-js from html tag; cf style/default.css for examples
  $('body').removeClass('no-js').addClass('with-js');
  $('body')
    .contents()
    .each(function () {
      if (this.nodeType == 8) {
        let { data } = this;
        data = data.replace(/ /g, '&nbsp;').replace(/\n/g, '<br />').replace(/\n/g, '<br/>');
        $(`<span class="tooltip" aria-hidden="true">${$('#footer a').prop('title')}${data}</span>`).appendTo('#footer a');
      }
    });

  // manage outgoing links
  dotclear.outgoingLinks('a');

  // Popups: dealing with Escape key fired
  $('#dotclear-admin.popup').on('keyup', (e) => {
    if (e.key == 'Escape') {
      e.preventDefault();
      window.close();
      return false;
    }
  });

  // Blog switcher
  $('#switchblog').on('change', function () {
    this.form.submit();
  });

  // Menu state
  const menu_settings = {
    legend_click: true,
    speed: 100,
  };
  $('#blog-menu h3:first').toggleWithLegend(
    $('#blog-menu ul:first'),
    $.extend(
      {
        user_pref: 'dc_blog_menu',
      },
      menu_settings,
    ),
  );
  $('#system-menu h3:first').toggleWithLegend(
    $('#system-menu ul:first'),
    $.extend(
      {
        user_pref: 'dc_system_menu',
      },
      menu_settings,
    ),
  );
  $('#plugins-menu h3:first').toggleWithLegend(
    $('#plugins-menu ul:first'),
    $.extend(
      {
        user_pref: 'dc_plugins_menu',
      },
      menu_settings,
    ),
  );
  $('#favorites-menu h3:first').toggleWithLegend(
    $('#favorites-menu ul:first'),
    $.extend(
      {
        user_pref: 'dc_favorites_menu',
        hide: false,
        reverse_user_pref: true,
      },
      menu_settings,
    ),
  );

  $('#help').helpViewer();

  // Password helpers
  dotclear.passwordHelpers();
  // Password
  $('form:has(input[type=password][name=your_pwd])').on('submit', function () {
    const e = this.elements.your_pwd;
    if (e.value == '') {
      $(e)
        .addClass('missing')
        .on('focusout', function () {
          $(this).removeClass('missing');
        });
      e.focus();
      return false;
    }
    return true;
  });

  // Cope with ellipsis'ed cells
  $('table .maximal').each(function () {
    if (this.offsetWidth < this.scrollWidth && this.title == '') {
      this.title = this.innerText;
      $(this).addClass('ellipsis');
    }
  });
  $('table .maximal.ellipsis a').each(function () {
    if (this.title == '') {
      this.title = this.innerText;
    }
  });

  // Advanced users
  if (dotclear.data.hideMoreInfo) {
    $('.more-info,.form-note:not(.warn,.warning,.info)').addClass('no-more-info');
  }

  // Ajax loader activity indicator
  if (dotclear.data.showAjaxLoader) {
    $(document).ajaxStart(() => {
      $('body').addClass('ajax-loader');
      $('div.ajax-loader').show();
    });
    $(document).ajaxStop(() => {
      $('body').removeClass('ajax-loader');
      $('div.ajax-loader').hide();
    });
  }

  // Main menu collapser
  const objMain = $('#wrapper');
  const hideMainMenu = 'hide_main_menu';

  // Sidebar separator
  $('#collapser').on('click', (e) => {
    e.preventDefault();
    if (objMain.hasClass('hide-mm')) {
      // Show sidebar
      objMain.removeClass('hide-mm');
      dotclear.dropLocalData(hideMainMenu);
      $('#main-menu input#qx').trigger('focus');
      return;
    }
    // Hide sidebar
    objMain.addClass('hide-mm');
    dotclear.storeLocalData(hideMainMenu, true);
    $('#content a.go_home').trigger('focus');
  });
  // Cope with current stored state of collapser
  if (dotclear.readLocalData(hideMainMenu) === true) {
    objMain.addClass('hide-mm');
  } else {
    objMain.removeClass('hide-mm');
  }

  // totop scroll
  $(window).on('scroll', function () {
    if ($(this).scrollTop() == 0) {
      $('#gototop').fadeOut();
    } else {
      $('#gototop').fadeIn();
    }
  });
  $('#gototop').on('click', (e) => {
    $('body,html').animate({ scrollTop: 0 }, 800);
    e.preventDefault();
  });

  // Go back (aka Cancel) button
  $('.go-back').on('click', () => {
    history.back();
  });
});
