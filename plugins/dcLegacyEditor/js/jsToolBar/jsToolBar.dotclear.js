/*global jsToolBar */
'use strict';

/* Change link button actions
-------------------------------------------------------- */
jsToolBar.prototype.elements.link.data = {};
jsToolBar.prototype.elements.link.fncall = {};
jsToolBar.prototype.elements.link.open_url = 'popup_link.php?plugin_id=dcLegacyEditor';

jsToolBar.prototype.elements.link.popup = function (args = '') {
  window.the_toolbar = this;

  this.elements.link.data = {};
  const url = this.elements.link.open_url + args;

  window.open(
    url,
    'dc_popup',
    'alwaysRaised=yes,dependent=yes,toolbar=yes,height=420,width=520,menubar=no,resizable=yes,scrollbars=yes,status=no',
  );
};

jsToolBar.prototype.elements.link.fn.wiki = function () {
  this.elements.link.popup.call(this, `&hreflang=${this.elements.link.default_hreflang}`);
};
jsToolBar.prototype.elements.link.fncall.wiki = function () {
  const { data } = this.elements.link;

  if (data.href == '') {
    return;
  }

  let etag = `|${data.href}`;
  if (data.hreflang) {
    etag += `|${data.hreflang}`;
  }

  if (data.title) {
    if (!data.hreflang) {
      etag += '|';
    }
    etag += `|${data.title}`;
  }

  if (data.content) {
    this.encloseSelection(`[${data.content}`, `${etag}]`);
  } else {
    this.encloseSelection('[', `${etag}]`);
  }
};

jsToolBar.prototype.elements.link.fn.xhtml = function () {
  this.elements.link.popup.call(this, `&hreflang=${this.elements.link.default_hreflang}`);
};
jsToolBar.prototype.elements.link.fncall.xhtml = function () {
  const { data } = this.elements.link;

  if (data.href == '') {
    return;
  }

  let stag = `<a href="${data.href}"`;

  if (data.hreflang) {
    stag += ` hreflang="${data.hreflang}"`;
  }
  if (data.title) {
    stag += ` title="${data.title}"`;
  }
  stag += '>';
  const etag = '</a>';

  if (data.content) {
    this.encloseSelection('', '', () => stag + data.content + etag);
  } else {
    this.encloseSelection(stag, etag);
  }
};

jsToolBar.prototype.elements.link.fn.wysiwyg = function () {
  let href;
  let title;
  let hreflang;
  href = title = '';
  hreflang = this.elements.link.default_hreflang;

  const a = this.getAncestor();

  if (a.tagName == 'a') {
    href = a.tag.href || '';
    title = a.tag.title || '';
    hreflang = a.tag.hreflang || '';
  }

  this.elements.link.popup.call(this, `&href=${href}&hreflang=${hreflang}&title=${title}`);
};
jsToolBar.prototype.elements.link.fncall.wysiwyg = function () {
  const { data } = this.elements.link;

  let a = this.getAncestor();

  if (a.tagName == 'a') {
    if (data.href == '') {
      // Remove link
      this.replaceNodeByContent(a.tag);
      this.iwin.focus();
    } else {
      // Update link
      a.tag.href = data.href;
      if (data.hreflang) {
        a.tag.setAttribute('hreflang', data.hreflang);
      } else {
        a.tag.removeAttribute('hreflang');
      }
      if (data.title) {
        a.tag.setAttribute('title', data.title);
      } else {
        a.tag.removeAttribute('title');
      }
    }
    return;
  }

  // Create link
  const n = data.content ? document.createTextNode(data.content) : this.getSelectedNode();
  a = this.iwin.document.createElement('a');
  a.href = data.href;
  if (data.hreflang) a.setAttribute('hreflang', data.hreflang);
  if (data.title) a.setAttribute('title', data.title);
  a.appendChild(n);
  this.insertNode(a);
};
jsToolBar.prototype.getAncestor = function () {
  const res = {};
  let range;
  let commonAncestorContainer;

  if (this.iwin.getSelection) {
    //gecko
    const selection = this.iwin.getSelection();
    range = selection.getRangeAt(0);
    commonAncestorContainer = range.commonAncestorContainer;
    while (commonAncestorContainer.nodeType != 1) {
      commonAncestorContainer = commonAncestorContainer.parentNode;
    }
  } else {
    //ie
    range = this.iwin.document.selection.createRange();
    commonAncestorContainer = range.parentElement();
  }

  let ancestorTagName = commonAncestorContainer.tagName.toLowerCase();
  while (ancestorTagName != 'a' && ancestorTagName != 'body') {
    commonAncestorContainer = commonAncestorContainer.parentNode;
    ancestorTagName = commonAncestorContainer.tagName.toLowerCase();
  }

  res.tag = commonAncestorContainer;
  res.tagName = ancestorTagName;

  return res;
};

