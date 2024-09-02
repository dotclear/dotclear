/*global jsToolBar, dotclear */
'use strict';

jsToolBar.prototype.can_wwg = document.designMode != undefined;
jsToolBar.prototype.iframe = null;
jsToolBar.prototype.iwin = null;
jsToolBar.prototype.ibody = null;
jsToolBar.prototype.iframe_css = null;

/* Editor methods
-------------------------------------------------------- */
jsToolBar.prototype.drawToolBar = jsToolBar.prototype.draw;
jsToolBar.prototype.draw = function (mode = 'xhtml') {
  if (this.can_wwg) {
    this.mode = 'wysiwyg';
    this.drawToolBar('wysiwyg');
    this.initWindow();
    return;
  }
  this.drawToolBar(mode);
};

jsToolBar.prototype.switchMode = function (mode = 'xhtml') {
  if (mode == 'xhtml') {
    this.wwg_mode = true;
    this.draw(mode);
    return;
  }
  if (this.wwg_mode) {
    this.syncContents('iframe');
  }
  this.wwg_mode = false;
  this.removeEditor();
  this.textarea.style.display = '';
  this.drawToolBar(mode);
};

jsToolBar.prototype.syncContents = function (from = 'textarea') {
  const This = this;
  if (from == 'textarea') {
    initContent();
  } else {
    this.validBlockquote();
    let html = this.applyHtmlFilters(this.ibody.innerHTML);
    if (html == '<br />' || html == '<br>') {
      html = '<p></p>';
    }
    this.textarea.value = html;
  }

  function initContent() {
    if (!This.iframe.contentWindow.document || !This.iframe.contentWindow.document.body) {
      setTimeout(initContent, 1);
      return;
    }
    This.ibody = This.iframe.contentWindow.document.body;

    if (This.textarea.value != '' && This.textarea.value != '<p></p>') {
      This.ibody.innerHTML = This.applyWysiwygFilters(This.textarea.value);
      if (This.ibody.createTextRange) {
        //cursor at the begin for IE
        const IErange = This.ibody.createTextRange();
        IErange.execCommand('SelectAll');
        IErange.collapse();
        IErange.select();
      }
      return;
    }
    const idoc = This.iwin.document;
    const para = idoc.createElement('p');
    para.appendChild(idoc.createElement('br'));
    while (idoc.body.hasChildNodes()) {
      idoc.body.removeChild(idoc.body.lastChild);
    }
    idoc.body.appendChild(para);
  }
};
jsToolBar.prototype.htmlFilters = {
  tagsoup(str) {
    return this.tagsoup2xhtml(str);
  },
};
jsToolBar.prototype.applyHtmlFilters = function (str) {
  for (const fn in this.htmlFilters) {
    str = this.htmlFilters[fn].call(this, str);
  }
  return str;
};
jsToolBar.prototype.wysiwygFilters = {};
jsToolBar.prototype.applyWysiwygFilters = function (str) {
  for (const fn in this.wysiwygFilters) {
    str = this.wysiwygFilters[fn].call(this, str);
  }
  return str;
};

jsToolBar.prototype.switchEdit = function () {
  if (this.wwg_mode) {
    this.textarea.style.display = '';
    this.iframe.style.display = 'none';
    this.syncContents('iframe');
    this.drawToolBar('xhtml');
    this.wwg_mode = false;
  } else {
    this.iframe.style.display = '';
    this.textarea.style.display = 'none';
    this.syncContents('textarea');
    this.drawToolBar('wysiwyg');
    this.wwg_mode = true;
  }
  this.focusEditor();
  this.setSwitcher();
};

/** Creates iframe for editor, inits a blank document
 */
