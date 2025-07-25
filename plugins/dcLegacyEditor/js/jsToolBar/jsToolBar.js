/*exported jsToolBar */
'use strict';

class jsToolBar {
  constructor(textarea) {
    if (!textarea) {
      return;
    }

    if (typeof document.selection === 'undefined' && typeof textarea.setSelectionRange === 'undefined') {
      return;
    }

    this.textarea = textarea;

    this.editor = document.createElement('div');
    this.editor.className = 'jstEditor';

    this.textarea.parentNode.insertBefore(this.editor, this.textarea);
    this.editor.appendChild(this.textarea);

    this.toolbar = document.createElement('div');
    this.toolbar.className = 'jstElements';

    if (this.toolbar_bottom) {
      this.editor.parentNode.insertBefore(this.toolbar, this.editor.nextSibling);
      this.editor.parentNode.classList.add('toolbar_bottom');
    } else {
      this.editor.parentNode.insertBefore(this.toolbar, this.editor);
      this.editor.parentNode.classList.add('toolbar_top');
    }

    this.context = null;
    this.toolNodes = {}; // lorsque la toolbar est dessinée , cet objet est garni
    // de raccourcis vers les éléments DOM correspondants aux outils.

    // Dynamic height
    if (this.textarea && this?.dynamic_height) {
      // Store initial number of rows
      this.dynamic = {
        min: this.textarea.clientHeight + 2, // Keeping 1 pixel borders in mind
        max: 0, // 0 = use as far as height as possible
        timer: false,
      };
      this.debounceFunction = (func, delay) => {
        if (this.dynamic.timer) {
          clearTimeout(this.dynamic.timer);
        }
        this.dynamic.timer = setTimeout(func, delay);
      };
      this.adjustHeight = (el, min, max) => {
        // Compute the height difference which is caused by border and outline
        const outerHeight = Number.parseInt(window.getComputedStyle(el).height, 10);
        const diff = outerHeight - el.clientHeight;

        // Set the height to 0 in case of it has to be shrinked
        el.style.height = 0;

        let calc_max = max;
        if (calc_max === 0) {
          // No max limit = keep toolbar visible as far as possible
          calc_max = window.innerHeight - 100; // Removing approximative usual height of toolbar
        }

        // Set the correct height
        // el.scrollHeight is the full height of the content, not just the visible part
        el.style.height = `${Math.min(calc_max, Math.max(min, el.scrollHeight + diff))}px`;
      };
      this.textarea.addEventListener('input', () =>
        this.debounceFunction(this.adjustHeight(this.textarea, this.dynamic.min, this.dynamic.max), 300),
      );
      // First call
      this.adjustHeight(this.textarea, this.dynamic.min, this.dynamic.max);
    }
  }

  getMode() {
    return this.mode;
  }

  setMode(mode = 'xhtml') {
    this.mode = mode;
  }

  switchMode(mode = 'xhtml') {
    this.draw(mode);
  }

  button(toolName) {
    const tool = this.elements[toolName];
    if (typeof tool.fn[this.mode] !== 'function') return null;
    const b = new jsButton(
      tool.title,
      tool.fn[this.mode],
      this,
      `jstb_${toolName}`,
      tool.accesskey,
      tool.shortkey,
      tool.shortkey_name,
    );
    if (tool.icon !== undefined) {
      b.icon = tool.icon;
      if (tool.icon_dark !== undefined) {
        b.icon_dark = tool.icon_dark;
      }
    }
    return b;
  }

  space(toolName) {
    const tool = new jsSpace(toolName);
    if (this.elements[toolName].format !== undefined && !this.elements[toolName].format[this.mode]) return null;
    if (this.elements[toolName].width !== undefined) {
      tool.width = this.elements[toolName].width;
    }
    return tool;
  }

  combo(toolName) {
    const tool = this.elements[toolName];

    if (tool[this.mode] === undefined) {
      return;
    }
    const { length } = tool[this.mode].list;

    if (typeof tool[this.mode].fn !== 'function' || length === 0) {
      return null;
    }
    const options = {};
    for (const opt of tool[this.mode].list) {
      options[opt] = tool.options[opt];
    }
    return new jsCombo(tool.title, options, this, tool[this.mode].fn);
  }

