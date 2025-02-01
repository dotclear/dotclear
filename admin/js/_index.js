/*global jQuery, dotclear, jsToolBar */
'use strict';

dotclear.dbCommentsCount = (icon) => {
  dotclear.jsonServicesGet('getCommentsCount', (data) => {
    if (data.ret === dotclear.dbCommentsCount_Counter) {
      return;
    }
    // First pass or counter changed
    const nbLabel = icon.querySelector('span.db-icon-title');
    if (nbLabel) {
      nbLabel.textContent = data.ret;
    }
    // Store current counter
    dotclear.dbCommentsCount_Counter = data.ret;
  });
};
dotclear.dbPostsCount = (icon) => {
  dotclear.jsonServicesGet('getPostsCount', (data) => {
    if (data.ret === dotclear.dbPostsCount_Counter) {
      return;
    }
    // First pass or counter changed
    const nbLabel = icon.querySelector('span.db-icon-title');
    if (nbLabel) {
      nbLabel.textContent = data.ret;
    }
    // Store current counter
    dotclear.dbPostsCount_Counter = data.ret;
  });
};
dotclear.dbStoreUpdate = (store, icon) => {
  dotclear.jsonServicesPost(
    'checkStoreUpdate',
    (data) => {
      if (!data.check) {
        return;
      }
      // Something has to be displayed
      // update link to details
      icon.setAttribute('href', `${icon.getAttribute('href')}#update`);
      // update icon
      for (const img of icon.querySelectorAll('img')) {
        img.setAttribute('src', img.getAttribute('src').replace(/([^/]+)(\..*)$/g, '$1-update$2'));
      }
      // add icon text says there is an update
      const label = icon.querySelector('.db-icon-title');
      if (label) {
        label.innerHTML = `${label.innerHTML}<br>${data.ret}`;
      }
      // Badge (info) on dashboard icon
      dotclear.badge(icon.parentNode, {
        id: `mu-${store}`,
        value: data.nb,
        icon: true,
        type: 'info',
      });
    },
    { store },
  );
};

