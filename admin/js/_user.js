/*global $, getData */
'use strict';

$(function () {
  if ($('#new_pwd').length == 0) {
    return;
  }
  const texts = getData('user');
  $('#new_pwd').pwstrength({
    texts: texts,
  });
});
