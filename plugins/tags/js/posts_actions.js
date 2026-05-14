/*global jQuery, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  const tag_field = document.getElementById('new_tags');

  tag_field.after(dotclear.htmlToNode('<div id="tags_list"></div>'));
  tag_field.style.display = 'none';

  const target = document.getElementById('tags_list');
  const meta_editor = new dotclear.MetaEditor(target, tag_field, 'tag', dotclear.getData('editor_tags_options'));

  meta_editor.meta_url = 'index.php?process=Plugin&p=tags&m=tag_posts&amp;tag=';

  meta_editor.meta_dialog = dotclear.htmlToNode('<input type="text">');
  meta_editor.meta_dialog.setAttribute('title', meta_editor.text_add_meta.replace(/%s/, meta_editor.meta_type));
  meta_editor.meta_dialog.setAttribute('id', 'post_meta_tag_input');
  meta_editor.meta_dialog.style.width = '90%';

  meta_editor.addMetaDialog();

  const save_tags = document.querySelector('input[name="save_tags"]');
  save_tags?.addEventListener('click', () => {
    const tag_input = document.getElementById('post_meta_tag_input');
    tag_field.value = tag_input?.value;
  });

  const tag_input = document.getElementById('post_meta_tag_input');
  jQuery(tag_input).autocomplete(meta_editor.service_uri, {
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
