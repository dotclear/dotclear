/*global jsToolBar, dotclear */
'use strict';

jsToolBar.prototype.elements.removeFormat = jsToolBar.prototype.elements.removeFormat || {};
dotclear.mergeDeep(jsToolBar.prototype, dotclear.getData('legacy_editor'));