  draw(mode) {
    this.setMode(mode);

    // Empty toolbar
    while (this.toolbar.hasChildNodes()) {
      this.toolbar.removeChild(this.toolbar.firstChild);
    }
    this.toolNodes = {}; // vide les raccourcis DOM/**/

    // Draw toolbar elements
    let b;
    let tool;
    let newTool;
    let previous = 'space';

    for (const i in this.elements) {
      b = this.elements[i];

      const disabled =
        b.type === undefined ||
        b.type === '' ||
        (b.disabled !== undefined && b.disabled) ||
        (b.context !== undefined && b.context != null && b.context !== this.context);

      if (!disabled && typeof this[b.type] === 'function') {
        tool = this[b.type](i);
        if (tool) {
          // Don't display first space in toolbar or if another space following a first one
          if (b.type !== 'space' || b.type !== previous) {
            newTool = tool.draw();
            previous = b.type;
          }
        }
        if (newTool) {
          this.toolNodes[i] = newTool; //mémorise l'accès DOM pour usage éventuel ultérieur
          this.toolbar.appendChild(newTool);
        }
      }
    }
  }

  singleTag(stag = null, etag = stag) {
    if (!stag || !etag) {
      return;
    }

    this.encloseSelection(stag, etag);
  }

  encloseSelection(prefix = '', suffix = '', fn = null) {
    this.textarea.focus();

    let start;
    let end;
    let sel;
    let scrollPos;
    let subst;
    let res;

    if (typeof document.selection !== 'undefined') {
      sel = document.selection.createRange().text;
    } else if (typeof this.textarea.setSelectionRange !== 'undefined') {
      start = this.textarea.selectionStart;
      end = this.textarea.selectionEnd;
      scrollPos = this.textarea.scrollTop;
      sel = this.textarea.value.substring(start, end);
    }

    let clean_suffix = suffix;
    if (sel.match(/ $/)) {
      // exclude ending space char, if any
      sel = sel.substring(0, sel.length - 1);
      clean_suffix = `${clean_suffix} `;
    }

    if (typeof fn === 'function') {
      res = sel ? fn.call(this, sel) : fn('');
    } else {
      res = sel || '';
    }

    subst = prefix + res + clean_suffix;

    if (typeof document.selection !== 'undefined') {
      document.selection.createRange().text = subst;
      this.textarea.caretPos -= suffix.length;
    } else if (typeof this.textarea.setSelectionRange !== 'undefined') {
      this.textarea.value = this.textarea.value.substring(0, start) + subst + this.textarea.value.substring(end);
      if (sel || typeof fn === 'function') {
        this.textarea.setSelectionRange(start + subst.length, start + subst.length);
      } else if (typeof fn !== 'function') {
        this.textarea.setSelectionRange(start + prefix.length, start + prefix.length);
      }
      this.textarea.scrollTop = scrollPos;
    }
  }

  stripBaseURL(url) {
    if (this.base_url !== '') {
      const pos = url.indexOf(this.base_url);
      if (pos === 0) {
        return url.substring(this.base_url.length);
      }
    }

    return url;
  }
}

// Set default properties
jsToolBar.prototype.base_url = '';
jsToolBar.prototype.mode = 'xhtml';
jsToolBar.prototype.elements = {};
jsToolBar.prototype.toolbar_bottom = false;

// jsButton
class jsButton {
  constructor(title, fn, scope, className, accesskey, shortkey, shortkey_name) {
    this.title = title || null;
    this.fn =
      fn ||
      (() => {
        /* void */
      });
    this.scope = scope || null;
    this.className = className || null;
    this.accesskey = accesskey || null;
    this.shortkey = shortkey || null; // See https://www.w3.org/TR/uievents-code/#table-key-code-alphanumeric-writing-system
    this.shortkey_name = shortkey_name || null;
  }

