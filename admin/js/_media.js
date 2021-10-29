/*global $, jQuery, dotclear */
'use strict';

(($) => {
  $.fn.enhancedUploader = function () {
    return this.each(function () {
      const me = $(this);
      const $container = $(me).parent();

      function enableButton(button) {
        button.prop('disabled', false).removeClass('disabled');
      }

      function disableButton(button) {
        button.prop('disabled', true).addClass('disabled');
      }

      function displayMessageInQueue(n) {
        let msg = '';
        if (n == 1) {
          msg = dotclear.jsUpload.msg.file_in_queue;
        } else if (n > 1) {
          msg = dotclear.jsUpload.msg.files_in_queue;
          msg = msg.replace(/%d/, n);
        } else {
          msg = dotclear.jsUpload.msg.no_file_in_queue;
        }
        $('.queue-message', me).html(msg);
      }

      $('.button.choose_files').on('click', (e) => {
        if ($container.hasClass('enhanced_uploader')) {
          // Use the native click() of the file input.
          $('#upfile').trigger('click');
          e.preventDefault();
        }
      });

      $('.button.cancel', '#fileupload .fileupload-buttonbar').on('click', () => {
        $('.button.cancel', '#fileupload .fileupload-buttonbar').hide();
        disableButton($('.button.start', '#fileupload .fileupload-buttonbar'));
        displayMessageInQueue(0);
      });

      $(me).on('click', '.cancel', () => {
        if ($('.fileupload-ctrl .files .template-upload', me).length == 0) {
          $('.button.cancel', '#fileupload .fileupload-buttonbar').hide();
          disableButton($('.button.start', '#fileupload .fileupload-buttonbar'));
        }
        displayMessageInQueue($('.files .template-upload', me).length);
      });

      $('.button.clean', me).on('click', function (e) {
        $('.fileupload-ctrl .files .template-download', me).slideUp(500, function () {
          $(this).remove();
        });
        $(this).hide();
        e.preventDefault();
      });

      $(me)
        .fileupload({
          url: $(me).attr('action'),
          autoUpload: false,
          sequentialUploads: true,
          uploadTemplateId: null,
          downloadTemplateId: null,
          uploadTemplate: dotclear.jsUpload.template_upload,
          downloadTemplate: dotclear.jsUpload.template_download,
        })
        .on('fileuploadadd', () => {
          $('.button.cancel').css('display', 'inline-block');
          $('#fileupload .fileupload-buttonbar').show();
          enableButton($('.button.start', '#fileupload .fileupload-buttonbar'));
        })
        .on('fileuploadadded', () => {
          displayMessageInQueue($('.files .template-upload', me).length);
        })
        .on('fileuploaddone', (e, data) => {
          if (data.result.files[0].html !== undefined) {
            $('.media-list .media-items-bloc').append(data.result.files[0].html);
            $('#form-medias .hide').removeClass('hide');
          }
          $('.button.clean').css('display', 'inline-block');
          $(me).show();
        })
        .on('fileuploadalways', () => {
          displayMessageInQueue($('.files .template-upload', me).length);
          if ($('.fileupload-ctrl .files .template-upload', me).length == 0) {
            $('.button.cancel', '#fileupload .fileupload-buttonbar').hide();
            disableButton($('.button.start', '#fileupload .fileupload-buttonbar'));
          }
        });

      let msg;
      let label;

      if ($container.hasClass('enhanced_uploader')) {
        msg = dotclear.msg.enhanced_uploader_disable;
        label = dotclear.jsUpload.msg.choose_files;
        $(me).fileupload({
          disabled: false,
        });
        displayMessageInQueue(0);
        disableButton($('.button.start', '#fileupload .fileupload-buttonbar'));
      } else {
        msg = dotclear.msg.enhanced_uploader_activate;
        label = dotclear.jsUpload.msg.choose_file;
        $(me).fileupload({
          disabled: true,
        });
      }

      $(`<p class="clear"><button type="button" class="enhanced-toggle">${msg}</button></p>`)
        .on('click', function (e) {
          if ($container.hasClass('enhanced_uploader')) {
            msg = dotclear.msg.enhanced_uploader_activate;
            label = dotclear.jsUpload.msg.choose_file;
            $('#upfile').attr('multiple', false);
            enableButton($('.button.start', '#fileupload .fileupload-buttonbar'));

            // when a user has clicked enhanced_uploader, and has added files
            // We must remove files in table
            $('.files .upload-file', me).remove();
            $('.button.cancel,.button.clean', '#fileupload .fileupload-buttonbar').hide();
            $(me).fileupload({
              disabled: true,
            });
            $('.queue-message', me).html('').hide();
          } else {
            msg = dotclear.msg.enhanced_uploader_disable;
            label = dotclear.jsUpload.msg.choose_files;
            $('#upfile').attr('multiple', true);
            const startButton = $('.button.start');
            const buttonBar = $('#fileupload .fileupload-buttonbar');
            disableButton(startButton);
            disableButton(buttonBar);
            startButton.css('display', 'inline-block');
            buttonBar.show();
            $(me).fileupload({
              disabled: false,
            });
            $('.queue-message', me).show();
            displayMessageInQueue(0);
          }
          $(this).find('button').text(msg);
          $('.add-label', me).text(label);

          $container.toggleClass('enhanced_uploader');
          e.preventDefault();
        })
        .appendTo(me);
    });
  };
})(jQuery);

$(() => {
  $('#fileupload').enhancedUploader();

  $('.checkboxes-helpers').each(function () {
    dotclear.checkboxesHelpers(this, undefined, '#form-medias input[type="checkbox"]', '#form-medias #delete_medias');
  });
  dotclear.condSubmit('#form-medias input[type="checkbox"]', '#form-medias #delete_medias');

  $('#form-medias #delete_medias').on('click', (e) => {
    const count_checked = $('input[name="medias[]"]:checked', $('#form-medias')).length;
    if (count_checked == 0) {
      e.preventDefault();
      return false;
    }
    return window.confirm(dotclear.msg.confirm_delete_medias.replace('%d', count_checked));
  });

  // Preview media
  $('.modal-image').magnificPopup({
    type: 'image',
  });

  // attach media
  $('#form-medias').on('click', '.media-item .attach-media', function (e) {
    const parts = $(this).prop('href').split('?');
    const str_params = parts[1].split('&');
    let postData = {};

    for (let n = 0; n < str_params.length; n++) {
      const kv = str_params[n].split('=');
      postData[kv[0]] = kv[1];
    }
    postData.xd_check = dotclear.nonce;

    $.post(parts[0], postData, (data) => {
      if (data.url !== undefined) {
        document.location = data.url;
      }
    });

    e.preventDefault();
  });

  // Replace remove links by a POST on hidden form
  fileRemoveAct();

  function fileRemoveAct() {
    $('body').on('click', 'a.media-remove', function () {
      const m_name = $(this).parents('.media-item-bloc').find('a.media-link').text();
      let m_text = '';
      m_text =
        $(this).parents('div.media-folder').length == 0 ?
          dotclear.msg.confirm_delete_media.replace('%s', m_name) :
          dotclear.msg.confirm_delete_directory.replace('%s', m_name);
      if (window.confirm(m_text)) {
        const f = $('#media-remove-hide').get(0);
        f.elements.remove.value = this.href.replace(/^(.*)&remove=(.*?)(&|$)/, '$2');
        this.href = '';
        f.submit();
      }
      return false;
    });
  }

  // Switch folder
  const urlmenu = document.getElementById('switchfolder');
  if (urlmenu) {
    urlmenu.onchange = function () {
      window.location = this.options[this.selectedIndex].value;
    };
  }
});
