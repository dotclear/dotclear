/*global $, jQuery, dotclear */
'use strict';

/* jQuery extensions
-------------------------------------------------------- */

/**
 * @name jQuery
 * @class
 * @typedef {jQuery} $
 * @external "jQuery"
 */

/**
 * @name fn
 * @class
 * @memberOf jQuery
 * @external "jQuery.fn"
 */

/**
 * Expands element using callback to get content.
 *
 * @param      {Object}           opts    The options
 * @function
 * @memberof    external:"jQuery"
 */
$.expandContent = (opts) => {
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
 * @deprecated  Should use dotclear.toggleWithLegend(this_element, target, options)
 *
 * @param       {jQuery}  target   The target
 * @param       {Object}  options  The options
 * @return      {jQuery}
 * @function
 * @memberof    external:"jQuery.fn"
 */
$.fn.toggleWithLegend = function (target, options) {
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
  const p = Object.assign(defaults, options);
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
        dotclear.jsonServicesPost('setSectionFold', () => {}, {
          section: p.user_pref,
          value: p.hide ^ p.reverse_user_pref ? 1 : 0,
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

/**
 * Add toggle mecanism for a details element
 *
 * @deprecated  Should use dotclear.toggleWithDetails(this_element, options)
 *
 * @param       {Object}  options       The options
 * @return      {jQuery}
 * @function
 * @memberof    external:"jQuery.fn"
 */
$.fn.toggleWithDetails = function (options) {
  const defaults = {
    unfolded_sections: dotclear.unfolded_sections,
    hide: true, // Is section unfolded?
    fn: false, // A function called on first display,
    user_pref: false,
    reverse_user_pref: false, // Reverse user pref behavior
  };
  const p = Object.assign(defaults, options);
  if (p.user_pref && p.unfolded_sections !== undefined && p.user_pref in p.unfolded_sections) {
    p.hide = p.reverse_user_pref;
  }
  const toggle = () => {
    if (!p.hide && p.fn) {
      p.fn.apply(target);
      p.fn = false;
    }
    p.hide = !p.hide;
    if (p.hide && this.attr('open')) {
      this.removeAttr('open');
    } else if (!p.hide && !this.attr('open')) {
      this.attr('open', 'open');
    }
  };
  return this.each(() => {
    $('summary', this).on('click', (e) => {
      // Catch click only on summary child of details HTML element
      if (p.user_pref) {
        dotclear.jsonServicesPost('setSectionFold', () => {}, {
          section: p.user_pref,
          value: p.hide ^ p.reverse_user_pref ? 1 : 0,
        });
      }
      toggle();
      e.preventDefault();
      return false;
    });
    toggle();
  });
};
