/*global $, dotclear, metaEditor */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  const tag_field = $('#new_tags');

  tag_field.after('<div id="tags_list"></div>');
  tag_field.hide();

  const target = $('#tags_list');
  const mEdit = new metaEditor(target, tag_field, 'tag', dotclear.getData('editor_tags_options'));

  mEdit.meta_url = 'index.php?process=Plugin&p=tags&m=tag_posts&amp;tag=';

  mEdit.meta_dialog = $('<input type="text">');
  mEdit.meta_dialog.attr('title', mEdit.text_add_meta.replace(/%s/, mEdit.meta_type));
  mEdit.meta_dialog.attr('id', 'post_meta_tag_input');
  mEdit.meta_dialog.css('width', '90%');

  mEdit.addMetaDialog();

  $('input[name="save_tags"]').on('click', () => {
    tag_field.val($('#post_meta_tag_input').val());
  });

  $('#post_meta_tag_input').autocomplete(mEdit.service_uri, {
    extraParams: {
      f: 'searchMetadata',
      metaType: 'tag',
      json: 1,
    },
    delay: 1000,
    multiple: true,
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
