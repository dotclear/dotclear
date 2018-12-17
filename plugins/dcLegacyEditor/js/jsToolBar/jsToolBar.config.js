/*global jsToolBar, getData, mergeDeep */
'use strict';

jsToolBar.prototype.elements.removeFormat = jsToolBar.prototype.elements.removeFormat || {};
mergeDeep(jsToolBar.prototype, getData('legacy_editor'));
