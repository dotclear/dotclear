/*global dotclear, jQuery */
'use strict';

dotclear.MetaEditor = class {
  constructor(target, meta_field, meta_type, meta_options = {}) {
    this.meta_url = meta_options.meta_url || '';
    this.text_confirm_remove = meta_options.text_confirm_remove || 'Are you sure you want to remove this %s?';
    this.text_add_meta = meta_options.text_add_meta || 'Add a %s to this entry';
    this.text_choose = meta_options.text_choose || 'Choose from list';
    this.text_all = meta_options.text_all || 'all';
    this.text_separation = meta_options.text_separation || 'Separate each %s by comas';
    this.list_type = meta_options.list_type || 'more';

    this.target = target;
    this.target_node = target instanceof jQuery ? target.get(0) : target;

    this.meta_type = meta_type;
    this.meta_dialog = null;

    this.meta_field = meta_field;
    this.meta_field_node = meta_field instanceof jQuery ? meta_field.get(0) : meta_field;

    this.submit_button = null;
    this.post_id = false;

    this.service_uri = dotclear.servicesUri;
  }

  displayMeta(type, post_id, input_id = 'post_meta_input') {
    this.meta_type = type;
    this.post_id = post_id;
    this.target_node.replaceChildren();

    this.meta_dialog = dotclear.htmlToNode(
      `<input type="text" class="ib meta-helper" title="${this.text_add_meta.replace(/%s/, this.meta_type)}" id="${input_id}">`,
    );
    // Meta dialog input
    this.meta_dialog.addEventListener('keydown', (event) => {
      // We don't want to submit form!
      if (event.key === 'Enter') {
        event.stopPropagation();
        return false;
      }
      return true;
    });
    this.meta_dialog.addEventListener('keyup', (event) => {
      // We don't want to submit form!
      if (event.key === 'Enter') {
        this.addMeta(event.currentTarget.value);
        event.preventDefault();
        return false;
      }
      return true;
    });

    this.submit_button = dotclear.htmlToNode('<input type="button" value="ok" class="ib meta-helper">');
    this.submit_button.addEventListener('click', () => {
      this.addMeta(this.meta_dialog.value);
      return false;
    });

    this.addMetaDialog();

    if (!this.post_id) {
      this.target_node.append(this.meta_field_node);
    }
    this.displayMetaList();
  }

  displayMetaList() {
    if (this.meta_list === undefined) {
      this.meta_list = dotclear.htmlToNode('<ul class="metaList"></ul>');
      this.target_node.prepend(this.meta_list);
    }

    if (!this.post_id) {
      const meta = this.splitMetaValues(this.meta_field_node.value);

      this.meta_list.replaceChildren();
      for (const m of meta) {
        const li = dotclear.htmlToNode(`<li>${m}</li>`);
        const a_remove = dotclear.htmlToNode(
          '<button type="button" class="metaRemove meta-helper"><img src="images/trash.svg" alt="remove"></button>',
        );
        a_remove.meta_id = m;
        a_remove.addEventListener('click', (event) => {
          this.removeMeta(event.currentTarget.meta_id);
          return false;
        });
        li.prepend(a_remove);
        this.meta_list.append(li);
      }
      return;
    }

    const displayDataList = (data) => {
      this.meta_list.replaceChildren();
      for (const elt of data) {
        const { meta_id, uri } = elt;
        const li = dotclear.htmlToNode(`<li><a href="${this.meta_url}${uri}">${meta_id}</a></li>`);
        const a_remove = dotclear.htmlToNode(
          '<button type="button" class="metaRemove meta-helper"><img src="images/trash.svg" alt="remove"></button>',
        );
        a_remove.meta_id = meta_id;
        a_remove.addEventListener('click', (event) => {
          this.removeMeta(event.currentTarget.meta_id);
          return false;
        });
        li.prepend(a_remove);
        this.meta_list.append(li);
      }
    };

    dotclear.jsonServicesGet('getMeta', (data) => displayDataList(data), {
      metaType: this.meta_type,
      sortby: 'metaId,asc',
      postId: this.post_id,
    });
  }

  addMetaDialog() {
    // Cope with jQuery selector
    this.meta_dialog = this.meta_dialog instanceof jQuery ? this.meta_dialog.get(0) : this.meta_dialog;

    if (this.submit_button === null) {
      const para = dotclear.htmlToNode('<p></p>');
      para.append(this.meta_dialog);
      this.target_node.append(para);
    } else {
      const para = dotclear.htmlToNode('<p></p>');
      para.append(this.meta_dialog);
      para.append(' ');
      para.append(this.submit_button);
      this.target_node.append(para);
    }

    if (this.text_separation !== '') {
      const para = dotclear.htmlToNode('<p></p>');
      para.classList.add('form-note');
      para.append(this.text_separation.replace(/%s/, this.meta_type));
      this.target_node.append(para);
    }

    this.showMetaList(this.list_type, this.target_node);
  }

  showMetaList(list_type, targetNode) {
    const params = { metaType: this.meta_type, sortby: 'metaId,asc' };
    if (list_type === 'more') {
      params.limit = 30;
    }
    dotclear.jsonServicesGet(
      'getMeta',
      (data) => {
        const pl = dotclear.htmlToNode('<p class="addMeta"></p>');

        const cleanup = targetNode.querySelectorAll('.addMeta');
        for (const element of cleanup) element.remove();

        if (data.length > 0) {
          pl.replaceChildren();

          let i = 0;
          for (const elt of data) {
            const meta_link = dotclear.htmlToNode(`<button type="button" class="metaItem meta-helper">${elt.meta_id}</button>`);
            meta_link.meta_id = elt.meta_id;
            meta_link.addEventListener('click', () => {
              const v = this.splitMetaValues(`${this.meta_dialog.value},${elt.meta_id}`);
              this.meta_dialog.value = v.join(',');
              return false;
            });

            if (i > 0) {
              pl.append(', ');
            }
            pl.append(meta_link);
            i++;
          }

          if (list_type === 'more') {
            const a_more = dotclear.htmlToNode('<button type="button" class="button metaGetMore meta-helper"></button>');
            a_more.append(this.text_all + String.fromCodePoint(160, 187));
            a_more.addEventListener('click', () => {
              this.showMetaList('more-all', targetNode);
              return false;
            });
            pl.append(' ... ');
            pl.append(a_more);
          }

          if (list_type !== 'more-all') {
            pl.classList.add('hide');

            const pa = dotclear.htmlToNode('<p></p>');
            targetNode.append(pa);

            const a = dotclear.htmlToNode(
              `<button type="button" class="button metaGetList meta-helper">${this.text_choose}</button>`,
            );
            a.addEventListener('click', (event) => {
              event.currentTarget?.parentNode?.nextElementSibling.classList.remove('hide');
              event.currentTarget.remove();
              return false;
            });

            pa.append(a);
          }

          targetNode.append(pl);
          return;
        }
        pl.replaceChildren();
      },
      params,
    );
  }

  addMeta(str) {
    let list = this.splitMetaValues(str).join(',');
    if (!this.post_id) {
      list = this.splitMetaValues(`${this.meta_field_node.value},${list}`);
      this.meta_field_node.value = list;

      this.meta_dialog.value = '';
      this.displayMetaList();
      return;
    }

    dotclear.jsonServicesPost(
      'setPostMeta',
      (_data) => {
        this.meta_dialog.value = '';
        this.displayMetaList();
      },
      {
        postId: this.post_id,
        metaType: this.meta_type,
        meta: list,
      },
    );
  }

  removeMeta(meta_id) {
    if (!this.post_id) {
      const meta = this.splitMetaValues(this.meta_field_node.value);
      const i = meta.indexOf(meta_id);
      if (i >= 0) {
        meta.splice(i, 1);
      }
      this.meta_field_node.value = meta.join(',');
      this.displayMetaList();
      return;
    }
    const text_confirm_msg = this.text_confirm_remove.replace(/%s/, this.meta_type);

    if (globalThis.confirm(text_confirm_msg)) {
      dotclear.jsonServicesPost('delMeta', (_data) => this.displayMetaList(), {
        postId: this.post_id,
        metaId: meta_id,
        metaType: this.meta_type,
      });
    }
  }

  splitMetaValues(str) {
    const list = new Set(
      str
        .split(',')
        .map((s) => s.trim())
        .filter(Boolean),
    );
    return Array.from(list).sort((a, b) => a.localeCompare(b));
  }
};

if (!dotclear.modern) {
  // Dotclear Legacy (may be deleted in future)

  // biome-ignore lint/correctness/noInnerDeclarations: <legacy code>
  // biome-ignore lint/correctness/noUnusedVariables: <legacy code>
  var metaEditor = dotclear.MetaEditor;
}
