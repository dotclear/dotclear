/*global $ */
'use strict';

$(function() {
  $.pageTabs('two-boxes');
  $('#pageslist').sortable({
    'cursor': 'move'
  });
  $('#pageslist tr').hover(function() {
    $(this).css({
      'cursor': 'move'
    });
  }, function() {
    $(this).css({
      'cursor': 'auto'
    });
  });
  $('#pageslist tr td input.position').hide();
  $('#pageslist tr td.handle').addClass('handler');
});