dotclear.ready(() => {
  // DOM ready and content loaded

  const formQuickEntry = document.getElementById('quick-entry');
  if (formQuickEntry) {
    Object.assign(dotclear, dotclear.getData('dotclear_quickentry'));

    function quickPost(status) {
      if (typeof jsToolBar === 'function' && dotclear.contentTb.getMode() === 'wysiwyg') {
        dotclear.contentTb.syncContents('iframe');
      }

      formQuickEntry.querySelector('p.info')?.remove();
      formQuickEntry.querySelector('p.error')?.remove();

      dotclear.jsonServicesPost(
        'quickPost',
        (data) => {
          let msg = `<p class="info">${dotclear.msg.entry_created} - <a href="post.php?id=${data.id}">${dotclear.msg.edit_entry}</a>`;
          if (data.status === dotclear.post_published) {
            msg += ` - <a href="${data.url}">${dotclear.msg.view_entry}</a>`;
          }
          msg += '</p>';
          formQuickEntry.append(dotclear.htmlToNode(msg));
          // Reset form
          formQuickEntry.reset();
          document.getElementById('post_content').dispatchEvent(new Event('change', { bubbles: true }));
          if (typeof jsToolBar === 'function' && dotclear.contentTb.getMode() === 'wysiwyg') {
            dotclear.contentTb.syncContents('textarea');
          }
        },
        {
          post_title: formQuickEntry.querySelector('#post_title').value,
          post_content: formQuickEntry.querySelector('#post_content').value,
          cat_id: formQuickEntry.querySelector('#cat_id').value,
          post_status: status,
          post_format: formQuickEntry.querySelector('#post_format').value,
          post_lang: formQuickEntry.querySelector('#post_lang').value,
          new_cat_title: formQuickEntry.querySelector('#new_cat_title').value,
          new_cat_parent: formQuickEntry.querySelector('#new_cat_parent').value,
        },
        (error) => {
          formQuickEntry.append(dotclear.htmlToNode(`<p class="error"><strong>${dotclear.msg.error}</strong> ${error}</p>`));
        },
      );
    }

    if (typeof jsToolBar === 'function') {
      dotclear.contentTb = new jsToolBar(formQuickEntry.querySelector('#post_content'));
      dotclear.contentTb.switchMode(formQuickEntry.querySelector('#post_format').value);
    }

    // Form submission (save)
    formQuickEntry.querySelector('input[name=save]')?.addEventListener('click', (event) => {
      quickPost(dotclear.post_pending);
      event.preventDefault();
      return false;
    });

    const savePublish = formQuickEntry.querySelector('input[name=save-publish]');
    if (savePublish) {
      const btn = dotclear.htmlToNode(`<input type="submit" value="${savePublish.value}" name="save-and-publish">`);
      savePublish.remove();
      formQuickEntry.querySelector('input[name=save]').after(btn);
      btn.addEventListener('click', (event) => {
        quickPost(dotclear.post_published);
        event.preventDefault();
        return false;
      });
    }

    // allow to hide quick entry div, and remember choice
    const title = document.querySelector('#quick h3');
    if (title) {
      const siblings = title.parentNode.querySelectorAll(':not(h3)');
      dotclear.toggleWithLegend(title, siblings, {
        legend_click: true,
        user_pref: 'dcx_quick_entry',
      });
    }
  }

  // outgoing links for documentation
  dotclear.outgoingLinks('#doc-and-support a');

  // check if core update available
  dotclear.jsonServicesGet('checkCoreUpdate', (data) => {
    if (data.check) {
      // Something has to be displayed
      document.querySelector('#content h2').after(dotclear.htmlToNode(data.ret));
      // manage outgoing links
      dotclear.outgoingLinks('#ajax-update a');
    }
  });

  // check if store update available, if db has icon
  const iconPlugins = document.querySelector('#dashboard-main #icons p #icon-process-plugins-fav');
  if (iconPlugins) {
    dotclear.dbStoreUpdate('plugins', iconPlugins);
  }
  const iconThemes = document.querySelector('#dashboard-main #icons p #icon-process-blog_theme-fav');
  if (iconThemes) {
    dotclear.dbStoreUpdate('themes', iconThemes);
  }

  // check if some news are available
  dotclear.jsonServicesGet('checkNewsUpdate', (data) => {
    if (!data.check) {
      return;
    }
    // Something has to be displayed
    if (!document.getElementById('dashboard-boxes')) {
      // Create the #dashboard-boxes container
      document.getElementById('dashboard-main').append(dotclear.htmlToNode('<div id="dashboard-boxes"></div>'));
    }
    if (!document.querySelector('#dashboard-boxes div.db-items')) {
      // Create the #dashboard-boxes div.db-items container
      document.getElementById('dashboard-boxes').prepend(dotclear.htmlToNode('<div class="db-items"></div>'));
    }
    document.querySelector('#dashboard-boxes div.db-items').prepend(dotclear.htmlToNode(data.ret));
    // manage outgoing links
    dotclear.outgoingLinks('#ajax-news a');
  });

  // run counters' update on some dashboard icons
  // Comments (including everything)
  const iconComments = document.querySelector('#dashboard-main #icons p #icon-process-comments-fav');
  if (iconComments) {
    // Icon exists on dashboard
    // First pass
    dotclear.dbCommentsCount(iconComments);
    // Then fired every 60 seconds (1 minute)
    dotclear.dbCommentsCount_Timer = setInterval(dotclear.dbCommentsCount, 60 * 1000, iconComments);
  }
  // Posts
  const iconPosts = document.querySelector('#dashboard-main #icons p #icon-process-posts-fav');
  if (iconPosts) {
    // Icon exists on dashboard
    // First pass
    dotclear.dbPostsCount(iconPosts);
    // Then fired every 600 seconds (10 minutes)
    dotclear.dbPostsCount_Timer = setInterval(dotclear.dbPostsCount, 600 * 1000, iconPosts);
  }

  if (dotclear.data.noDragDrop) {
    return;
  }

  // Dashboard boxes and their children are sortable
  Object.assign(dotclear, dotclear.getData('dotclear_dragndrop'));
  const set_positions = (sel, id) => {
    const list = jQuery(sel).sortable('toArray').join();
    // Save positions (via services) for id
    dotclear.jsonServicesPost('setDashboardPositions', () => {}, { id, list });
  };
  const init_positions = (sel, id) => {
    jQuery(sel).sortable({
      cursor: 'move',
      opacity: 0.5,
      delay: 200,
      distance: 10,
      tolerance: 'pointer',
      update() {
        set_positions(sel, id);
      },
      start() {
        document.getElementById(sel)?.classList.add('sortable-area');
      },
      stop() {
        document.getElementById(sel)?.classList.remove('sortable-area');
      },
    });
  };
  const reset_positions = (sel) => {
    jQuery(sel).sortable('destroy');
  };
  // List of sortable areas
  const areas = [
    ['#dashboard-main', 'main_order'],
    ['#dashboard-boxes', 'boxes_order'],
    ['#db-items', 'boxes_items_order'],
    ['#db-contents', 'boxes_contents_order'],
  ];
  // Set or reset sortable depending on #dragndrop checbkox value
  const dragndrop = document.getElementById('dragndrop');
  const dragndropLabel = document.getElementById('dragndrop-label');
  dragndrop?.addEventListener('click', (event) => {
    if (event.target.checked) {
      // Activate sorting feature
      for (const element of areas) init_positions(element[0], element[1]);
      dragndrop.setAttribute('title', dotclear.dragndrop_on);
      dragndropLabel.textContent = dotclear.dragndrop_on;
      return;
    }
    // Deactivate sorting feature
    for (const element of areas) reset_positions(element[0]);
    dragndrop.setAttribute('title', dotclear.dragndrop_off);
    dragndropLabel.textContent = dotclear.dragndrop_off;
  });
});