jsToolBar.prototype.initWindow = function () {
  const This = this;

  this.iframe = document.createElement('iframe');
  this.textarea.parentNode.insertBefore(this.iframe, this.textarea.nextSibling);

  this.switcher = document.createElement('ul');
  this.switcher.className = 'jstSwitcher';
  this.editor.appendChild(this.switcher);

  this.iframe.height = this.textarea.offsetHeight + 0;
  this.iframe.width = this.textarea.offsetWidth + 0;

  if (this.textarea.tabIndex != undefined) {
    this.iframe.tabIndex = this.textarea.tabIndex;
  }

  function initIframe() {
    const doc = This.iframe.contentWindow.document;
    if (!doc) {
      setTimeout(initIframe, 1);
      return false;
    }

    doc.open();
    const html = `<html>
  <head>
    <link rel="stylesheet" href="style/default.css" type="text/css" media="screen">
    <style type="text/css">${This.iframe_css}</style>
    ${This.base_url == '' ? '' : `<base href="${This.base_url}">`}
  </head>
  <body id="${This.textarea.id}-jstEditorIframe"></body>
</html>`;

    doc.write(html);
    doc.close();

    if (dotclear?.data?.htmlFontSize) {
      doc.documentElement.style.setProperty('--html-font-size', dotclear.data.htmlFontSize);
    }

    // Set lang if set for the textarea
    if (This.textarea.lang) {
      doc.documentElement.setAttribute('lang', This.textarea.lang);
    }

    This.iwin = This.iframe.contentWindow;

    This.syncContents('textarea');

    if (This.wwg_mode == undefined) {
      This.wwg_mode = true;
    }

    if (This.wwg_mode) {
      This.textarea.style.display = 'none';
    } else {
      This.iframe.style.display = 'none';
    }

    // update textarea on submit
    if (This.textarea.form) {
      This.textarea.form.addEventListener('submit', () => {
        if (This.wwg_mode) {
          This.syncContents('iframe');
        }
      });
    }

    for (const evt in This.iwinEvents) {
      const event = This.iwinEvents[evt];
      This.addIwinEvent(This.iframe.contentWindow.document, event.type, event.fn, This);
    }

    This.setSwitcher();
    try {
      This.iwin.document.designMode = 'on';
    } catch (e) {} // Firefox needs this

    return true;
  }
  initIframe();
};
jsToolBar.prototype.addIwinEvent = (target, type, fn, scope) => {
  const myFn = (e) => {
    fn.call(scope, e);
  };
  addEvent(target, type, myFn, true);
  // fix memory leak
  addEvent(
    scope.iwin,
    'unload',
    () => {
      removeEvent(target, type, myFn, true);
    },
    true,
  );
};
jsToolBar.prototype.iwinEvents = {
  block1: {
    type: 'mouseup',
    fn() {
      this.adjustBlockLevelCombo();
    },
  },
  block2: {
    type: 'keyup',
    fn() {
      this.adjustBlockLevelCombo();
    },
  },
};

/** Insert a mode switcher after editor area
 */
jsToolBar.prototype.switcher_visual_title = 'visual';
jsToolBar.prototype.switcher_source_title = 'source';
jsToolBar.prototype.setSwitcher = function () {
  while (this.switcher.hasChildNodes()) {
    this.switcher.removeChild(this.switcher.firstChild);
  }

  const This = this;

  function setLink(title, link) {
    const li = document.createElement('li');
    let a;
    if (link) {
      a = document.createElement('a');
      a.href = '#';
      a.editor = This;
      a.onclick = function () {
        this.editor.switchEdit();
        return false;
      };
      a.appendChild(document.createTextNode(title));
    } else {
      li.className = 'jstSwitcherCurrent';
      a = document.createTextNode(title);
    }

    li.appendChild(a);
    This.switcher.appendChild(li);
  }

  setLink(this.switcher_visual_title, !this.wwg_mode);
  setLink(this.switcher_source_title, this.wwg_mode);
};

/** Removes editor area and mode switcher
 */
