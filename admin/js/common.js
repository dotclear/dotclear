/*global $, jQuery, getData, isObject, mergeDeep */
/*exported chainHandler */
'use strict';

/* Get PreInit JSON data */
const dotclear_init = getData('dotclear_init');

/* Set some CSS variables here
-------------------------------------------------------- */
// set base font-size of body (62.5% default, usually : 50% to 75%)
if (typeof dotclear_init.htmlFontSize !== 'undefined') {
  document.documentElement.style.setProperty('--html-font-size', dotclear_init.htmlFontSize);
}
// set theme mode (dark/light/…)
dotclear_init.theme = 'light';
if (document.documentElement.getAttribute('data-theme') !== '') {
  dotclear_init.theme = document.documentElement.getAttribute('data-theme');
} else {
  if (window.matchMedia) {
    if (window.matchMedia("(prefers-color-scheme: dark)").matches) {
      dotclear_init.theme = 'dark';
    }
  }
}
dotclear_init.lowdata = false;
if (window.matchMedia) {
  if (window.matchMedia("(prefers-reduced-data: reduce)").matches) {
    dotclear_init.lowdata = true;
  }
}
document.documentElement.style.setProperty('--dark-mode', (dotclear_init.theme === 'dark' ? 1 : 0));
/* ChainHandler, py Peter van der Beken
-------------------------------------------------------- */
function chainHandler(obj, handlerName, handler) {
  obj[handlerName] = (function(existingFunction) {
    return function() {
      handler.apply(this, arguments);
      if (existingFunction) existingFunction.apply(this, arguments);
    };
  })(handlerName in obj ? obj[handlerName] : null);
}
/* jQuery extensions
-------------------------------------------------------- */
jQuery.fn.check = function() {
  return this.each(function() {
    if (this.checked != undefined) {
      this.checked = true;
    }
  });
};
jQuery.fn.unCheck = function() {
  return this.each(function() {
    if (this.checked != undefined) {
      this.checked = false;
    }
  });
};
jQuery.fn.setChecked = function(status) {
  return this.each(function() {
    if (this.checked != undefined) {
      this.checked = status;
    }
  });
};
jQuery.fn.toggleCheck = function() {
  return this.each(function() {
    if (this.checked != undefined) {
      this.checked = !this.checked;
    }
  });
};
jQuery.fn.enableShiftClick = function() {
  this.on('click', function(event) {
    if (event.shiftKey) {
      if (dotclear.lastclicked != '') {
        let range;
        const trparent = $(this).parents('tr');
        const id = `#${dotclear.lastclicked}`;
        if (trparent.nextAll(id).length != 0) {
          range = trparent.nextUntil(id);
        } else {
          range = trparent.prevUntil(id);
        }
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
jQuery.fn.toggleWithLegend = function(target, s) {
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
    reverse_user_pref: false // Reverse cookie behavior
  };
  const p = jQuery.extend(defaults, s);
  if (!target) {
    return this;
  }
  const set_cookie = p.hide ^ p.reverse_cookie;
  if (p.cookie && jQuery.cookie(p.cookie)) {
    p.hide = p.reverse_cookie;
  }
  let set_user_pref = p.hide ^ p.reverse_user_pref;
  if (p.user_pref && p.unfolded_sections !== undefined && (p.user_pref in p.unfolded_sections)) {
    p.hide = p.reverse_user_pref;
  }
  const toggle = function(i) {
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
    if (p.cookie && set_cookie) {
      if (p.hide ^ p.reverse_cookie) {
        jQuery.cookie(p.cookie, '', {
          expires: -1
        });
      } else {
        jQuery.cookie(p.cookie, 1, {
          expires: 30
        });
      }
    }
    p.hide = !p.hide;
  };
  return this.each(function() {
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
    $(ctarget).on('click', function(e) {
      if (p.user_pref && set_user_pref) {
        jQuery.post('services.php', {
          f: 'setSectionFold',
          section: p.user_pref,
          value: (p.hide ^ p.reverse_user_pref ? 1 : 0),
          xd_check: dotclear.nonce
        }, function() {});
        jQuery.cookie(p.user_pref, '', {
          expires: -1
        });
      }
      toggle(b);
      e.preventDefault();
      return false;
    });
    toggle($(b).get(0));
    $(this).prepend(b);
  });
};

(function($) {
  $.expandContent = function(opts) {
    if (opts == undefined || opts.callback == undefined || typeof opts.callback !== 'function') {
      return;
    }
    if (opts.line != undefined) {
      multipleExpander(opts.line, opts.lines, opts.callback);
    }
    opts.lines.each(function() {
      singleExpander(this, opts.callback);
    });
  };
  const singleExpander = function(line, callback) {
    $(`<button type="button" class="details-cmd" aria-expanded="false" aria-label="${dotclear.img_plus_alt}">${dotclear.img_plus_txt}</button>`).on(
      'click',
      function(e) {
        if (toggleArrow(this) !== '') {
          callback(line, '', e);
        }
        e.preventDefault();
      }).prependTo($(line).children().get(0)); // first td
  };
  const multipleExpander = function(line, lines, callback) {
    $(`<button type="button" class="details-cmd" aria-expanded="false" aria-label="${dotclear.img_plus_alt}">${dotclear.img_plus_txt}</button>`).on(
      'click',
      function(e) {
        var action = toggleArrow(this);
        lines.each(function() {
          if (toggleArrow(this.firstChild.firstChild, action) !== '') {
            callback(this, action, e);
          }
        });
        e.preventDefault();
      }).prependTo($(line).children().get(0)); // first td
  };
  const toggleArrow = function(button, action) {
    action = action || '';
    if (action == '') {
      if (button.getAttribute('aria-label') == dotclear.img_plus_alt) {
        action = 'open';
      } else {
        action = 'close';
      }
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
      action = '';
    }
    return action;
  };
})(jQuery);
jQuery.fn.helpViewer = function() {
  if (this.length < 1) {
    return this;
  }
  let select = $();
  const p = {
    img_on_txt: dotclear.img_plus_txt,
    img_on_alt: dotclear.img_plus_alt,
    img_off_txt: dotclear.img_minus_txt,
    img_off_alt: dotclear.img_minus_alt
  };
  const This = this;
  const toggle = function() {
    $('#content').toggleClass('with-help');
    if (document.all) {
      if ($('#content').hasClass('with-help')) {
        select = $('#content select:visible').hide();
      } else {
        select.show();
      }
    }
    $('p#help-button span a').text($('#content').hasClass('with-help') ? dotclear.msg.help_hide : dotclear.msg.help);
    sizeBox();
    return false;
  };
  const sizeBox = function() {
    This.css('height', 'auto');
    if ($('#wrapper').height() > This.height()) {
      This.css('height', $('#wrapper').height() + 'px');
    }
  };
  const textToggler = function(o) {
    const b = $(`<button type="button" class="details-cmd" aria-label="${p.img_on_alt}">${p.img_on_txt}</button>`);
    o.css('cursor', 'pointer');
    let hide = true;
    o.prepend(' ').prepend(b);
    o.on('click', function() {
      $(this).nextAll().each(function() {
        if ($(this).is('h4')) {
          return false;
        }
        $(this).toggle();
        sizeBox();
        return true;
      });
      hide = !hide;
      const img = $(this).find('button.details-cmd');
      if (!hide) {
        img.html(p.img_off_txt);
        img.attr('value', p.img_off_txt);
        img.attr('aria-label', p.img_off_alt);
      } else {
        img.html(p.img_on_txt);
        img.attr('value', p.img_on_txt);
        img.attr('aria-label', p.img_on_alt);
      }
    });
  };
  this.addClass('help-box');
  this.find('>hr').remove();
  this.find('h4').each(function() {
    textToggler($(this));
  });
  this.find('h4:first').nextAll('*:not(h4)').hide();
  sizeBox();
  const img = $(`<p id="help-button"><span><a href="">${dotclear.msg.help}</a></span></p>`);
  img.on('click', function(e) {
    e.preventDefault();
    return toggle();
  });
  $('#content').append(img);
  // listen for scroll
  const peInPage = $('#help-button').offset().top;
  $('#help-button').addClass('floatable');
  const peInFloat = $('#help-button').offset().top - $(window).scrollTop();
  $('#help-button').removeClass('floatable');
  $(window).on('scroll', function() {
    if ($(window).scrollTop() >= peInPage - peInFloat) {
      $('#help-button').addClass('floatable');
    } else {
      $('#help-button').removeClass('floatable');
    }
  });
  return this;
};

/* Dotclear common object
-------------------------------------------------------- */
const dotclear = {
  msg: {},

  enterKeyInForm: function(frm_id, ok_id, cancel_id) {
    $(frm_id + ':not(' + cancel_id + ')').on('keyup', function(e) {
      if ((e.key == 'Enter') && ($(ok_id).prop('disabled') !== true)) {
        e.preventDefault();
        e.stopPropagation();
        $(ok_id).trigger('click');
      }
    });
  },

  condSubmit: function(chkboxes, target) {
    const checkboxes = $(chkboxes),
      submitButt = $(target);
    if (checkboxes === undefined || submitButt === undefined) {
      return;
    }
    // Set initial state
    submitButt.attr('disabled', !checkboxes.is(':checked'));
    if (!checkboxes.is(':checked')) {
      submitButt.addClass('disabled');
    } else {
      submitButt.removeClass('disabled');
    }
    checkboxes.on('click', function() {
      // Update target state
      submitButt.attr('disabled', !checkboxes.is(':checked'));
      if (!checkboxes.is(':checked')) {
        submitButt.addClass('disabled');
      } else {
        submitButt.removeClass('disabled');
      }
    });
  },

  hideLockable: function() {
    $('div.lockable').each(function() {
      const current_lockable_div = this;
      $(this).find('p.form-note').hide();
      $(this).find('input').each(function() {
        this.disabled = true;
        $(this).width(`${$(this).width() - 14}px`);
        const imgE = document.createElement('img');
        imgE.src = 'images/locker.png';
        imgE.style.position = 'absolute';
        imgE.style.top = '1.8em';
        imgE.style.left = `${$(this).width() + 14}px`;
        imgE.alt = dotclear.msg.click_to_unlock;
        $(imgE).css('cursor', 'pointer');
        $(imgE).on('click', function() {
          $(this).hide();
          $(this).prev('input').each(function() {
            this.disabled = false;
            $(this).width(($(this).width() + 14) + 'px');
          });
          $(current_lockable_div).find('p.form-note').show();
        });
        $(this).parent().css('position', 'relative');
        $(this).after(imgE);
      });
    });
  },

  checkboxesHelpers: function(e, target, c, s) {
    $(e).append(document.createTextNode(dotclear.msg.to_select));
    $(e).append(document.createTextNode(' '));
    $(`<button type="button" class="checkbox-helper select-all">${dotclear.msg.select_all}</button>`).on('click', function() {
      if (target !== undefined) {
        target.check();
      } else {
        $(e).parents('form').find('input[type="checkbox"]').check();
      }
      if (c !== undefined && s !== undefined) {
        dotclear.condSubmit(c, s);
      }
      return false;
    }).appendTo($(e));
    $(e).append(document.createTextNode(' '));
    $(`<button type="button" class="checkbox-helper select-none">${dotclear.msg.no_selection}</button>`).on('click', function() {
      if (target !== undefined) {
        target.unCheck();
      } else {
        $(e).parents('form').find('input[type="checkbox"]').unCheck();
      }
      if (c !== undefined && s !== undefined) {
        dotclear.condSubmit(c, s);
      }
      return false;
    }).appendTo($(e));
    $(e).append(document.createTextNode(' '));
    $(`<button type="button" class="checkbox-helper select-reverse">${dotclear.msg.invert_sel}</button>`).on('click', function() {
      if (target !== undefined) {
        target.toggleCheck();
      } else {
        $(e).parents('form').find('input[type="checkbox"]').toggleCheck();
      }
      if (c !== undefined && s !== undefined) {
        dotclear.condSubmit(c, s);
      }
      return false;
    }).appendTo($(e));
  },

  postsActionsHelper: function() {
    $('#form-entries').on('submit', function() {
      const action = $(this).find('select[name="action"]').val();
      if (action === undefined) {
        return;
      }
      let checked = false;
      $(this).find('input[name="entries[]"]').each(function() {
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
  },

  commentsActionsHelper: function() {
    $('#form-comments').on('submit', function() {
      const action = $(this).find('select[name="action"]').val();
      let checked = false;
      $(this).find('input[name="comments[]"]').each(function() {
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
  },

  outgoingLinks: function(target) {
    $(target).filter(function() {
      return ((this.hostname && this.hostname != location.hostname && !$(this).hasClass('modal') && !$(this).hasClass('modal-image')) || $(this).hasClass('outgoing'));
    }).each(function() {
      $(this).prop('title', `${$(this).prop('title')} (${dotclear.msg.new_window})`);
      if (!$(this).hasClass('outgoing')) {
        $(this).append('&nbsp;<img class="outgoing-js" src="images/outgoing-link.svg" alt=""/>');
      }
    }).on('click', function(e) {
      e.preventDefault();
      window.open($(this).attr('href'));
    });
  },

  /**
   * Add headers on each cells (responsive tables)
   *
   * @param      DOM elt   table         The table
   * @param      string    selector      The selector
   * @param      number    [offset=0]    The offset = number of firsts columns to ignore
   * @param      boolean   [thead=false] True if titles are in thead rather than in the first tr of the body
   */
  responsiveCellHeaders: function(table, selector, offset = 0, thead = false) {
    try {
      let THarray = [];
      const ths = table.getElementsByTagName("th");
      for (let i = 0; i < ths.length; i++) {
        for (let colspan = ths[i].colSpan; colspan > 0; colspan--) {
          THarray.push(ths[i].innerText.replace('►', ''));
        }
      }
      let styleElm = document.createElement("style");
      let styleSheet;
      document.head.appendChild(styleElm);
      styleSheet = styleElm.sheet;
      for (let i = offset; i < THarray.length; i++) {
        styleSheet.insertRule(
          selector + " td:nth-child(" + (i + 1) + ')::before {content:"' + THarray[i] + ' ";}',
          styleSheet.cssRules.length
        );
      }
      table.className += (table.className !== '' ? ' ' : '') + 'rch' + (thead ? ' rch-thead' : '');
    } catch (e) {
      console.log("responsiveCellHeaders(): " + e);
    }
  },

  badge: function($elt, options = null) {
    // Cope with selector given as string or DOM element rather than a jQuery object
    if (typeof $elt === 'string' || $elt instanceof Element) {
      $elt = $($elt);
    }

    // Return if elt does not exist
    if (!$elt.length) return;

    // Cope with options
    const opt = $.extend({
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
      classes: ''
    }, options);

    // Set some constants
    const classid = `span.badge.badge-${opt.id}`; // Pseudo unique class

    // Set badgeable class to elt parent's (if sibling) or elt itself, if it is necessary
    const $parent = (opt.sibling ? $elt.parent() : $elt);
    if (!opt.inline && !opt.remove && !$parent.hasClass('badgeable')) {
      $parent.addClass('badgeable');
    }

    // Remove existing badge if exists
    const $badge = (opt.sibling ? $parent.children(classid) : $elt.children(classid));
    if ($badge.length) {
      $badge.remove();
    }

    // Add the new badge if any
    if (!opt.remove && opt.value !== null) {
      // Compose badge classes
      let classes = ['badge'];
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
  },

  passwordHelpers: function() {

    const togglePasswordHelper = function(e) {
      e.preventDefault();
      const button = e.currentTarget;
      const isPasswordShown = button.classList.contains('pw-hide');

      let inputType = isPasswordShown ? 'password' : 'text';
      let buttonContent = isPasswordShown ? dotclear.msg.show_password : dotclear.msg.hide_password;

      button.classList.toggle('pw-hide', !isPasswordShown);
      button.classList.toggle('pw-show', isPasswordShown);

      button.previousElementSibling.setAttribute('type', inputType);
      button.setAttribute('title', buttonContent);
      button.querySelector('span').textContent = buttonContent;
    };

    // Compose button
    const buttonTemplate = new DOMParser().parseFromString(
      `<button type="button" class="pw-show" title="${dotclear.msg.show_password}"><span class="sr-only">${dotclear.msg.show_password}</span></button>`,
      'text/html'
    ).body.firstChild;

    const passwordFields = document.querySelectorAll('input[type=password]');

    for (const passwordField of passwordFields) {
      const button = buttonTemplate.cloneNode(true);
      passwordField.after(button);
      passwordField.classList.add('pwd_helper');
      button.addEventListener('click', togglePasswordHelper);
    }
  }
};

/* On document ready
-------------------------------------------------------- */
$(function() {
  // Store preinit DATA in dotclear object
  dotclear.data = dotclear_init;
  // Debug mode
  dotclear.debug = dotclear.data.debug || false;
  // Get other DATA
  Object.assign(dotclear, getData('dotclear'));
  Object.assign(dotclear.msg, getData('dotclear_msg'));
  // Function's aliases (from prepend.js)
  if (typeof getData === 'function') {
    dotclear.getData = dotclear.getData || getData;
  }
  if (typeof isObject === 'function') {
    dotclear.isObject = dotclear.isObject || isObject;
  }
  if (typeof mergeDeep === 'function') {
    dotclear.mergeDeep = dotclear.mergeDeep || mergeDeep;
  }
  // set theme class
  $('body').addClass(`${dotclear.data.theme}-mode`);
  dotclear.data.darkMode = dotclear.data.theme === 'dark' ? 1 : 0;
  if (document.documentElement.getAttribute('data-theme') === '') {
    // Theme is set to automatic, keep an eye on system change
    dotclear.theme_OS = window.matchMedia('(prefers-color-scheme: dark)');
    let switchScheme = function(e) {
        let theme = e.matches ? 'dark' : 'light';
        if (theme !== dotclear.data.theme) {
          $('body').removeClass(`${dotclear.data.theme}-mode`);
          dotclear.data.theme = theme;
          $('body').addClass(`${dotclear.data.theme}-mode`);
          document.documentElement.style.setProperty('--dark-mode', (dotclear.data.theme === 'dark' ? 1 : 0));
        }
    };
    try {
      dotclear.theme_OS.addEventListener('change', (e) => switchScheme(e));
    } catch(e) {
      try {
        dotclear.theme_OS.addListener((e) => switchScheme(e));
      } catch(e) {
        try {
          dotclear.theme_OS.onchange((e) => switchScheme(e));
        } catch(e) {
          console.log(e);
        }
      }
    }
  }
  // Watch data-theme attribute modification
  const observer = new MutationObserver((mutations) => {
    for (let mutation of mutations) {
      let theme = 'light';
      if (mutation.target.getAttribute('data-theme') !== '') {
        theme = mutation.target.getAttribute('data-theme');
      } else {
        theme = window.matchMedia('(prefers-color-scheme: dark)') ? 'dark' : 'light';
      }
      $('body').removeClass(`${dotclear.data.theme}-mode`);
      dotclear.data.theme = theme;
      $('body').addClass(`${dotclear.data.theme}-mode`);
      document.documentElement.style.setProperty('--dark-mode', (dotclear.data.theme === 'dark' ? 1 : 0));
    }
  });
  observer.observe(document.documentElement, {
    attributeFilter: ['data-theme']
  });
  // remove class no-js from html tag; cf style/default.css for examples
  $('body').removeClass('no-js').addClass('with-js');
  $('body').contents().each(function() {
    if (this.nodeType == 8) {
      let data = this.data;
      data = data.replace(/ /g, '&nbsp;').replace(/\n/g, '<br/>');
      $(`<span class="tooltip" aria-hidden="true">${$('#footer a').prop('title')}${data}</span>`).appendTo('#footer a');
    }
  });
  // Footer effect
  if (!dotclear.lowdata) {
    const footer_target = document.querySelector('#footer a');
    if (footer_target) {
      const footer_audio = document.querySelector('#thanks');
      if (footer_audio) {
        footer_target.addEventListener('mouseenter', () => {
          footer_audio.load();
          footer_audio.play();
        });
        footer_target.addEventListener('mouseleave', () => {
          footer_audio.pause();
        });
      }
    }
  }
  // manage outgoing links
  dotclear.outgoingLinks('a');
  // Popups: dealing with Escape key fired
  $('#dotclear-admin.popup').on('keyup', function(e) {
    if (e.key == 'Escape') {
      e.preventDefault();
      window.close();
      return false;
    }
  });
  // Blog switcher
  $('#switchblog').on('change', function() {
    this.form.submit();
  });
  const menu_settings = {
    img_on_src: dotclear.img_menu_off,
    img_off_src: dotclear.img_menu_on,
    legend_click: true,
    speed: 100
  };
  $('#blog-menu h3:first').toggleWithLegend($('#blog-menu ul:first'), $.extend({
    user_pref: 'dc_blog_menu'
  }, menu_settings));
  $('#system-menu h3:first').toggleWithLegend($('#system-menu ul:first'), $.extend({
    user_pref: 'dc_system_menu'
  }, menu_settings));
  $('#plugins-menu h3:first').toggleWithLegend($('#plugins-menu ul:first'), $.extend({
    user_pref: 'dc_plugins_menu'
  }, menu_settings));
  $('#favorites-menu h3:first').toggleWithLegend($('#favorites-menu ul:first'), $.extend({
    user_pref: 'dc_favorites_menu',
    hide: false,
    reverse_user_pref: true
  }, menu_settings));
  $('#help').helpViewer();
  // Notices
  $('p.success,p.warning,p.error,div.error').each(function() {
    $(this).addClass('close-notice-parent');
    $(this).append(`<button class="close-notice" type="button"><img src="images/close.png" alt="${dotclear.msg.close_notice}" /></button>`);
  });
  $('button.close-notice').on('click', function(e) {
    e.preventDefault();
    $(this).parent().hide();
  });
  // Password helpers
  dotclear.passwordHelpers();
  // Password
  $('form:has(input[type=password][name=your_pwd])').on('submit', function() {
    const e = this.elements.your_pwd;
    if (e.value == '') {
      $(e).addClass('missing').on('focusout', function() {
        $(this).removeClass('missing');
      });
      e.focus();
      return false;
    }
    return true;
  });
  // Cope with ellipsis'ed cells
  $('table .maximal').each(function() {
    if (this.offsetWidth < this.scrollWidth) {
      if (this.title == '') {
        this.title = this.innerText;
        $(this).addClass('ellipsis');
      }
    }
  });
  $('table .maximal.ellipsis a').each(function() {
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
    $(document).ajaxStart(function() {
      $('body').addClass('ajax-loader');
      $('div.ajax-loader').show();
    });
    $(document).ajaxStop(function() {
      $('body').removeClass('ajax-loader');
      $('div.ajax-loader').hide();
    });
  }
  // Main menu collapser
  const objMain = $('#wrapper');

  function showSidebar() {
    // Show sidebar
    objMain.removeClass('hide-mm');
    $.cookie('sidebar-pref', null, {
      expires: 30
    });
  }

  function hideSidebar() {
    // Hide sidebar
    objMain.addClass('hide-mm');
    $.cookie('sidebar-pref', 'hide-mm', {
      expires: 30
    });
  }
  // Sidebar separator
  $('#collapser').on('click', function(e) {
    e.preventDefault();
    if (objMain.hasClass('hide-mm')) {
      showSidebar();
      $('#main-menu input#qx').trigger('focus');
    } else {
      hideSidebar();
      $('#content a.go_home').trigger('focus');
    }
  });
  if ($.cookie('sidebar-pref') == 'hide-mm') {
    objMain.addClass('hide-mm');
  } else {
    objMain.removeClass('hide-mm');
  }
  // totop scroll
  $(window).on('scroll', function() {
    if ($(this).scrollTop() != 0) {
      $('#gototop').fadeIn();
    } else {
      $('#gototop').fadeOut();
    }
  });
  $('#gototop').on('click', function(e) {
    $('body,html').animate({
      scrollTop: 0
    }, 800);
    e.preventDefault();
  });
  // Go back (aka Cancel) button
  $('.go-back').on('click', () => {
    history.back();
  });
});
