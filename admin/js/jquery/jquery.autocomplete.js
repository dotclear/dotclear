/*global $ */
'use strict';

/*
 * jQuery Autocomplete plugin 1.2.4 - adapted to jQuery 3+ (strict mode)
 *
 * Copyright (c) 2009 Jörn Zaefferer
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
 * With small modifications by Alfonso Gómez-Arzola.
 * See changelog for details.
 *
 */

(() => {
  $.fn.extend({
    autocomplete(urlOrData, options) {
      const isUrl = typeof urlOrData == 'string';
      options = $.extend(
        {},
        $.Autocompleter.defaults,
        {
          url: isUrl ? urlOrData : null,
          data: isUrl ? null : urlOrData,
          delay: isUrl ? $.Autocompleter.defaults.delay : 10,
          max: options && !options.scroll ? 10 : 150,
          noRecord: 'No Records.',
        },
        options,
      );

      // if highlight is set to false, replace it with a do-nothing function
      options.highlight = options.highlight || ((value) => value);

      // if the formatMatch option is not specified, then use formatItem for backwards compatibility
      options.formatMatch = options.formatMatch || options.formatItem;

      return this.each(function () {
        new $.Autocompleter(this, options);
      });
    },
    result(handler) {
      return this.bind('result', handler);
    },
    search(handler) {
      return this.trigger('search', [handler]);
    },
    flushCache() {
      return this.trigger('flushCache');
    },
    setOptions(options) {
      return this.trigger('setOptions', [options]);
    },
    unautocomplete() {
      return this.trigger('unautocomplete');
    },
  });

  $.Autocompleter = function (input, options) {
    const KEY = {
      UP: 38,
      DOWN: 40,
      DEL: 46,
      TAB: 9,
      RETURN: 13,
      ESC: 27,
      COMMA: 188,
      PAGEUP: 33,
      PAGEDOWN: 34,
      BACKSPACE: 8,
    };

    let globalFailure = null;
    if (options.failure != null && typeof options.failure == 'function') {
      globalFailure = options.failure;
    }

    // Create $ object for input element
    const $input = $(input).attr('autocomplete', 'off').addClass(options.inputClass);

    let timeout;
    let previousValue = '';
    const cache = $.Autocompleter.Cache(options);
    let hasFocus = 0;
    let lastKeyPressCode;
    const config = {
      mouseDownOnSelect: false,
    };
    const select = $.Autocompleter.Select(options, input, selectCurrent, config);

    let blockSubmit;

    // older versions of opera don't trigger keydown multiple times while pressed, others don't work with keypress at all
    $input
      .on(
        `${navigator.userAgent.includes('Opera') && !('KeyboardEvent' in window) ? 'keypress' : 'keydown'}.autocomplete`,
        (event) => {
          // a keypress means the input has focus
          // avoids issue where input had focus before the autocomplete was applied
          hasFocus = 1;
          // track last key pressed
          lastKeyPressCode = event.keyCode;
          switch (event.keyCode) {
            case KEY.UP:
              if (select.visible()) {
                event.preventDefault();
                select.prev();
              } else {
                onChange(0, true);
              }
              break;

            case KEY.DOWN:
              if (select.visible()) {
                event.preventDefault();
                select.next();
              } else {
                onChange(0, true);
              }
              break;

            case KEY.PAGEUP:
              if (select.visible()) {
                event.preventDefault();
                select.pageUp();
              } else {
                onChange(0, true);
              }
              break;

            case KEY.PAGEDOWN:
              if (select.visible()) {
                event.preventDefault();
                select.pageDown();
              } else {
                onChange(0, true);
              }
              break;

            // matches also semicolon
            case options.multiple && options.multipleSeparator.trim() == ',' && KEY.COMMA:
            case KEY.TAB:
            case KEY.RETURN:
              if (selectCurrent()) {
                // stop default to prevent a form submit, Opera needs special handling
                event.preventDefault();
                blockSubmit = true;
                return false;
              }
              break;

            case KEY.ESC:
              select.hide();
              break;

            default:
              clearTimeout(timeout);
              timeout = setTimeout(onChange, options.delay);
              break;
          }
        },
      )
      .on('focus', () => {
        // track whether the field has focus, we shouldn't process any
        // results if the field no longer has focus
        hasFocus++;
      })
      .on('blur', () => {
        hasFocus = 0;
        if (!config.mouseDownOnSelect) {
          hideResults();
        }
      })
      .on('click', () => {
        // show select when clicking in a focused field
        // but if clickFire is true, don't require field
        // to be focused to begin with; just show select
        if (options.clickFire) {
          if (!select.visible()) {
            onChange(0, true);
          }
        } else if (hasFocus++ > 1 && !select.visible()) {
          onChange(0, true);
        }
      })
      .on('search', function () {
        // TODO why not just specifying both arguments?
        const fn = arguments.length > 1 ? arguments[1] : null;

        function findValueCallback(q, data) {
          let result;
          if (data?.length) {
            for (const elt of data) {
              if (elt.result.toLowerCase() == q.toLowerCase()) {
                result = elt;
                break;
              }
            }
          }
          if (typeof fn == 'function') fn(result);
          else $input.trigger('result', result && [result.data, result.value]);
        }
        $.each(trimWords($input.val()), (i, value) => {
          request(value, findValueCallback, findValueCallback);
        });
      })
      .on('flushCache', () => {
        cache.flush();
      })
      .on('setOptions', function () {
        $.extend(true, options, arguments[1]);
        // if we've updated the data, repopulate
        if ('data' in arguments[1]) cache.populate();
      })
      .on('unautocomplete', () => {
        select.off();
        $input.off();
        $(input.form).off('.autocomplete');
      });

    function selectCurrent() {
      const selected = select.selected();
      if (!selected) return false;

      let v = selected.result;
      previousValue = v;

      if (options.multiple) {
        const words = trimWords($input.val());
        if (words.length > 1) {
          const seperator = options.multipleSeparator.length;
          const cursorAt = $(input).selection().start;
          let wordAt;
          let progress = 0;
          $.each(words, (i, word) => {
            progress += word.length;
            if (cursorAt <= progress) {
              wordAt = i;
              return false;
            }
            progress += seperator;
          });
          words[wordAt] = v;
          // TODO this should set the cursor to the right position, but it gets overriden somewhere
          //$.Autocompleter.Selection(input, progress + seperator, progress + seperator);
          v = words.join(options.multipleSeparator);
        }
        v += options.multipleSeparator;
      }

      $input.val(v);
      hideResultsNow();
      $input.trigger('result', [selected.data, selected.value]);
      return true;
    }

    function onChange(crap, skipPrevCheck) {
      if (lastKeyPressCode == KEY.DEL) {
        select.hide();
        return;
      }

      let currentValue = $input.val();

      if (!skipPrevCheck && currentValue == previousValue) return;

      previousValue = currentValue;

      currentValue = lastWord(currentValue);
      if (currentValue.length >= options.minChars) {
        $input.addClass(options.loadingClass);
        if (!options.matchCase) currentValue = currentValue.toLowerCase();
        request(currentValue, receiveData, hideResultsNow);
      } else {
        stopLoading();
        select.hide();
      }
    }

    function trimWords(value) {
      if (!value) return [''];
      if (!options.multiple) return [value.trim()];
      return $.map(value.split(options.multipleSeparator), (word) => (value.trim().length ? word.trim() : null));
    }

    function lastWord(value) {
      if (!options.multiple) return value;
      let words = trimWords(value);
      if (words.length == 1) return words[0];
      const cursorAt = $(input).selection().start;
      words = cursorAt == value.length ? trimWords(value) : trimWords(value.replace(value.substring(cursorAt), ''));
      return words[words.length - 1];
    }

    // fills in the input box w/the first match (assumed to be the best match)
    // q: the term entered
    // sValue: the first matching result
    function autoFill(q, sValue) {
      // autofill in the complete box w/the first match as long as the user hasn't entered in more data
      // if the last user key pressed was backspace, don't autofill
      if (options.autoFill && lastWord($input.val()).toLowerCase() == q.toLowerCase() && lastKeyPressCode != KEY.BACKSPACE) {
        // fill in the value (keep the case the user has typed)
        $input.val($input.val() + sValue.substring(lastWord(previousValue).length));
        // select the portion of the value not typed by the user (so the next character will erase)
        $(input).selection(previousValue.length, previousValue.length + sValue.length);
      }
    }

    function hideResults() {
      clearTimeout(timeout);
      timeout = setTimeout(hideResultsNow, 200);
    }

    function hideResultsNow() {
      select.hide();
      clearTimeout(timeout);
      stopLoading();
      if (options.mustMatch) {
        // call search and run callback
        $input.search((result) => {
          // if no value found, clear the input box
          if (!result) {
            if (options.multiple) {
              const words = trimWords($input.val()).slice(0, -1);
              $input.val(words.join(options.multipleSeparator) + (words.length ? options.multipleSeparator : ''));
            } else {
              $input.val('');
              $input.trigger('result', null);
            }
          }
        });
      }
    }

    function receiveData(q, data) {
      if (data?.length && hasFocus) {
        stopLoading();
        select.display(data, q);
        autoFill(q, data[0].value);
        select.show();
      } else {
        hideResultsNow();
      }
    }

    function request(term, success, failure) {
      if (!options.matchCase) term = term.toLowerCase();
      const data = cache.load(term);
      // recieve the cached data
      if (data) {
        if (data.length) {
          success(term, data);
        } else {
          const parsed = options.parse?.(options.noRecord) || parse(options.noRecord);
          success(term, parsed);
        }
        // if an AJAX url has been supplied, try loading the data now
      } else if (typeof options.url == 'string' && options.url.length > 0) {
        const extraParams = {
          timestamp: +new Date(),
        };
        $.each(options.extraParams, (key, param) => {
          extraParams[key] = typeof param == 'function' ? param() : param;
        });

        $.ajax({
          // try to leverage ajaxQueue plugin to abort previous requests
          mode: 'abort',
          // limit abortion to this input
          port: `autocomplete${input.name}`,
          dataType: options.dataType,
          url: options.url,
          data: $.extend(
            {
              q: lastWord(term),
              limit: options.max,
            },
            extraParams,
          ),
          success(data) {
            const parsed = options.parse?.(data) || parse(data);
            cache.add(term, parsed);
            success(term, parsed);
          },
        });
      } else {
        // if we have a failure, we need to empty the list -- this prevents the the [TAB] key from selecting the last successful match
        select.emptyList();
        if (globalFailure != null) {
          globalFailure();
        } else {
          failure(term);
        }
      }
    }

    function parse(data) {
      const parsed = [];
      const rows = data.split('\n');
      for (const elt of rows) {
        var row = elt.trim();
        if (row) {
          row = row.split('|');
          parsed[parsed.length] = {
            data: row,
            value: row[0],
            result: options.formatResult?.(row, row[0]) || row[0],
          };
        }
      }
      return parsed;
    }

    function stopLoading() {
      $input.removeClass(options.loadingClass);
    }
  };

  $.Autocompleter.defaults = {
    inputClass: 'ac_input',
    resultsClass: 'ac_results',
    loadingClass: 'ac_loading',
    minChars: 1,
    delay: 400,
    matchCase: false,
    matchSubset: true,
    matchContains: false,
    cacheLength: 100,
    max: 1000,
    mustMatch: false,
    extraParams: {},
    selectFirst: true,
    formatItem(row) {
      return row[0];
    },
    formatMatch: null,
    autoFill: false,
    width: 0,
    multiple: false,
    multipleSeparator: ' ',
    inputFocus: true,
    clickFire: false,
    highlight(value, term) {
      return value.replace(
        new RegExp(
          `(?![^&;]+;)(?!<[^<>]*)(${term.replace(/([\^\$\(\)\[\]\{\}\*\.\+\?\|\\])/gi, '\\$1')})(?![^<>]*>)(?![^&;]+;)`,
          'gi',
        ),
        '<strong>$1</strong>',
      );
    },
    scroll: true,
    scrollHeight: 180,
    scrollJumpPosition: true,
  };

  $.Autocompleter.Cache = (options) => {
    let data = {};
    let length = 0;

    function matchSubset(s, sub) {
      if (!options.matchCase) s = s.toLowerCase();
      let i = s.indexOf(sub);
      if (options.matchContains == 'word') {
        i = s.toLowerCase().search(`\\b${sub.toLowerCase()}`);
      }
      if (i == -1) return false;
      return i == 0 || options.matchContains;
    }

    function add(q, value) {
      if (length > options.cacheLength) {
        flush();
      }
      if (!data[q]) {
        length++;
      }
      data[q] = value;
    }

    function populate() {
      if (!options.data) return false;
      // track the matches
      const stMatchSets = {};
      let nullData = 0;

      // no url was specified, we need to adjust the cache length to make sure it fits the local data store
      if (!options.url) options.cacheLength = 1;

      // track all options for minChars = 0
      stMatchSets[''] = [];

      // loop through the array and create a lookup structure
      for (let i = 0, ol = options.data.length; i < ol; i++) {
        let rawValue = options.data[i];
        // if rawValue is a string, make an array otherwise just reference the array
        rawValue = typeof rawValue == 'string' ? [rawValue] : rawValue;

        const value = options.formatMatch(rawValue, i + 1, options.data.length);
        if (typeof value === 'undefined' || value === false) continue;

        const firstChar = value.charAt(0).toLowerCase();
        // if no lookup array for this character exists, look it up now
        if (!stMatchSets[firstChar]) stMatchSets[firstChar] = [];

        // if the match is a string
        const row = {
          value,
          data: rawValue,
          result: options.formatResult?.(rawValue) || value,
        };

        // push the current match into the set list
        stMatchSets[firstChar].push(row);

        // keep track of minChars zero items
        if (nullData++ < options.max) {
          stMatchSets[''].push(row);
        }
      }

      // add the data items to the cache
      $.each(stMatchSets, (i, value) => {
        // increase the cache size
        options.cacheLength++;
        // add to the cache
        add(i, value);
      });
    }

    // populate any existing data
    setTimeout(populate, 25);

    function flush() {
      data = {};
      length = 0;
    }

    return {
      flush,
      add,
      populate,
      load(q) {
        if (!options.cacheLength || !length) return null;
        /*
         * if dealing w/local data and matchContains than we must make sure
         * to loop through all the data collections looking for matches
         */
        if (!options.url && options.matchContains) {
          // track all matches
          const csub = [];
          // loop through all the data grids for matches
          for (let k in data) {
            // don't search through the stMatchSets[""] (minChars: 0) cache
            // this prevents duplicates
            if (k.length > 0) {
              const c = data[k];
              $.each(c, (i, x) => {
                // if we've got a match, add it to the array
                if (matchSubset(x.value, q)) {
                  csub.push(x);
                }
              });
            }
          }
          return csub;
        }
        // if the exact item exists, use it
        else if (data[q]) {
          return data[q];
        } else if (options.matchSubset) {
          for (let i = q.length - 1; i >= options.minChars; i--) {
            const c = data[q.substr(0, i)];
            if (c) {
              const csub = [];
              $.each(c, (i, x) => {
                if (matchSubset(x.value, q)) {
                  csub[csub.length] = x;
                }
              });
              return csub;
            }
          }
        }
        return null;
      },
    };
  };

  $.Autocompleter.Select = (options, input, select, config) => {
    const CLASSES = {
      ACTIVE: 'ac_over',
    };

    let listItems;
    let active = -1;
    let data;
    let term = '';
    let needsInit = true;
    let element;
    let list;

    // Create results
    function init() {
      if (!needsInit) return;
      element = $('<div/>')
        .hide()
        .addClass(options.resultsClass)
        .css('position', 'absolute')
        .appendTo(document.body)
        .on('hover', function () {
          // Browsers except FF do not fire mouseup event on scrollbars, resulting in mouseDownOnSelect remaining true, and results list not always hiding.
          if ($(this).is(':visible')) {
            input.focus();
          }
          config.mouseDownOnSelect = false;
        });

      list = $('<ul/>')
        .appendTo(element)
        .on('mouseover', (event) => {
          if (target(event).nodeName && target(event).nodeName.toUpperCase() == 'LI') {
            active = $('li', list).removeClass(CLASSES.ACTIVE).index(target(event));
            $(target(event)).addClass(CLASSES.ACTIVE);
          }
        })
        .on('click', (event) => {
          $(target(event)).addClass(CLASSES.ACTIVE);
          select();
          if (options.inputFocus) input.focus();
          return false;
        })
        .on('mousedown', () => {
          config.mouseDownOnSelect = true;
        })
        .on('mouseup', () => {
          config.mouseDownOnSelect = false;
        });

      if (options.width > 0) element.css('width', options.width);

      needsInit = false;
    }

    function target(event) {
      let element = event.target;
      while (element && element.tagName != 'LI') element = element.parentNode;
      // more fun with IE, sometimes event.target is empty, just ignore it then
      if (!element) return [];
      return element;
    }

    function moveSelect(step) {
      listItems.slice(active, active + 1).removeClass(CLASSES.ACTIVE);
      movePosition(step);
      const activeItem = listItems.slice(active, active + 1).addClass(CLASSES.ACTIVE);
      if (options.scroll) {
        let offset = 0;
        listItems.slice(0, active).each(function () {
          offset += this.offsetHeight;
        });
        if (offset + activeItem[0].offsetHeight - list.scrollTop() > list[0].clientHeight) {
          list.scrollTop(offset + activeItem[0].offsetHeight - list.innerHeight());
        } else if (offset < list.scrollTop()) {
          list.scrollTop(offset);
        }
      }
    }

    function movePosition(step) {
      if (
        options.scrollJumpPosition ||
        (!options.scrollJumpPosition && !((step < 0 && active == 0) || (step > 0 && active == listItems.length - 1)))
      ) {
        active += step;
        if (active < 0) {
          active = listItems.length - 1;
        } else if (active >= listItems.length) {
          active = 0;
        }
      }
    }

    function limitNumberOfItems(available) {
      return options.max && options.max < available ? options.max : available;
    }

    function fillList() {
      list.empty();
      const max = limitNumberOfItems(data.length);
      for (let i = 0; i < max; i++) {
        if (!data[i]) continue;
        const formatted = options.formatItem(data[i].data, i + 1, max, data[i].value, term);
        if (formatted === false) continue;
        const li = $('<li/>')
          .html(options.highlight(formatted, term))
          .addClass(i % 2 == 0 ? 'ac_even' : 'ac_odd')
          .appendTo(list)[0];
        $.data(li, 'ac_data', data[i]);
      }
      listItems = list.find('li');
      if (options.selectFirst) {
        listItems.slice(0, 1).addClass(CLASSES.ACTIVE);
        active = 0;
      }
      // apply bgiframe if available
      if ($.fn.bgiframe) list.bgiframe();
    }

    return {
      display(d, q) {
        init();
        data = d;
        term = q;
        fillList();
      },
      next() {
        moveSelect(1);
      },
      prev() {
        moveSelect(-1);
      },
      pageUp() {
        if (active != 0 && active - 8 < 0) {
          moveSelect(-active);
        } else {
          moveSelect(-8);
        }
      },
      pageDown() {
        if (active != listItems.length - 1 && active + 8 > listItems.length) {
          moveSelect(listItems.length - 1 - active);
        } else {
          moveSelect(8);
        }
      },
      hide() {
        if (element) {
          element.hide();
        }
        if (listItems) {
          listItems.removeClass(CLASSES.ACTIVE);
        }
        active = -1;
      },
      visible() {
        return element?.is(':visible');
      },
      current() {
        return this.visible() && (listItems.filter(`.${CLASSES.ACTIVE}`)[0] || (options.selectFirst && listItems[0]));
      },
      show() {
        const offset = $(input).offset();
        element
          .css({
            width: typeof options.width == 'string' || options.width > 0 ? options.width : $(input).width(),
            top: offset.top + input.offsetHeight,
            left: offset.left,
          })
          .show();
        if (options.scroll) {
          list.scrollTop(0);
          list.css({
            maxHeight: options.scrollHeight,
            overflow: 'auto',
          });

          if (navigator.userAgent.includes('MSIE') && typeof document.body.style.maxHeight === 'undefined') {
            let listHeight = 0;
            listItems.each(function () {
              listHeight += this.offsetHeight;
            });
            const scrollbarsVisible = listHeight > options.scrollHeight;
            list.css('height', scrollbarsVisible ? options.scrollHeight : listHeight);
            if (!scrollbarsVisible) {
              // IE doesn't recalculate width when scrollbar disappears
              listItems.width(
                list.width() - parseInt(listItems.css('padding-left')) - parseInt(listItems.css('padding-right')),
              );
            }
          }
        }
      },
      selected() {
        const selected = listItems && listItems.filter(`.${CLASSES.ACTIVE}`).removeClass(CLASSES.ACTIVE);
        return selected?.length && $.data(selected[0], 'ac_data');
      },
      emptyList() {
        list.empty();
      },
      unbind() {
        element.remove();
      },
    };
  };

  $.fn.selection = function (start, end) {
    if (start !== undefined) {
      return this.each(function () {
        if (this.createTextRange) {
          const selRange = this.createTextRange();
          if (end === undefined || start == end) {
            selRange.move('character', start);
          } else {
            selRange.collapse(true);
            selRange.moveStart('character', start);
            selRange.moveEnd('character', end);
          }
          selRange.select();
        } else if (this.setSelectionRange) {
          this.setSelectionRange(start, end);
        } else if (this.selectionStart) {
          this.selectionStart = start;
          this.selectionEnd = end;
        }
      });
    }
    const field = this[0];
    if (field.createTextRange) {
      const range = document.selection.createRange();
      const orig = field.value;
      const teststring = '<->';
      const textLength = range.text.length;
      range.text = teststring;
      const caretAt = field.value.indexOf(teststring);
      field.value = orig;
      this.selection(caretAt, caretAt + textLength);
      return {
        start: caretAt,
        end: caretAt + textLength,
      };
    } else if (field.selectionStart !== undefined) {
      return {
        start: field.selectionStart,
        end: field.selectionEnd,
      };
    }
  };
})();