jsToolBar.prototype.removeEditor = function () {
  if (this.iframe != null) {
    this.iframe.parentNode.removeChild(this.iframe);
    this.iframe = null;
  }

  if (this.switcher != undefined && this.switcher.parentNode != undefined) {
    this.switcher.parentNode.removeChild(this.switcher);
  }
};

/** Focus on the editor area
 */
jsToolBar.prototype.focusEditor = function () {
  if (this.wwg_mode) {
    try {
      this.iwin.document.designMode = 'on';
    } catch (e) {} // Firefox needs this
    setTimeout(() => {
      this.iframe.contentWindow.focus();
    }, 1);
  } else {
    this.textarea.focus();
  }
};

/** Resizer
 */
jsToolBar.prototype.resizeSetStartH = function () {
  if (this.wwg_mode && this.iframe != undefined) {
    this.dragStartH = this.iframe.offsetHeight;
    return;
  }
  this.dragStartH = this.textarea.offsetHeight + 0;
};
jsToolBar.prototype.resizeDragMove = function (event) {
  const new_height = `${this.dragStartH + event.clientY - this.dragStartY}px`;
  if (this.iframe != undefined) {
    this.iframe.style.height = new_height;
  }
  this.textarea.style.height = new_height;
};

/* Editing methods
-------------------------------------------------------- */
/** Replaces current selection by given node
 */
jsToolBar.prototype.insertNode = function (node) {
  let range;

  if (this.iwin.getSelection) {
    // Gecko
    const sel = this.iwin.getSelection();
    range = sel.getRangeAt(0);

    // deselect all ranges
    sel.removeAllRanges();

    // empty range
    range.deleteContents();

    // Insert node
    range.insertNode(node);

    range.selectNodeContents(node);
    range.setEndAfter(node);
    if (range.endContainer.childNodes.length > range.endOffset && range.endContainer.nodeType != Node.TEXT_NODE) {
      range.setEnd(range.endContainer.childNodes[range.endOffset], 0);
    } else {
      range.setEnd(range.endContainer.childNodes[0]);
    }
    sel.addRange(range);

    sel.collapseToEnd();
  } else {
    // IE
    // lambda element
    const p = this.iwin.document.createElement('div');
    p.appendChild(node);
    range = this.iwin.document.selection.createRange();
    range.execCommand('delete');
    // insert innerHTML from element
    range.pasteHTML(p.innerHTML);
    range.collapse(false);
    range.select();
  }
  this.iwin.focus();
};

/** Returns a document fragment with selected nodes
 */
jsToolBar.prototype.getSelectedNode = function () {
  let sel;
  let content;
  if (this.iwin.getSelection) {
    // Gecko
    sel = this.iwin.getSelection();
    const range = sel.getRangeAt(0);
    return range.cloneContents();
  }
  // IE
  sel = this.iwin.document.selection;
  const d = this.iwin.document.createElement('div');
  d.innerHTML = sel.createRange().htmlText;
  content = this.iwin.document.createDocumentFragment();
  for (const child of d.childNodes) {
    content.appendChild(child.cloneNode(true));
  }
  return content;
};

/** Returns string representation for selected node
 */
jsToolBar.prototype.getSelectedText = function () {
  if (this.iwin.getSelection) {
    // Gecko
    return this.iwin.getSelection().toString();
  }
  // IE
  const range = this.iwin.document.selection.createRange();
  return range.text;
};

jsToolBar.prototype.replaceNodeByContent = function (node) {
  const content = this.iwin.document.createDocumentFragment();
  for (const child of node.childNodes) {
    content.appendChild(child.cloneNode(true));
  }
  node.parentNode.replaceChild(content, node);
};

