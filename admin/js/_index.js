/*global $, dotclear, jsToolBar */
'use strict';

dotclear.dbCommentsCount = () => {
  const params = {
    f: 'getCommentsCount',
    xd_check: dotclear.nonce,
  };
  $.get('services.php', params, (data) => {
    if ($('rsp[status=failed]', data).length > 0) {
      // For debugging purpose only:
      // console.log($('rsp',data).attr('message'));
      window.console.log('Dotclear REST server error');
    } else {
      const nb = $('rsp>count', data).attr('ret');
      if (nb != dotclear.dbCommentsCount_Counter) {
        // First pass or counter changed
        const icon = $('#dashboard-main #icons p a[href="comments.php"]');
        if (icon.length) {
          // Update count if exists
          const nb_label = icon.children('span.db-icon-title');
          if (nb_label.length) {
            nb_label.text(nb);
          }
        }
        // Store current counter
        dotclear.dbCommentsCount_Counter = nb;
      }
    }
  });
};
dotclear.dbPostsCount = () => {
  const params = {
    f: 'getPostsCount',
    xd_check: dotclear.nonce,
  };
  $.get('services.php', params, (data) => {
    if ($('rsp[status=failed]', data).length > 0) {
      // For debugging purpose only:
      // console.log($('rsp',data).attr('message'));
      window.console.log('Dotclear REST server error');
    } else {
      const nb = $('rsp>count', data).attr('ret');
      if (nb != dotclear.dbPostsCount_Counter) {
        // First pass or counter changed
        const icon = $('#dashboard-main #icons p a[href="posts.php"]');
        if (icon.length) {
          // Update count if exists
          const nb_label = icon.children('span.db-icon-title');
          if (nb_label.length) {
            nb_label.text(nb);
          }
        }
        // Store current counter
        dotclear.dbPostsCount_Counter = nb;
      }
    }
  });
};
dotclear.dbStoreUpdate = (store, icon, image) => {
  const params = {
    f: 'checkStoreUpdate',
    xd_check: dotclear.nonce,
    store,
  };
  $.post('services.php', params, (data) => {
    if ($('rsp[status=failed]', data).length === 0 && $('rsp>update', data).attr('check') == 1) {
      // Something has to be displayed
      const xml = $('rsp>update', data).attr('ret');
      // update link to details
      icon.children('a').attr('href', `${icon.children('a').attr('href')}#update`);
      // update icon, cope with dc_admin_icon_url() and iconset
      icon
        .children('a')
        .children('img')
        .attr(
          'src',
          icon
            .children('a')
            .children('img')
            .attr('src')
            .replace(/([^\/]+)$/g, `${image}-b-update.png`),
        );
      // add icon text says there is an update
      icon.children('a').children('.db-icon-title').append('<br />').append(xml);
      // Badge (info) on dashboard icon
      const nb = Number($('rsp>update', data).attr('nb'));
      dotclear.badge(icon, {
        id: `mu-${store}`,
        value: nb,
        icon: true,
        type: 'info',
      });
    }
  });
};
$(() => {
  function quickPost(f, status) {
    if (typeof jsToolBar === 'function' && dotclear.contentTb.getMode() == 'wysiwyg') {
      dotclear.contentTb.syncContents('iframe');
    }

    const params = {
      f: 'quickPost',
      xd_check: dotclear.nonce,
      post_title: $('#post_title', f).val(),
      post_content: $('#post_content', f).val(),
      cat_id: $('#cat_id', f).val(),
      post_status: status,
      post_format: $('#post_format', f).val(),
      post_lang: $('#post_lang', f).val(),
      new_cat_title: $('#new_cat_title', f).val(),
      new_cat_parent: $('#new_cat_parent', f).val(),
    };

    $('p.qinfo', f).remove();

    $.post('services.php', params, (data) => {
      let msg;
      if ($('rsp[status=failed]', data).length > 0) {
        msg = `<p class="qinfo"><strong>${dotclear.msg.error}</strong> ${$('rsp', data).text()}</p>`;
      } else {
        msg =
          '<p class="qinfo">' +
          dotclear.msg.entry_created +
          ' - <a href="post.php?id=' +
          $('rsp>post', data).attr('id') +
          '">' +
          dotclear.msg.edit_entry +
          '</a>';
        if ($('rsp>post', data).attr('post_status') == 1) {
          msg += ` - <a href="${$('rsp>post', data).attr('post_url')}">${dotclear.msg.view_entry}</a>`;
        }
        msg += '</p>';
        $('#post_title', f).val('');
        $('#post_content', f).val('');
        $('#post_content', f).change();
        if (typeof jsToolBar === 'function' && dotclear.contentTb.getMode() == 'wysiwyg') {
          dotclear.contentTb.syncContents('textarea');
        }
        $('#cat_id', f).val('0');
        $('#new_cat_title', f).val('');
        $('#new_cat_parent', f).val('0');
      }

      $('fieldset', f).prepend(msg);
    });
  }

  const f = $('#quick-entry');
  if (f.length > 0) {
    if (typeof jsToolBar === 'function') {
      dotclear.contentTb = new jsToolBar($('#post_content', f)[0]);
      dotclear.contentTb.switchMode($('#post_format', f).val());
    }

    $('input[name=save]', f).on('click', () => {
      quickPost(f, -2);
      return false;
    });

    if ($('input[name=save-publish]', f).length > 0) {
      const btn = $(`<input type="submit" value="${$('input[name=save-publish]', f).val()}" />`);
      $('input[name=save-publish]', f).remove();
      $('input[name=save]', f).after(btn).after(' ');
      btn.on('click', () => {
        quickPost(f, 1);
        return false;
      });
    }

    $('#new_cat').toggleWithLegend($('#new_cat').parent().children().not('#new_cat'), {
      // no cookie on new category as we don't use this every day
      legend_click: true,
    });
  }

  // allow to hide quick entry div, and remember choice
  $('#quick h3').toggleWithLegend($('#quick').children().not('h3'), {
    legend_click: true,
    user_pref: 'dcx_quick_entry',
  });

  // check if core update available
  let params = {
    f: 'checkCoreUpdate',
    xd_check: dotclear.nonce,
  };
  $.post('services.php', params, (data) => {
    if ($('rsp[status=failed]', data).length === 0 && $('rsp>update', data).attr('check') == 1) {
      // Something has to be displayed
      const xml = $('rsp>update', data).attr('ret');
      $('#content h2').after(xml);
      // manage outgoing links
      dotclear.outgoingLinks('#ajax-update a');
    }
  });

  // check if store update available, if db has icon
  if ($('#dashboard-main #icons p a[href="plugins.php"]').length) {
    const plugins_db_icon = $('#dashboard-main #icons p a[href="plugins.php"]').parent();
    dotclear.dbStoreUpdate('plugins', plugins_db_icon, 'plugins');
  }
  if ($('#dashboard-main #icons p a[href="blog_theme.php"]').length) {
    const themes_db_icon = $('#dashboard-main #icons p a[href="blog_theme.php"]').parent();
    dotclear.dbStoreUpdate('themes', themes_db_icon, 'blog-theme');
  }

  // check if some news are available
  params = {
    f: 'checkNewsUpdate',
    xd_check: dotclear.nonce,
  };
  $.post('services.php', params, (data) => {
    if ($('rsp[status=failed]', data).length === 0 && $('rsp>news', data).attr('check') == 1) {
      // Something has to be displayed
      const xml = $('rsp>news', data).attr('ret');
      if ($('#dashboard-boxes').length == 0) {
        // Create the #dashboard-boxes container
        $('#dashboard-main').append('<div id="dashboard-boxes"></div>');
      }
      if ($('#dashboard-boxes div.db-items').length == 0) {
        // Create the #dashboard-boxes div.db-items container
        $('#dashboard-boxes').prepend('<div class="db-items"></div>');
      }
      $('#dashboard-boxes div.db-items').prepend(xml);
      // manage outgoing links
      dotclear.outgoingLinks('#ajax-news a');
    }
  });

  // run counters' update on some dashboard icons
  // Comments (including everything)
  if ($('#dashboard-main #icons p a[href="comments.php"]').length) {
    // Icon exists on dashboard
    // First pass
    dotclear.dbCommentsCount();
    // Then fired every 60 seconds (1 minute)
    dotclear.dbCommentsCount_Timer = setInterval(dotclear.dbCommentsCount, 60 * 1000);
  }
  // Posts
  if ($('#dashboard-main #icons p a[href="posts.php"]').length) {
    // Icon exists on dashboard
    // First pass
    dotclear.dbPostsCount();
    // Then fired every 600 seconds (10 minutes)
    dotclear.dbPostsCount_Timer = setInterval(dotclear.dbPostsCount, 600 * 1000);
  }

  if (!dotclear.data.noDragDrop) {
    // Dashboard boxes and their children are sortable
    const set_positions = (sel, id) => {
      const list = $(sel).sortable('toArray').join();
      // Save positions (via services) for id
      const params = {
        f: 'setDashboardPositions',
        xd_check: dotclear.nonce,
        id,
        list,
      };
      $.post('services.php', params, () => {});
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
        areas.forEach((element) => init_positions(element[0], element[1]));
        $(this).prop('title', dotclear.dragndrop_on);
        $('#dragndrop-label').text(dotclear.dragndrop_on);
      } else {
        // Deactivate sorting feature
        areas.forEach((element) => reset_positions(element[0]));
        $(this).prop('title', dotclear.dragndrop_off);
        $('#dragndrop-label').text(dotclear.dragndrop_off);
      }
    });
  }

  // Check adblocker
  dotclear.adblockCheck(dotclear.msg.adblocker);
});