/* Image selector
-------------------------------------------------------- */
jsToolBar.prototype.elements.img_select = {
  type: 'button',
  title: 'Image chooser',
  accesskey: 'm',
  fn: {},
  fncall: {},
  open_url: 'media.php?popup=1&plugin_id=dcLegacyEditor',
  data: {},
  popup() {
    window.the_toolbar = this;
    this.elements.img_select.data = {};

    window.open(
      this.elements.img_select.open_url,
      'dc_popup',
      'alwaysRaised=yes,dependent=yes,toolbar=yes,height=500,width=760,menubar=no,resizable=yes,scrollbars=yes,status=no',
    );
  },
};
jsToolBar.prototype.elements.img_select.fn.wiki = function () {
  this.elements.img_select.popup.call(this);
};
jsToolBar.prototype.elements.img_select.fncall.wiki = function () {
  const d = this.elements.img_select.data;
  if (d.src == undefined) {
    return;
  }

  this.encloseSelection('', '', (str) => {
    const alt = str ? str : d.title;
    let res = `((${d.src}|${alt}`;

    if (d.alignment == 'left') {
      res += '|L';
    } else if (d.alignment == 'right') {
      res += '|R';
    } else if (d.alignment == 'center') {
      res += '|C';
    } else if (d.description) {
      res += '|';
    }
    if (d.title) {
      res += `|${d.title}`;
    }
    if (d.description) {
      res += `|${d.description}`;
    }

    res += '))';

    if (d.link) {
      return `[${res}|${d.url}${alt ? `||${alt}` : ''}]`;
    }

    return res;
  });
};
jsToolBar.prototype.elements.img_select.fn.xhtml = function () {
  this.elements.img_select.popup.call(this);
};
jsToolBar.prototype.elements.img_select.fncall.xhtml = function () {
  const d = this.elements.img_select.data;
  if (d.src == undefined) {
    return;
  }

  this.encloseSelection('', '', (str) => {
    const alt = str ? str : d.title;
    let res = `<img src="${d.src}" alt="${alt
      .replace('&', '&amp;')
      .replace('>', '&gt;')
      .replace('<', '&lt;')
      .replace('"', '&quot;')}"`;

    if (d.alignment == 'left') {
      res += ' style="float: left; margin: 0 1em 1em 0;"';
    } else if (d.alignment == 'right') {
      res += ' style="float: right; margin: 0 0 1em 1em;"';
    } else if (d.alignment == 'center') {
      res += ' style="margin: 0 auto; display: block;"';
    }

    if (d.description) {
      res += ` title="${d.description.replace('&', '&amp;').replace('>', '&gt;').replace('<', '&lt;').replace('"', '&quot;')}"`;
    }

    res += ' />';

    if (d.link) {
      const ltitle = alt
        ? ` title="${alt.replace('&', '&amp;').replace('>', '&gt;').replace('<', '&lt;').replace('"', '&quot;')}"`
        : '';
      return `<a href="${d.url}"${ltitle}>${res}</a>`;
    }

    return res;
  });
};

jsToolBar.prototype.elements.img.fn.wysiwyg = function () {
  const src = this.elements.img.prompt.call(this);
  if (!src) {
    return;
  }

  const img = this.iwin.document.createElement('img');
  img.src = src;
  img.setAttribute('alt', this.getSelectedText());

  this.insertNode(img);
};