jsToolBar.prototype.getBlockLevel = function () {
  const blockElts = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

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
  while (!blockElts.includes(ancestorTagName) && ancestorTagName != 'body') {
    commonAncestorContainer = commonAncestorContainer.parentNode;
    ancestorTagName = commonAncestorContainer.tagName.toLowerCase();
  }
  return ancestorTagName == 'body' ? null : commonAncestorContainer;
};
jsToolBar.prototype.adjustBlockLevelCombo = function () {
  const blockLevel = this.getBlockLevel();
  if (blockLevel === null) {
    if (this.mode == 'wysiwyg') this.toolNodes.blocks.value = 'none';
    if (this.mode == 'xhtml') this.toolNodes.blocks.value = 'nonebis';
  } else this.toolNodes.blocks.value = blockLevel.tagName.toLowerCase();
};

/** HTML code cleanup
-------------------------------------------------------- */
jsToolBar.prototype.simpleCleanRegex = new Array(
  /* Remove every tags we don't need */
  [/<meta[\w\W]*?>/gim, ''],
  [/<style[\w\W]*?>[\w\W]*?<\/style>/gim, ''],
  [/<\/?font[\w\W]*?>/gim, ''],

  /* Replacements */
  [/<(\/?)(B|b|STRONG)([\s>/])/g, '<$1strong$3'],
  [/<(\/?)(I|i|EM)([\s>/])/g, '<$1em$3'],
  [/<IMG ([^>]*?[^/])>/gi, '<img $1 />'],
  [/<INPUT ([^>]*?[^/])>/gi, '<input $1 />'],
  [/<COL ([^>]*?[^/])>/gi, '<col $1 />'],
  [/<AREA ([^>]*?[^/])>/gi, '<area $1 />'],
  [/<PARAM ([^>]*?[^/])>/gi, '<param $1 />'],
  [/<HR ([^>]*?[^/])>/gi, '<hr $1/>'],
  [/<BR ([^>]*?[^/])>/gi, '<br $1/>'],
  [/<(\/?)U([\s>/])/gi, '<$1ins$2'],
  [/<(\/?)STRIKE([\s>/])/gi, '<$1del$2'],
  [/<span style="font-weight: normal;">([\w\W]*?)<\/span>/gm, '$1'],
  [/<span style="font-weight: bold;">([\w\W]*?)<\/span>/gm, '<strong>$1</strong>'],
  [/<span style="font-style: italic;">([\w\W]*?)<\/span>/gm, '<em>$1</em>'],
  [/<span style="text-decoration: underline;">([\w\W]*?)<\/span>/gm, '<ins>$1</ins>'],
  [/<span style="text-decoration: line-through;">([\w\W]*?)<\/span>/gm, '<del>$1</del>'],
  [/<span style="text-decoration: underline line-through;">([\w\W]*?)<\/span>/gm, '<del><ins>$1</ins></del>'],
  [/<span style="(font-weight: bold; ?|font-style: italic; ?){2}">([\w\W]*?)<\/span>/gm, '<strong><em>$2</em></strong>'],
  [
    /<span style="(font-weight: bold; ?|text-decoration: underline; ?){2}">([\w\W]*?)<\/span>/gm,
    '<ins><strong>$2</strong></ins>',
  ],
  [/<span style="(font-weight: italic; ?|text-decoration: underline; ?){2}">([\w\W]*?)<\/span>/gm, '<ins><em>$2</em></ins>'],
  [
    /<span style="(font-weight: bold; ?|text-decoration: line-through; ?){2}">([\w\W]*?)<\/span>/gm,
    '<del><strong>$2</strong></del>',
  ],
  [/<span style="(font-weight: italic; ?|text-decoration: line-through; ?){2}">([\w\W]*?)<\/span>/gm, '<del><em>$2</em></del>'],
  [
    /<span style="(font-weight: bold; ?|font-style: italic; ?|text-decoration: underline; ?){3}">([\w\W]*?)<\/span>/gm,
    '<ins><strong><em>$2</em></strong></ins>',
  ],
  [
    /<span style="(font-weight: bold; ?|font-style: italic; ?|text-decoration: line-through; ?){3}">([\w\W]*?)<\/span>/gm,
    '<del><strong><em>$2</em></strong></del>',
  ],
  [
    /<span style="(font-weight: bold; ?|font-style: italic; ?|text-decoration: underline line-through; ?){3}">([\w\W]*?)<\/span>/gm,
    '<del><ins><strong><em>$2</em></strong></ins></del>',
  ],
  [/<strong style="font-weight: normal;">([\w\W]*?)<\/strong>/gm, '$1'],
  [/<([a-z]+) style="font-weight: normal;">([\w\W]*?)<\/\1>/gm, '<$1>$2</$1>'],
  [/<([a-z]+) style="font-weight: bold;">([\w\W]*?)<\/\1>/gm, '<$1><strong>$2</strong></$1>'],
  [/<([a-z]+) style="font-style: italic;">([\w\W]*?)<\/\1>/gm, '<$1><em>$2</em></$1>'],
  [/<([a-z]+) style="text-decoration: underline;">([\w\W]*?)<\/\1>/gm, '<ins><$1>$2</$1></ins>'],
  [/<([a-z]+) style="text-decoration: line-through;">([\w\W]*?)<\/\1>/gm, '<del><$1>$2</$1></del>'],
  [/<([a-z]+) style="text-decoration: underline line-through;">([\w\W]*?)<\/\1>/gm, '<del><ins><$1>$2</$1></ins></del>'],
  [
    /<([a-z]+) style="(font-weight: bold; ?|font-style: italic; ?){2}">([\w\W]*?)<\/\1>/gm,
    '<$1><strong><em>$3</em></strong></$1>',
  ],
  [
    /<([a-z]+) style="(font-weight: bold; ?|text-decoration: underline; ?){2}">([\w\W]*?)<\/\1>/gm,
    '<ins><$1><strong>$3</strong></$1></ins>',
  ],
  [
    /<([a-z]+) style="(font-weight: italic; ?|text-decoration: underline; ?){2}">([\w\W]*?)<\/\1>/gm,
    '<ins><$1><em>$3</em></$1></ins>',
  ],
  [
    /<([a-z]+) style="(font-weight: bold; ?|text-decoration: line-through; ?){2}">([\w\W]*?)<\/\1>/gm,
    '<del><$1><strong>$3</strong></$1></del>',
  ],
  [
    /<([a-z]+) style="(font-weight: italic; ?|text-decoration: line-through; ?){2}">([\w\W]*?)<\/\1>/gm,
    '<del><$1><em>$3</em></$1></del>',
  ],
  [
    /<([a-z]+) style="(font-weight: bold; ?|font-style: italic; ?|text-decoration: underline; ?){3}">([\w\W]*?)<\/\1>/gm,
    '<ins><$1><strong><em>$3</em></strong></$1></ins>',
  ],
  [
    /<([a-z]+) style="(font-weight: bold; ?|font-style: italic; ?|text-decoration: line-through; ?){3}">([\w\W]*?)<\/\1>/gm,
    '<del><$1><strong><em>$3</em></strong></$1></del>',
  ],
  [
    /<([a-z]+) style="(font-weight: bold; ?|font-style: italic; ?|text-decoration: underline line-through; ?){3}">([\w\W]*?)<\/\1>/gm,
    '<del><ins><$1><strong><em>$3</em></strong></$1></ins></del>',
  ],
  [/<p><blockquote>(.*)(\n)+<\/blockquote><\/p>/i, '<blockquote>$1</blockquote>\n'],
  /* mise en forme identique contigue */
  [/<\/(strong|em|ins|del|q|code)>(\s*?)<\1>/gim, '$2'],
  [/<(br|BR)>/g, '<br>'],
  [/<(hr|HR)>/g, '<hr>'],
  /* br intempestifs de fin de block */
  [/<br \/>\s*<\/(h1|h2|h3|h4|h5|h6|ul|ol|li|p|blockquote|div)/gi, '</$1'],
  [/<\/(h1|h2|h3|h4|h5|h6|ul|ol|li|p|blockquote)>([^\n\u000B\r\f])/gi, '</$1>\n$2'],
  [/<hr style="width: 100%; height: 2px;" \/>/g, '<hr>'],
);