  draw() {
    if (!this.scope) return null;

    const button = document.createElement('button');
    button.setAttribute('type', 'button');
    if (this.className) button.className = this.className;
    button.title = this.title;
    if (this.accesskey) button.accessKey = this.accesskey;
    if (this.shortkey) {
      if (this.shortkey_name) button.title += ` (CTRL+${this.shortkey_name})`;
      this.scope.textarea.addEventListener('keydown', (event) => {
        if (!(event.code === this.shortkey && event.ctrlKey && !event.altKey && !event.metaKey)) {
          return;
        }
        // Fire click
        button.click();
        event.preventDefault();
        return false;
      });
    }
    // Icons (light or light/dark)
    if (this.icon !== undefined) {
      const img_light = document.createElement('img');
      img_light.setAttribute('src', this.icon);
      img_light.setAttribute('alt', this.title);
      if (this.icon_dark === undefined) {
        // Add contrast
        button.appendChild(img_light);
      } else {
        img_light.classList.add('light-only');
        button.appendChild(img_light);
        const img_dark = document.createElement('img');
        img_dark.setAttribute('src', this.icon_dark);
        img_dark.setAttribute('alt', this.title);
        img_dark.classList.add('dark-only');
        button.appendChild(img_dark);
      }
    }
    // Title
    const span = document.createElement('span');
    span.appendChild(document.createTextNode(this.title));
    button.appendChild(span);

    if (typeof this.fn === 'function') {
      button.onclick = (...args) => {
        try {
          this.fn.apply(this.scope, args);
        } catch (e) {}
        return false;
      };
    }
    return button;
  }
}

// jsSpace
class jsSpace {
  constructor(id) {
    this.id = id || null;
    this.width = null;
  }

  draw() {
    const span = document.createElement('span');
    if (this.id) span.id = this.id;
    span.appendChild(document.createTextNode(String.fromCharCode(160)));
    span.className = 'jstSpacer';
    if (this.width) span.style.marginRight = `${this.width}px`;

    return span;
  }
}

//jsCombo
class jsCombo {
  constructor(title, options, scope, fn, className) {
    this.title = title || null;
    this.options = options || null;
    this.scope = scope || null;
    this.fn =
      fn ||
      (() => {
        /* void */
      });
    this.className = className || null;
  }

  draw() {
    if (!this.scope || !this.options) return null;

    const select = document.createElement('select');
    if (this.className) select.className = this.className;
    select.title = this.title;

    for (const o in this.options) {
      const option = document.createElement('option');
      option.value = o;
      option.appendChild(document.createTextNode(this.options[o]));
      select.appendChild(option);
    }

    const This = this;
    select.onchange = function () {
      try {
        This.fn.call(This.scope, this.value);
      } catch (e) {
        window.alert(e);
      }

      return false;
    };

    return select;
  }
}

// jsDialog
class jsDialog {
  title;
  confirm_label;
  cancel_label;
  fields;
  constructor({ title, confirm_label, cancel_label, fields } = {}) {
    this.title = title;
    this.confirm_label = confirm_label ?? 'Ok';
    this.cancel_label = cancel_label ?? 'Cancel';
    this.fields = fields;
  }
  prompt() {
    // 0. Check
    if (!this.fields?.length) return Promise.resolve(null);

    // 1. Create dialog HTML
    const template = document.createElement('template');
    const fields_html = this.fields.map((field) => `<p class="field">${field.html}</p>`).join('');
    const html = (strings, ...values) =>
      strings.reduce(
        (accumulator, currentValue, currentIndex) => accumulator + currentValue + (values[currentIndex] ?? ''),
        '',
      );
    const title = this.title ? `<h1>${this.title}</h1>` : '';
    template.innerHTML = html`
      <dialog class="jstDialog">
        ${title}
        <form method="dialog">
          ${fields_html}
          <p class="form-buttons">
            <button name="cancel" class="reset">${this.cancel_label}</button>
            <button type="submit" name="confirm" class="submit">${this.confirm_label}</button>
          </p>
        </form>
      </dialog>
    `;
    const dialog = template.content.firstElementChild;
    const fields = dialog.querySelectorAll('.field input, .field select');
    let index = 0;
    for (const field of fields) {
      if (this.fields[index]?.default) field.value = this.fields[index].default;
      index++;
    }

    // 2. Add dialog to body
    document.body.appendChild(dialog);

    const getReturnValue = () => JSON.stringify([...fields].map((field) => field.value));

    return new Promise((resolve) => {
      // 3. Add event listener to cope with dialog
      for (const field of fields) {
        field.addEventListener('keydown', (event) => {
          if (event.key !== 'Enter') {
            return;
          }
          event.preventDefault();
          dialog.returnValue = getReturnValue();
          dialog.close();
        });
      }

      // Cope with confirm button
      dialog.querySelector('button[name="confirm"]')?.addEventListener('click', (event) => {
        event.preventDefault();
        dialog.returnValue = getReturnValue();
        dialog.close();
      });

      // Cope with cancel button
      dialog.querySelector('button[name="cancel"]')?.addEventListener('click', () => {
        dialog.dispatchEvent(new Event('cancel'));
      });

      // Cope with dialog cancel event
      dialog.addEventListener('cancel', function onCancel(event) {
        event.preventDefault();
        dialog.removeEventListener('close', onCancel);
        dialog.returnValue = null;
        dialog.remove();
        resolve(null);
      });

      // Cope with dialog close event
      dialog.addEventListener('close', function onClose(event) {
        event.preventDefault();
        dialog.removeEventListener('close', onClose);
        const result = dialog.returnValue;
        dialog.remove();
        resolve(result);
      });

      // 4. Display dialog and give focus
      dialog.showModal();
      fields[0].focus();
    });
  }
}

