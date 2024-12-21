/*global $, dotclear, metaEditor */
'use strict';

dotclear.mergeDeep(dotclear.msg, dotclear.getData('editor_tags_msg'));

dotclear.ready(() => {
  // DOM ready and content loaded

  $('#edit-entry').on('onetabload', () => {
    const tags_edit = $('#tags-edit');
    let post_id = $('#id');
    let meta_field = null;
    let mEdit = null;

    if (tags_edit.length > 0) {
      post_id = post_id.length > 0 ? post_id.get(0).value : false;
      if (!post_id) {
        meta_field = $('<input type="hidden" name="post_tags">');
        meta_field.val($('#post_tags').val());
      }
      mEdit = new metaEditor(tags_edit, meta_field, 'tag', dotclear.getData('editor_tags_options'));
      mEdit.meta_url = 'index.php?process=Plugin&p=tags&m=tag_posts&amp;tag=';
      mEdit.displayMeta('tag', post_id, 'post_meta_tag_input');

      // mEdit object reference for toolBar
      window.dc_tag_editor = mEdit;
    }

    $('#post_meta_tag_input').autocomplete(mEdit.service_uri, {
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
                percent: elt.roundpercent,
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