/** Cleanup HTML code
 */
jsToolBar.prototype.tagsoup2xhtml = function (html) {
  for (const reg in this.simpleCleanRegex) {
    html = html.replace(this.simpleCleanRegex[reg][0], this.simpleCleanRegex[reg][1]);
  }
  /* tags vides */
  /* note : on tente de ne pas tenir compte des commentaires html, ceux-ci
     permettent entre autre d'inserer des commentaires conditionnels pour ie */
  while (/(<[^/!]>|<[^/!][^>]*[^/]>)\s*<\/[^>]*[^-]>/.test(html)) {
    html = html.replace(/(<[^/!]>|<[^/!][^>]*[^/]>)\s*<\/[^>]*[^-]>/g, '');
  }

  /* tous les tags en minuscule */
  html = html.replace(/<(\/?)([A-Z0-9]+)/g, (_match0, match1, match2) => `<${match1}${match2.toLowerCase()}`);

  /* IE laisse souvent des attributs sans guillemets */
  const myRegexp = /<[^>]+((\s+\w+\s*=\s*)([^"'][\w~@+$,%/:.#?=&;!*()-]*))[^>]*?>/;
  const myQuoteFn = (str, val1, val2, val3) => {
    const tamponRegex = new RegExp(regexpEscape(val1));
    return str.replace(tamponRegex, `${val2}"${val3}"`);
  };
  while (myRegexp.test(html)) {
    html = html.replace(myRegexp, myQuoteFn);
  }

  /* les navigateurs rajoutent une unite aux longueurs css nulles */
  /* note: a ameliorer ! */
  while (/(<[^>]+style=(["'])[^>]+[\s:]+)0(pt|px)(\2|\s|;)/.test(html)) {
    html = html.replace(/(<[^>]+style=(["'])[^>]+[\s:]+)0(pt|px)(\2|\s|;)/gi, '$10$4');
  }

  /* correction des fins de lignes : le textarea edite contient des \n
   * le wysiwyg des \r\n , et le textarea mis a jour SANS etre affiche des \r\n ! */
  html = html.replace(/\r\n/g, '\n');

  /* Trim only if there's no pre tag */
  const pattern_pre = /<pre>[\s\S]*<\/pre>/gi;
  if (!pattern_pre.test(html)) {
    return html.replace(/^\s+/g, '').replace(/\s+$/g, '');
  }

  return html;
};
jsToolBar.prototype.validBlockquote = function () {
  const blockElts = [
    'address',
    'blockquote',
    'dl',
    'div',
    'fieldset',
    'form',
    'h1',
    'h2',
    'h3',
    'h4',
    'h5',
    'h6',
    'hr',
    'ol',
    'p',
    'pre',
    'table',
    'ul',
  ];
  const BQs = this.iwin.document.getElementsByTagName('blockquote');
  let bqChilds;
  let p;

  for (const bq of BQs) {
    bqChilds = bq.childNodes;
    let frag = this.iwin.document.createDocumentFragment();
    for (let i = bqChilds.length - 1; i >= 0; i--) {
      if (
        bqChilds[i].nodeType == 1 && // Node.ELEMENT_NODE
        blockElts.includes(bqChilds[i].tagName.toLowerCase())
      ) {
        if (frag.childNodes.length > 0) {
          p = this.iwin.document.createElement('p');
          p.appendChild(frag);
          bq.replaceChild(p, bqChilds[i + 1]);
          frag = this.iwin.document.createDocumentFragment();
        }
      } else {
        if (frag.childNodes.length > 0) bq.removeChild(bqChilds[i + 1]);
        frag.insertBefore(bqChilds[i].cloneNode(true), frag.firstChild);
      }
    }
    if (frag.childNodes.length > 0) {
      p = this.iwin.document.createElement('p');
      p.appendChild(frag);
      bq.replaceChild(p, bqChilds[0]);
    }
  }
};

/* Removing text formating */
jsToolBar.prototype.removeFormatRegexp = new Array(
  [/(<[a-z][^>]*)margin\s*:[^;]*;/gm, '$1'],
  [/(<[a-z][^>]*)margin-bottom\s*:[^;]*;/gm, '$1'],
  [/(<[a-z][^>]*)margin-left\s*:[^;]*;/gm, '$1'],
  [/(<[a-z][^>]*)margin-right\s*:[^;]*;/gm, '$1'],
  [/(<[a-z][^>]*)margin-top\s*:[^;]*;/gm, '$1'],

  [/(<[a-z][^>]*)padding\s*:[^;]*;/gm, '$1'],
  [/(<[a-z][^>]*)padding-bottom\s*:[^;]*;/gm, '$1'],
  [/(<[a-z][^>]*)padding-left\s*:[^;]*;/gm, '$1'],
  [/(<[a-z][^>]*)padding-right\s*:[^;]*;/gm, '$1'],
  [/(<[a-z][^>]*)padding-top\s*:[^;]*;/gm, '$1'],

  [/(<[a-z][^>]*)font\s*:[^;]*;/gm, '$1'],
  [/(<[a-z][^>]*)font-family\s*:[^;]*;/gm, '$1'],
  [/(<[a-z][^>]*)font-size\s*:[^;]*;/gm, '$1'],
  [/(<[a-z][^>]*)font-style\s*:[^;]*;/gm, '$1'],
  [/(<[a-z][^>]*)font-variant\s*:[^;]*;/gm, '$1'],
  [/(<[a-z][^>]*)font-weight\s*:[^;]*;/gm, '$1'],

  [/(<[a-z][^>]*)color\s*:[^;]*;/gm, '$1'],
);

jsToolBar.prototype.removeTextFormating = function (html) {
  for (const reg in this.removeFormatRegexp) {
    html = html.replace(this.removeFormatRegexp[reg][0], this.removeFormatRegexp[reg][1]);
  }

  html = this.tagsoup2xhtml(html);
  return html.replace(/style="\s*?"/gim, '');
};

/** Toolbar elements
-------------------------------------------------------- */
jsToolBar.prototype.elements.blocks.wysiwyg = {
  list: ['none', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
  fn(opt) {
    if (opt == 'none') {
      const blockLevel = this.getBlockLevel();
      if (blockLevel !== null) {
        this.replaceNodeByContent(blockLevel);
      }
    } else {
      try {
        this.iwin.document.execCommand('formatblock', false, `<${opt}>`);
      } catch (e) {}
    }
    this.iwin.focus();
  },
};

jsToolBar.prototype.elements.strong.fn.wysiwyg = function () {
  this.iwin.document.execCommand('bold', false, null);
  this.iwin.focus();
};

jsToolBar.prototype.elements.em.fn.wysiwyg = function () {
  this.iwin.document.execCommand('italic', false, null);
  this.iwin.focus();
};

jsToolBar.prototype.elements.ins.fn.wysiwyg = function () {
  this.iwin.document.execCommand('underline', false, null);
  this.iwin.focus();
};

jsToolBar.prototype.elements.del.fn.wysiwyg = function () {
  this.iwin.document.execCommand('strikethrough', false, null);
  this.iwin.focus();
};

jsToolBar.prototype.elements.quote.fn.wysiwyg = function () {
  const n = this.getSelectedNode();
  const q = this.iwin.document.createElement('q');
  q.appendChild(n);
  this.insertNode(q);
};

jsToolBar.prototype.elements.code.fn.wysiwyg = function () {
  const n = this.getSelectedNode();
  const code = this.iwin.document.createElement('code');
  code.appendChild(n);
  this.insertNode(code);
};

jsToolBar.prototype.elements.mark.fn.wysiwyg = function () {
  const n = this.getSelectedNode();
  const mark = this.iwin.document.createElement('mark');
  mark.appendChild(n);
  this.insertNode(mark);
};

jsToolBar.prototype.elements.br.fn.wysiwyg = function () {
  const n = this.iwin.document.createElement('br');
  this.insertNode(n);
};

jsToolBar.prototype.elements.blockquote.fn.wysiwyg = function () {
  const n = this.getSelectedNode();
  const q = this.iwin.document.createElement('blockquote');
  q.appendChild(n);
  this.insertNode(q);
};

jsToolBar.prototype.elements.pre.fn.wysiwyg = function () {
  this.iwin.document.execCommand('formatblock', false, '<pre>');
  this.iwin.focus();
};

jsToolBar.prototype.elements.ul.fn.wysiwyg = function () {
  this.iwin.document.execCommand('insertunorderedlist', false, null);
  this.iwin.focus();
};

jsToolBar.prototype.elements.ol.fn.wysiwyg = function () {
  this.iwin.document.execCommand('insertorderedlist', false, null);
  this.iwin.focus();
};

jsToolBar.prototype.elements.link.fn.wysiwyg = function () {
  let href;
  let hreflang;
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

  // Update or remove link?
  if (ancestorTagName == 'a') {
    href = commonAncestorContainer.href || '';
    hreflang = commonAncestorContainer.hreflang || '';
  }

  href = window.prompt(this.elements.link.href_prompt, href);

  // Remove link
  if (ancestorTagName == 'a' && href == '') {
    this.replaceNodeByContent(commonAncestorContainer);
  }
  if (!href) return; // user cancel

  hreflang = window.prompt(this.elements.link.hreflang_prompt, hreflang);

  // Update link
  if (ancestorTagName == 'a' && href) {
    commonAncestorContainer.setAttribute('href', href);
    if (hreflang) {
      commonAncestorContainer.setAttribute('hreflang', hreflang);
    } else {
      commonAncestorContainer.removeAttribute('hreflang');
    }
    return;
  }

  // Create link
  const n = this.getSelectedNode();
  const a = this.iwin.document.createElement('a');
  a.href = href;
  if (hreflang) a.setAttribute('hreflang', hreflang);
  a.appendChild(n);
  this.insertNode(a);
};

// Remove format and Toggle
jsToolBar.prototype.elements.removeFormat = {
  type: 'button',
  title: 'Remove text formating',
  fn: {},
};
jsToolBar.prototype.elements.removeFormat.disabled = !jsToolBar.prototype.can_wwg;
jsToolBar.prototype.elements.removeFormat.fn.xhtml = function () {
  let html = this.textarea.value;
  html = this.removeTextFormating(html);
  this.textarea.value = html;
};
jsToolBar.prototype.elements.removeFormat.fn.wysiwyg = function () {
  let html = this.iwin.document.body.innerHTML;
  html = this.removeTextFormating(html);
  this.iwin.document.body.innerHTML = html;
};
/** Utilities
-------------------------------------------------------- */
function addEvent(obj, evType, fn, useCapture) {
  if (obj.addEventListener) {
    obj.addEventListener(evType, fn, useCapture);
    return true;
  }
  if (obj.attachEvent) {
    return obj.attachEvent(`on${evType}`, fn);
  }
  return false;
}

function removeEvent(obj, evType, fn, useCapture) {
  if (obj.removeEventListener) {
    obj.removeEventListener(evType, fn, useCapture);
    return true;
  }
  if (obj.detachEvent) {
    return obj.detachEvent(`on${evType}`, fn);
  }
  return false;
}

function regexpEscape(s) {
  return s.replace(/([\\\^$*+[\]?{}.=!:(|)])/g, '\\$1');
}
