/*global dotclear, jsToolBar */
'use strict';

// Toolbar button for tags
jsToolBar.prototype.elements.tagSpace = {
  type: 'space',
  format: {
    wysiwyg: true,
    wiki: true,
    xhtml: true,
    markdown: true,
  },
};

jsToolBar.prototype.elements.tag = {
  type: 'button',
  title: 'Keyword',
  fn: {},
};

dotclear.mergeDeep(jsToolBar.prototype.elements, dotclear.getData('legacy_editor_tags'));

jsToolBar.prototype.elements.tag.context = 'post';
jsToolBar.prototype.elements.tag.icon = 'index.php?pf=tags/img/tag-add.png';
jsToolBar.prototype.elements.tag.fn.wiki = function () {
  this.encloseSelection('', '', function (str) {
    if (str == '') {
      window.alert(dotclear.msg.no_selection);
      return '';
    }
    if (str.indexOf(',') != -1) {
      return str;
    } else {
      window.dc_tag_editor.addMeta(str);
      return '[' + str + '|tag:' + str + ']';
    }
  });
};
jsToolBar.prototype.elements.tag.fn.markdown = function () {
  const url = this.elements.tag.url;
  this.encloseSelection('', '', function (str) {
    if (str == '') {
      window.alert(dotclear.msg.no_selection);
      return '';
    }
    if (str.indexOf(',') != -1) {
      return str;
    } else {
      window.dc_tag_editor.addMeta(str);
      return '[' + str + '](' + this.stripBaseURL(url + '/' + str) + ')';
    }
  });
};
jsToolBar.prototype.elements.tag.fn.xhtml = function () {
  const url = this.elements.tag.url;
  this.encloseSelection('', '', function (str) {
    if (str == '') {
      window.alert(dotclear.msg.no_selection);
      return '';
    }
    if (str.indexOf(',') != -1) {
      return str;
    } else {
      window.dc_tag_editor.addMeta(str);
      return '<a href="' + this.stripBaseURL(url + '/' + str) + '">' + str + '</a>';
    }
  });
};
jsToolBar.prototype.elements.tag.fn.wysiwyg = function () {
  const t = this.getSelectedText();

  if (t == '') {
    window.alert(dotclear.msg.no_selection);
    return;
  }
  if (t.indexOf(',') != -1) {
    return;
  }

  const n = this.getSelectedNode();
  const a = document.createElement('a');
  a.href = this.stripBaseURL(this.elements.tag.url + '/' + t);
  a.appendChild(n);
  this.insertNode(a);
  window.dc_tag_editor.addMeta(t);
};
