/*global jQuery, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  document.getElementById('edit-entry')?.addEventListener('onetabload', () => {
    const tags_node = document.getElementById('tags-edit');
    const id = document.getElementById('id');

    let meta_field = null;
    let meta_editor = null;

    if (tags_node) {
      const post_id = id ? id.value : 0;
      if (!post_id) {
        meta_field = dotclear.htmlToNode('<input type="hidden" name="post_tags">');
      }
      meta_editor = new dotclear.MetaEditor(tags_node, meta_field, 'tag', dotclear.getData('editor_tags_options'));
      meta_editor.meta_url = 'index.php?process=Plugin&p=tags&m=tag_posts&amp;tag=';
      meta_editor.displayMeta('tag', post_id, 'post_meta_tag_input');

      // Retoute h5 label for post_tags to post_meta_tag_input
      const label = document.querySelector('h5 label[for=post_tags]');
      if (label) label.setAttribute('for', 'post_meta_tag_input');

      // mEdit object reference for toolBar
      dotclear.meta_editor_tag = meta_editor;
    }

    const tag_input = document.getElementById('post_meta_tag_input');
    jQuery(tag_input).autocomplete(meta_editor.service_uri, {
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

  const target = document.querySelector('h5 .s-tags');
  if (target) {
    const siblings = document.querySelectorAll('.s-tags:not(label)');
    if (siblings) {
      dotclear.toggleWithLegend(target, siblings, {
        user_pref: 'post_tags',
        legend_click: true,
      });
    }
  }
});