jsToolBar.prototype.elements.img_select.fn.wysiwyg = function () {
  this.elements.img_select.popup.call(this);
};
jsToolBar.prototype.elements.img_select.fncall.wysiwyg = function () {
  const d = this.elements.img_select.data;
  const alt = this.getSelectedText() ? this.getSelectedText() : d.title;
  if (d.src == undefined) {
    return;
  }

  const fig = d.description ? this.iwin.document.createElement('figure') : null;
  const img = this.iwin.document.createElement('img');
  const block = d.description ? fig : img;

  if (d.alignment == 'left') {
    if (block.style.styleFloat == undefined) {
      block.style.cssFloat = 'left';
    } else {
      block.style.styleFloat = 'left';
    }
    block.style.marginTop = 0;
    block.style.marginRight = '1em';
    block.style.marginBottom = '1em';
    block.style.marginLeft = 0;
  } else if (d.alignment == 'right') {
    if (block.style.styleFloat == undefined) {
      block.style.cssFloat = 'right';
    } else {
      block.style.styleFloat = 'right';
    }
    block.style.marginTop = 0;
    block.style.marginRight = 0;
    block.style.marginBottom = '1em';
    block.style.marginLeft = '1em';
  } else if (d.alignment == 'center') {
    if (d.description) {
      block.style.textAlign = 'center';
    } else {
      block.style.marginTop = 0;
      block.style.marginRight = 'auto';
      block.style.marginBottom = 0;
      block.style.marginLeft = 'auto';
      block.style.display = 'block';
    }
  }

  img.src = d.src;
  img.setAttribute('alt', alt);
  if (d.title) {
    img.setAttribute('title', d.title);
  }
  if (d.description) {
    const figcaption = this.iwin.document.createElement('figcaption');
    figcaption.appendChild(this.iwin.document.createTextNode(d.description));
    fig.appendChild(img);
    fig.appendChild(figcaption);
  }

  if (d.link) {
    const a = this.iwin.document.createElement('a');
    a.href = d.url;
    if (alt) {
      a.setAttribute('title', alt);
    }
    a.appendChild(block);
    this.insertNode(a);
  } else {
    this.insertNode(block);
  }
};

// MP3 helpers
jsToolBar.prototype.elements.mp3_insert = {
  fncall: {},
  data: {},
};
jsToolBar.prototype.elements.mp3_insert.fncall.wiki = function () {
  const d = this.elements.mp3_insert.data;
  if (d.player == undefined) {
    return;
  }

  this.encloseSelection('', '', () => `\n///html\n${d.player}///\n`);
};
jsToolBar.prototype.elements.mp3_insert.fncall.xhtml = function () {
  const d = this.elements.mp3_insert.data;
  if (d.player == undefined) {
    return;
  }

  this.encloseSelection('', '', () => `\n${d.player}\n`);
};
jsToolBar.prototype.elements.mp3_insert.fncall.wysiwyg = () => {
  return;
};

// FLV helpers
jsToolBar.prototype.elements.flv_insert = {
  fncall: {},
  data: {},
};
jsToolBar.prototype.elements.flv_insert.fncall.wiki = function () {
  const d = this.elements.flv_insert.data;
  if (d.player == undefined) {
    return;
  }

  this.encloseSelection('', '', () => `\n///html\n${d.player}///\n`);
};
jsToolBar.prototype.elements.flv_insert.fncall.xhtml = function () {
  const d = this.elements.flv_insert.data;
  if (d.player == undefined) {
    return;
  }

  this.encloseSelection('', '', () => `\n${d.player}\n`);
};
jsToolBar.prototype.elements.flv_insert.fncall.wysiwyg = () => {
  return;
};

/* Posts selector
-------------------------------------------------------- */
jsToolBar.prototype.elements.post_link = {
  type: 'button',
  title: 'Link to an entry',
  fn: {},
  open_url: 'popup_posts.php?plugin_id=dcLegacyEditor',
  data: {},
  popup() {
    window.the_toolbar = this;
    this.elements.img_select.data = {};

    window.open(
      this.elements.post_link.open_url,
      'dc_popup',
      'alwaysRaised=yes,dependent=yes,toolbar=yes,height=500,width=760,menubar=no,resizable=yes,scrollbars=yes,status=no',
    );
  },
};
jsToolBar.prototype.elements.post_link.fn.wiki = function () {
  this.elements.post_link.popup.call(this);
};
jsToolBar.prototype.elements.post_link.fn.xhtml = function () {
  this.elements.post_link.popup.call(this);
};
jsToolBar.prototype.elements.post_link.fn.wysiwyg = function () {
  this.elements.post_link.popup.call(this);
};

// Last space element
jsToolBar.prototype.elements.space3 = {
  type: 'space',
  format: {
    wysiwyg: true,
    wiki: true,
    xhtml: true,
  },
};
