/*global $, dotclear, jsToolBar */
'use strict';

dotclear.dbCommentsCount = (icon) => {
  dotclear.jsonServicesGet('getCommentsCount', (data) => {
    if (data.ret === dotclear.dbCommentsCount_Counter) {
      return;
    }
    // First pass or counter changed
    const nb_label = icon.children('span.db-icon-title');
    if (nb_label.length) {
      nb_label.text(data.ret);
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
    const nb_label = icon.children('span.db-icon-title');
    if (nb_label.length) {
      nb_label.text(data.ret);
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
      icon.children('a').attr('href', `${icon.children('a').attr('href')}#update`);
      // update icon
      icon
        .children('a')
        .children('img')
        .attr(
          'src',
          icon
            .children('a')
            .children('img')
            .attr('src')
            .replace(/([^/]+)(\..*)$/g, '$1-update$2'),
        );
      // add icon text says there is an update
      icon.children('a').children('.db-icon-title').append('<br>').append(data.ret);
      // Badge (info) on dashboard icon
      dotclear.badge(icon, {
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

  function quickPost(f, status) {
    if (typeof jsToolBar === 'function' && dotclear.contentTb.getMode() === 'wysiwyg') {
      dotclear.contentTb.syncContents('iframe');
    }

    $('p.info', f).remove();
    $('p.error', f).remove();

    dotclear.jsonServicesPost(
      'quickPost',
      (data) => {
        let msg = `<p class="info">${dotclear.msg.entry_created} - <a href="post.php?id=${data.id}">${dotclear.msg.edit_entry}</a>`;
        if (data.status === dotclear.post_published) {
          msg += ` - <a href="${data.url}">${dotclear.msg.view_entry}</a>`;
        }
        msg += '</p>';
        $('#post_title', f).val('');
        $('#post_content', f).val('');
        $('#post_content', f).change();
        if (typeof jsToolBar === 'function' && dotclear.contentTb.getMode() === 'wysiwyg') {
          dotclear.contentTb.syncContents('textarea');
        }
        $('#cat_id', f).val('0');
        $('#new_cat_title', f).val('');
        $('#new_cat_parent', f).val('0');
        f.append(msg);
      },
      {
        post_title: $('#post_title', f).val(),
        post_content: $('#post_content', f).val(),
        cat_id: $('#cat_id', f).val(),
        post_status: status,
        post_format: $('#post_format', f).val(),
        post_lang: $('#post_lang', f).val(),
        new_cat_title: $('#new_cat_title', f).val(),
        new_cat_parent: $('#new_cat_parent', f).val(),
      },
      (error) => {
        const msg = `<p class="error"><strong>${dotclear.msg.error}</strong> ${error}</p>`;
        f.append(msg);
      },
    );
  }

  const f = $('#quick-entry');
  if (f.length > 0) {
    Object.assign(dotclear, dotclear.getData('dotclear_quickentry'));

    if (typeof jsToolBar === 'function') {
      dotclear.contentTb = new jsToolBar($('#post_content', f)[0]);
      dotclear.contentTb.switchMode($('#post_format', f).val());
    }

    $('input[name=save]', f).on('click', () => {
      quickPost(f, dotclear.post_pending);
      return false;
    });

    if ($('input[name=save-publish]', f).length > 0) {
      const btn = $(`<input type="submit" value="${$('input[name=save-publish]', f).val()}" name="save-and-publish">`);
      $('input[name=save-publish]', f).remove();
      $('input[name=save]', f).after(btn).after(' ');
      btn.on('click', () => {
        quickPost(f, dotclear.post_published);
        return false;
      });
    }

    // allow to hide quick entry div, and remember choice
    $('#quick h3').toggleWithLegend($('#quick').children().not('h3'), {
      legend_click: true,
      user_pref: 'dcx_quick_entry',
    });
  }

  // outgoing links for documentation
  dotclear.outgoingLinks('#doc-and-support a');

  // check if core update available
  dotclear.jsonServicesGet('checkCoreUpdate', (data) => {
    if (data.check) {
      // Something has to be displayed
      $('#content h2').after(data.ret);
      // manage outgoing links
      dotclear.outgoingLinks('#ajax-update a');
    }
  });

  // check if store update available, if db has icon
  if ($('#dashboard-main #icons p #icon-process-plugins-fav').length) {
    const plugins_db_icon = $('#dashboard-main #icons p #icon-process-plugins-fav').parent();
    dotclear.dbStoreUpdate('plugins', plugins_db_icon);
  }
  if ($('#dashboard-main #icons p #icon-process-blog_theme-fav').length) {
    const themes_db_icon = $('#dashboard-main #icons p #icon-process-blog_theme-fav').parent();
    dotclear.dbStoreUpdate('themes', themes_db_icon);
  }

  // check if some news are available
  dotclear.jsonServicesGet('checkNewsUpdate', (data) => {
    if (!data.check) {
      return;
    }
    // Something has to be displayed
    if ($('#dashboard-boxes').length === 0) {
      // Create the #dashboard-boxes container
      $('#dashboard-main').append('<div id="dashboard-boxes"></div>');
    }
    if ($('#dashboard-boxes div.db-items').length === 0) {
      // Create the #dashboard-boxes div.db-items container
      $('#dashboard-boxes').prepend('<div class="db-items"></div>');
    }
    $('#dashboard-boxes div.db-items').prepend(data.ret);
    // manage outgoing links
    dotclear.outgoingLinks('#ajax-news a');
  });

  // run counters' update on some dashboard icons
  // Comments (including everything)
  const icon_comments = $('#dashboard-main #icons p #icon-process-comments-fav');
  if (icon_comments.length) {
    // Icon exists on dashboard
    // First pass
    dotclear.dbCommentsCount(icon_comments);
    // Then fired every 60 seconds (1 minute)
    dotclear.dbCommentsCount_Timer = setInterval(dotclear.dbCommentsCount, 60 * 1000, icon_comments);
  }
  // Posts
  const icon_posts = $('#dashboard-main #icons p #icon-process-posts-fav');
  if (icon_posts.length) {
    // Icon exists on dashboard
    // First pass
    dotclear.dbPostsCount(icon_posts);
    // Then fired every 600 seconds (10 minutes)
    dotclear.dbPostsCount_Timer = setInterval(dotclear.dbPostsCount, 600 * 1000, icon_posts);
  }

  if (dotclear.data.noDragDrop) {
    return;
  }
  // Dashboard boxes and their children are sortable
  const set_positions = (sel, id) => {
    const list = $(sel).sortable('toArray').join();
    // Save positions (via services) for id
    dotclear.jsonServicesPost('setDashboardPositions', () => {}, { id, list });
  };
  const init_positions = (sel, id) => {
    $(sel).sortable({
      cursor: 'move',
      opacity: 0.5,
      delay: 200,
      distance: 10,
      tolerance: 'pointer',
      update() {
        set_positions(sel, id);
      },
      start() {
        $(sel).addClass('sortable-area');
      },
      stop() {
        $(sel).removeClass('sortable-area');
      },
    });
  };
  const reset_positions = (sel) => {
    $(sel).sortable('destroy');
  };
  // List of sortable areas
  const areas = [
    ['#dashboard-main', 'main_order'],
    ['#dashboard-boxes', 'boxes_order'],
    ['#db-items', 'boxes_items_order'],
    ['#db-contents', 'boxes_contents_order'],
  ];
  // Set or reset sortable depending on #dragndrop checbkox value
  $('#dragndrop').on('click', function () {
    Object.assign(dotclear, dotclear.getData('dotclear_dragndrop'));
    if ($(this).is(':checked')) {
      // Activate sorting feature
      for (const element of areas) init_positions(element[0], element[1]);
      $(this).prop('title', dotclear.dragndrop_on);
      $('#dragndrop-label').text(dotclear.dragndrop_on);
      return;
    }
    // Deactivate sorting feature
    for (const element of areas) reset_positions(element[0]);
    $(this).prop('title', dotclear.dragndrop_off);
    $('#dragndrop-label').text(dotclear.dragndrop_off);
  });
});
