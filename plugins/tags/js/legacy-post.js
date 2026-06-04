/*global dotclear */
'use strict';

// Toolbar button for tags
dotclear.ToolBar.prototype.elements.tag = {
  group: 'metadata',
  type: 'button',
  title: 'Keyword',
  key: 't',
  shortkey_name: 'T',
  fn: {},
};

dotclear.mergeDeep(dotclear.ToolBar.prototype.elements, dotclear.getData('legacy_editor_tags'));

dotclear.ToolBar.prototype.elements.tag.context = 'post';
dotclear.ToolBar.prototype.elements.tag.fn.wiki = function () {
  this.encloseSelection('', '', (str) => {
    if (str === '') {
      globalThis.alert(dotclear.msg.no_selection);
      return '';
    }
    if (str.includes(',')) {
      return str;
    }
    dotclear.meta_editor_tag.addMeta(str);
    return `[${str}|tag:${str}]`;
  });
};
dotclear.ToolBar.prototype.elements.tag.fn.markdown = function () {
  const { url } = this.elements.tag;
  this.encloseSelection('', '', function (str) {
    if (str === '') {
      globalThis.alert(dotclear.msg.no_selection);
      return '';
    }
    if (str.includes(',')) {
      return str;
    }
    dotclear.meta_editor_tag.addMeta(str);
    const href = `${url}/${str}`;
    return `[${str}](${this.stripBaseURL(href)})`;
  });
};
dotclear.ToolBar.prototype.elements.tag.fn.xhtml = function () {
  const { url } = this.elements.tag;
  this.encloseSelection('', '', function (str) {
    if (str === '') {
      globalThis.alert(dotclear.msg.no_selection);
      return '';
    }
    if (str.includes(',')) {
      return str;
    }
    dotclear.meta_editor_tag.addMeta(str);
    const href = `${url}/${str}`;
    return `<a href="${this.stripBaseURL(href)}">${str}</a>`;
  });
};
dotclear.ToolBar.prototype.elements.tag.fn.wysiwyg = function () {
  const t = this.getSelectedText();

  if (t === '') {
    globalThis.alert(dotclear.msg.no_selection);
    return;
  }
  if (t.includes(',')) {
    return;
  }

  const n = this.getSelectedNode();
  const a = document.createElement('a');
  a.href = this.stripBaseURL(`${this.elements.tag.url}/${t}`);
  a.appendChild(n);
  this.insertNode(a);
  dotclear.meta_editor_tag.addMeta(t);
};
