/*global jQuery */
'use strict';

(($) => {
  $.pageTabs = function (start_tab, opts) {
    const defaults = {
      containerClass: 'part-tabs',
      partPrefix: 'part-',
      contentClass: 'multi-part',
      activeClass: 'part-tabs-active',
      idTabPrefix: 'part-tabs-',
    };

    $.pageTabs.options = $.extend({}, defaults, opts);
    let active_tab = start_tab || '';
    const hash = $.pageTabs.getLocationHash();
    const subhash = $.pageTabs.getLocationSubhash();

    if (hash !== undefined && hash) {
      window.scrollTo(0, 0);
      active_tab = hash;
    } else if (active_tab == '') {
      // open first part
      active_tab = $(`.${$.pageTabs.options.contentClass}:eq(0)`).attr('id');
    }

    createTabs();

    $('ul li', `.${$.pageTabs.options.containerClass}`).on('click', function () {
      if ($(this).hasClass($.pageTabs.options.activeClass)) {
        return;
      }

      $(this).parent().find(`li.${$.pageTabs.options.activeClass}`).removeClass($.pageTabs.options.activeClass);
      $(this).addClass($.pageTabs.options.activeClass);
      $(`.${$.pageTabs.options.contentClass}.active`).removeClass('active').hide();

      const part_to_activate = $(`#${$.pageTabs.options.partPrefix}${getHash($(this).find('a').attr('href'))}`);

      part_to_activate.addClass('active').show();
      if (!part_to_activate.hasClass('loaded')) {
        part_to_activate.trigger('onetabload');
        part_to_activate.addClass('loaded');
      }

      part_to_activate.trigger('tabload');
    });

    $(window).on('hashchange onhashchange', () => {
      $.pageTabs.clickTab($.pageTabs.getLocationHash());
    });

    $.pageTabs.clickTab(active_tab);

    if (subhash !== undefined) {
      const elt = document.getElementById(subhash);
      // Check if currently hidden, and if so try to display it
      if ($(elt).is(':hidden')) {
        const prt = $(elt).closest(':visible');
        if (prt.length) {
          const btn = prt[0].querySelector('.details-cmd');
          if (btn) {
            btn.click();
          }
        }
      }
      // Tab displayed, now scroll to the sub-part if defined in original document.location (#tab.sub-part)
      elt.scrollIntoView();
      // Give focus to the sub-part if possible
      $(`#${subhash}`)
        .addClass('focus')
        .on('focusout', function () {
          $(this).removeClass('focus');
        });
      elt.focus();
    }

    return this;
  };

  const createTabs = function createTabs() {
    let lis = [];

    $(`.${$.pageTabs.options.contentClass}`).each(function () {
      $(this).hide();
      lis.push(
        `<li id="${$.pageTabs.options.idTabPrefix}${$(this).attr('id')}">` +
          '<a href="#' +
          $(this).attr('id') +
          '">' +
          $(this).attr('title') +
          '</a></li>'
      );
      $(this)
        .attr('id', $.pageTabs.options.partPrefix + $(this).attr('id'))
        .prop('title', '');
    });

    $(`<div class="${$.pageTabs.options.containerClass}"><ul>${lis.join('')}</ul></div>`).insertBefore(
      $(`.${$.pageTabs.options.contentClass}`).get(0)
    );
  };

  const getHash = function getHash(href = '') {
    return href.replace(/.*#/, '');
  };

  $.pageTabs.clickTab = (tab) => {
    if (tab == '') {
      tab = getHash($('ul li a', `.${$.pageTabs.options.containerClass}:eq(0)`).attr('href'));
    } else if ($(`#${$.pageTabs.options.idTabPrefix}${tab}`, `.${$.pageTabs.options.containerClass}`).length == 0) {
      // try to find anchor in a .multi-part div
      if ($(`#${tab}`).length == 1) {
        const div_content = $(`#${tab}`).parents(`.${$.pageTabs.options.contentClass}`);
        tab =
          div_content.length == 1
            ? div_content.attr('id').replace($.pageTabs.options.partPrefix, '')
            : getHash($('ul li a', `.${$.pageTabs.options.containerClass}:eq(0)`).attr('href'));
      } else {
        tab = getHash($('ul li a', `.${$.pageTabs.options.containerClass}:eq(0)`).attr('href'));
      }
    }

    $('ul li a', `.${$.pageTabs.options.containerClass}`)
      .filter(function () {
        return getHash($(this).attr('href')) == tab;
      })
      .parent()
      .trigger('click');
  };

  $.pageTabs.getLocationHash = () => {
    // Return the URL hash (without subhash — #hash[.subhash])
    const h = getHash(document.location.hash).split('.');
    return h[0];
  };
  $.pageTabs.getLocationSubhash = () => {
    // Return the URL subhash if present (without hash — #hash[.subhash])
    const sh = getHash(document.location.hash).split('.');
    return sh[1];
  };
})(jQuery);
