/*global $, dotclear */
'use strict';

$(function () {
  $('.recall-for-all').attr('disabled', 'disabled');
  $('#settings_recall_all').on('change', function () {
    if ($(this).attr('selected') != 'selected') {
      $('.recall-per-task').attr('disabled', 'disabled');
      $('.recall-for-all').removeAttr('disabled');
    }
  });
  $('#settings_recall_separate').on('change', function () {
    if ($(this).attr('selected') != 'selected') {
      $('.recall-per-task').removeAttr('disabled');
      $('.recall-for-all').attr('disabled', 'disabled');
    }
  });
  dotclear.condSubmit('#part-maintenance input[type="radio"]', '#part-maintenance input[type="submit"]');
  dotclear.condSubmit('#part-backup input[type="radio"]', '#part-backup input[type="submit"]');
  dotclear.condSubmit('#part-dev input[type="radio"]', '#part-dev input[type="submit"]');
});
