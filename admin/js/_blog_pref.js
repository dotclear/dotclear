/*global dotclear, jsToolBar */
'use strict';

// Get blog preferences data
const data = dotclear.getData('blog_pref');
Object.assign(dotclear.msg, data.msg);
dotclear.blog_pref = data.url;

dotclear.ready(() => {
  // DOM ready and content loaded

  // Blog URL scan method helper
  const url = document.getElementById('blog_url');
  if (url && !url.hidden) {
    const scan = document.getElementById('url_scan');

    // Check URL scan method
    const checkQueryString = (url, scan) => {
      let msg = '';
      if (/.*[^/]$/.exec(url.value) && scan.value === 'path_info') {
        msg = dotclear.msg.warning_path_info;
      } else if (/.*[^?]$/.exec(url.value) && scan.value === 'query_string') {
        msg = dotclear.msg.warning_query_string;
      }
      // Warning if necessary
      const warning = document.getElementById('urlwarning');
      if (warning) warning.remove();
      if (msg !== '') {
        url.parentNode.parentNode.after(dotclear.htmlToNode(`<p id="urlwarning" class="warning">${msg}</p>`));
      }
    };

    // 1st checking
    checkQueryString(url, scan);

    // Check on leaving URL field
    url?.addEventListener('focusout', () => {
      checkQueryString(url, scan);
    });

    // Check on changing scan method
    scan?.addEventListener('change', () => {
      checkQueryString(url, scan);
    });
  }

  // Date and time format helpers
  for (const type of ['date', 'time']) {
    const select = document.getElementById(`${type}_format_select`);
    if (select) {
      const current = document.getElementById(`${type}_format`);
      const help = document.getElementById(`${type}_format_help`);
      if (current) {
        select.addEventListener('change', (event) => {
          if (event.currentTarget.value === '') return;
          current.value = event.currentTarget.value;
          if (help) {
            help.innerText = dotclear.msg?.example_prefix + event.currentTarget.selectedOptions[0]?.label;
          }
        });
      }
    }
  }

  // Static home URL selector helper
  const staticUrlSelector = document.getElementById('static_home_url_selector');
  staticUrlSelector?.addEventListener('click', (e) => {
    window.open(
      dotclear.blog_pref.popup_posts,
      'dc_popup',
      'alwaysRaised=yes,dependent=yes,toolbar=yes,height=500,width=760,menubar=no,resizable=yes,scrollbars=yes,status=no',
    );
    e.preventDefault();
    return false;
  });

  // HTML text editor for blog description
  if (typeof jsToolBar === 'function') {
    const desc = document.getElementById('blog_desc');
    if (desc) {
      const tbWidgetText = new jsToolBar(desc);
      tbWidgetText.context = 'blog_desc';
      tbWidgetText.draw('xhtml');
    }
  }

  // Cope with standard, advanced and plugins prefs sections (add/restore collapse button)
  for (const part of [
    { id: 'standard', pref: 'std' },
    { id: 'advanced', pref: 'adv' },
    { id: 'plugins', pref: 'plg' },
  ]) {
    const title = document.querySelector(`#${part.id}-pref h3`);
    if (title) {
      const siblings = title.parentNode.querySelectorAll(':not(h3)');
      dotclear.toggleWithLegend(title, siblings, {
        legend_click: true,
        user_pref: `dcx_blog_pref_${part.pref}`,
      });
    }
  }
});
