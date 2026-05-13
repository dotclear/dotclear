/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  document.getElementById('edit-entry')?.addEventListener('onetabload', () => {
    const tags_edit = $('#tags-edit');
    let post_id = $('#id');
    let meta_field = null;
    let meta_editor = null;

    if (tags_edit.length > 0) {
      post_id = post_id.length > 0 ? post_id.get(0).value : false;
      if (!post_id) {
        meta_field = $('<input type="hidden" name="post_tags">');
        meta_field.val($('#post_tags').val());
      }
      meta_editor = new dotclear.MetaEditor(tags_edit, meta_field, 'tag', dotclear.getData('editor_tags_options'));
      meta_editor.meta_url = 'index.php?process=Plugin&p=tags&m=tag_posts&amp;tag=';
      meta_editor.displayMeta('tag', post_id, 'post_meta_tag_input');

      // mEdit object reference for toolBar
      dotclear.meta_editor_tag = meta_editor;
    }

    $('#post_meta_tag_input').autocomplete(meta_editor.service_uri, {
      extraParams: {
        f: 'searchMetadata',
        metaType: 'tag',
        json: 1,
      },
      delay: 1000,
      multiple: true,
      multipleSeparator: ', ',
      matchSubset: false,
      matchContains: true,
      parse(data) {
        const results = [];
        if (data.success) {
          for (const elt of data.payload) {
            results[results.length] = {
              data: {
                id: elt.meta_id,
                count: elt.count,
              },
              result: elt.meta_id,
            };
          }
        }
        return results;
      },
      formatItem(tag) {
        return tag.id;
      },
      formatResult(tag) {
        return tag.result;
      },
    });
  });

  $('h5 .s-tags').toggleWithLegend($('.s-tags').not('label'), {
    user_pref: 'post_tags',
    legend_click: true,
  });
});