// Elements definition ------------------------------------
// block format (paragraph, headers)
jsToolBar.prototype.elements.blocks = {
  type: 'combo',
  title: 'block format',
  options: {
    none: '-- none --', // only for wysiwyg mode
    nonebis: '- block format -', // only for xhtml mode
    p: 'Paragraph',
    h1: 'Header 1',
    h2: 'Header 2',
    h3: 'Header 3',
    h4: 'Header 4',
    h5: 'Header 5',
    h6: 'Header 6',
  },
  xhtml: {
    list: ['nonebis', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
    fn(opt) {
      if (opt === 'nonebis') this.textarea.focus();
      else
        try {
          this.singleTag(`<${opt}>`, `</${opt}>`);
        } catch (e) {}
      this.toolNodes.blocks.value = 'nonebis';
    },
  },
  wiki: {
    list: ['nonebis', 'h3', 'h4', 'h5'],
    fn(opt) {
      switch (opt) {
        case 'nonebis':
          this.textarea.focus();
          break;
        case 'h3':
          this.encloseSelection('!!!');
          break;
        case 'h4':
          this.encloseSelection('!!');
          break;
        case 'h5':
          this.encloseSelection('!');
          break;
      }
      this.toolNodes.blocks.value = 'nonebis';
    },
  },
};

// spacer
jsToolBar.prototype.elements.space0 = {
  type: 'space',
  format: {
    wysiwyg: true,
    wiki: true,
    xhtml: true,
  },
};

// strong
jsToolBar.prototype.elements.strong = {
  type: 'button',
  title: 'Strong emphasis',
  shortkey: 'KeyB',
  shortkey_name: 'B',
  fn: {
    wiki() {
      this.singleTag('__');
    },
    xhtml() {
      this.singleTag('<strong>', '</strong>');
    },
  },
};

// em
jsToolBar.prototype.elements.em = {
  type: 'button',
  title: 'Emphasis',
  shortkey: 'KeyI',
  shortkey_name: 'I',
  fn: {
    wiki() {
      this.singleTag("''");
    },
    xhtml() {
      this.singleTag('<em>', '</em>');
    },
  },
};

// ins
jsToolBar.prototype.elements.ins = {
  type: 'button',
  title: 'Inserted',
  shortkey: 'KeyU',
  shortkey_name: 'U',
  fn: {
    wiki() {
      this.singleTag('++');
    },
    xhtml() {
      this.singleTag('<ins>', '</ins>');
    },
  },
};

// del
jsToolBar.prototype.elements.del = {
  type: 'button',
  title: 'Deleted',
  shortkey: 'KeyD',
  shortkey_name: 'D',
  fn: {
    wiki() {
      this.singleTag('--');
    },
    xhtml() {
      this.singleTag('<del>', '</del>');
    },
  },
};

// quote
jsToolBar.prototype.elements.quote = {
  type: 'button',
  title: 'Inline quote',
  fn: {
    async wiki() {
      await this.elements.quote.prompt.call(this, (response) => {
        let end_tag = '';
        if (response.lang) {
          end_tag = `${end_tag}|${response.lang}`;
        }
        if (response.cite) {
          if (!response.lang) {
            end_tag = `${end_tag}|`;
          }
          end_tag = `${end_tag}|${response.cite}`;
        }
        end_tag = `${end_tag}}}`;
        this.encloseSelection('{{', end_tag);
      });
    },
    async xhtml() {
      await this.elements.quote.prompt.call(this, (response) => {
        let start_tag = '<q';
        if (response.cite) {
          start_tag = `${start_tag} cite="${response.cite}"`;
        }
        if (response.lang) {
          start_tag = `${start_tag} lang="${response.lang}"`;
        }
        start_tag = `${start_tag}>`;

        this.encloseSelection(start_tag, '</q>');
      });
    },
  },
  async prompt(callback = null) {
    const dialog = new jsDialog({
      title: this.toolbar.querySelector('.jstb_quote')?.title || this.elements.quote.title,
      confirm_label: this.cite_dialog.ok,
      cancel_label: this.cite_dialog.cancel,
      fields: [
        {
          // Quote URL input
          default: this.cite_dialog.fields.default_url,
          html: this.cite_dialog.fields.url,
        },
        {
          // Language select
          default: this.cite_dialog.fields.default_lang,
          html: this.cite_dialog.fields.language,
        },
      ],
    });
    await dialog.prompt().then((choice) => {
      if (choice && callback) {
        const response = JSON.parse(choice);
        callback({
          cite: this.stripBaseURL(response[0]),
          lang: response[1],
        });

        return;
      }
      this.toolbar.querySelector('.jstb_quote').focus();
    });
  },
};

// code
jsToolBar.prototype.elements.code = {
  type: 'button',
  title: 'Code',
  fn: {
    wiki() {
      this.singleTag('@@');
    },
    xhtml() {
      this.singleTag('<code>', '</code>');
    },
  },
};

// code
jsToolBar.prototype.elements.mark = {
  type: 'button',
  title: 'Mark',
  fn: {
    wiki() {
      this.singleTag('""');
    },
    xhtml() {
      this.singleTag('<mark>', '</mark>');
    },
  },
};

// spacer
jsToolBar.prototype.elements.space1 = {
  type: 'space',
  format: {
    wysiwyg: true,
    wiki: true,
    xhtml: true,
  },
};

// br
jsToolBar.prototype.elements.br = {
  type: 'button',
  title: 'Line break',
  fn: {
    wiki() {
      this.encloseSelection('%%%\n', '');
    },
    xhtml() {
      this.encloseSelection('<br>\n', '');
    },
  },
};

// spacer
jsToolBar.prototype.elements.space2 = {
  type: 'space',
  format: {
    wysiwyg: true,
    wiki: true,
    xhtml: true,
  },
};

// blockquote
jsToolBar.prototype.elements.blockquote = {
  type: 'button',
  title: 'Blockquote',
  fn: {
    xhtml() {
      this.singleTag('<blockquote>', '</blockquote>');
    },
    wiki() {
      this.encloseSelection('\n', '', (str) => `> ${str.replace(/\r/g, '').replace(/\n/g, '\n> ')}`);
    },
  },
};

// pre
jsToolBar.prototype.elements.pre = {
  type: 'button',
  title: 'Preformated text',
  fn: {
    wiki() {
      this.singleTag('///\n', '\n///');
    },
    xhtml() {
      this.singleTag('<pre>', '</pre>');
    },
  },
};

// ul
jsToolBar.prototype.elements.ul = {
  type: 'button',
  title: 'Unordered list',
  fn: {
    wiki() {
      this.encloseSelection('', '', (str) => `* ${str.replace(/\r/g, '').replace(/\n/g, '\n* ')}`);
    },
    xhtml() {
      this.encloseSelection('', '', (str) => `<ul>\n <li>${str.replace(/\r/g, '').replace(/\n/g, '</li>\n <li>')}</li>\n</ul>`);
    },
  },
};

// ol
jsToolBar.prototype.elements.ol = {
  type: 'button',
  title: 'Ordered list',
  fn: {
    wiki() {
      this.encloseSelection('', '', (str) => `# ${str.replace(/\r/g, '').replace(/\n/g, '\n# ')}`);
    },
    xhtml() {
      this.encloseSelection('', '', (str) => `<ol>\n <li>${str.replace(/\r/g, '').replace(/\n/g, '</li>\n <li>')}</li>\n</ol>`);
    },
  },
};

// spacer
jsToolBar.prototype.elements.space3 = {
  type: 'space',
  format: {
    wysiwyg: true,
    wiki: true,
    xhtml: true,
  },
};

// link
jsToolBar.prototype.elements.link = {
  type: 'button',
  title: 'Link',
  fn: {},
  accesskey: 'l',
  shortkey: 'KeyL',
  shortkey_name: 'L',
  href_prompt: 'Please give page URL:',
  hreflang_prompt: 'Language of this page:',
  default_hreflang: '',
  prompt(href = '', hreflang = '') {
    let language = hreflang || this.elements.link.default_hreflang;

    const url = window.prompt(this.elements.link.href_prompt, href);
    if (!url) {
      return false;
    }

    language = window.prompt(this.elements.link.hreflang_prompt, language);

    return {
      href: this.stripBaseURL(url),
      hreflang: language,
    };
  },
};

jsToolBar.prototype.elements.link.fn.xhtml = function () {
  const link = this.elements.link.prompt.call(this);
  if (!link) {
    return;
  }
  let stag = `<a href="${link.href}"`;
  if (link.hreflang) {
    stag = `${stag} hreflang="${link.hreflang}"`;
  }
  stag = `${stag}>`;
  const etag = '</a>';

  this.encloseSelection(stag, etag);
};
jsToolBar.prototype.elements.link.fn.wiki = function () {
  const link = this.elements.link.prompt.call(this);
  if (!link) {
    return;
  }
  const stag = '[';
  let etag = `|${link.href}`;
  if (link.hreflang) {
    etag = `${etag}|${link.hreflang}`;
  }
  etag = `${etag}]`;

  this.encloseSelection(stag, etag);
};

// img
jsToolBar.prototype.elements.img = {
  type: 'button',
  title: 'External image',
  src_prompt: 'Please give image URL:',
  fn: {},
  prompt(src = '') {
    return this.stripBaseURL(window.prompt(this.elements.img.src_prompt, src));
  },
};
jsToolBar.prototype.elements.img.fn.xhtml = function () {
  const src = this.elements.img.prompt.call(this);
  if (src) {
    this.encloseSelection('', '', (str) => (str ? `<img src="${src}" alt="${str}">` : `<img src="${src}" alt="">`));
  }
};
jsToolBar.prototype.elements.img.fn.wiki = function () {
  const src = this.elements.img.prompt.call(this);
  if (src) {
    this.encloseSelection('', '', (str) => (str ? `((${src}|${str}))` : `((${src}))`));
  }
};

// spacer
jsToolBar.prototype.elements.space4 = {
  type: 'space',
  format: {
    wysiwyg: false,
    wiki: true,
    xhtml: false,
  },
};

// Preview
jsToolBar.prototype.elements.preview = {
  type: 'button',
  title: 'Preview',
  shortkey: 'KeyP',
  shortkey_name: 'P',
  format: {
    wysiwyg: false,
    wiki: true,
    xhtml: false,
  },
  fn: {
    wiki() {
      dotclear.jsonServicesPost(
        'wikiConvert',
        (data) => {
          if (data.ret && data.msg) {
            const src = `<div class="wiki_preview"><div class="wiki_markup">${data.msg}</div></div>`;
            $.magnificPopup.open({
              items: {
                src,
                type: 'inline',
              },
            });
          }
        },
        { wiki: this.textarea.value },
      );
    },
  },
};

// spacer
jsToolBar.prototype.elements.space5 = {
  type: 'space',
  format: {
    wysiwyg: false,
    wiki: true,
    xhtml: false,
  },
};
