/*global $, dotclear, jsToolBar */
'use strict';

dotclear.postExpander = function(line) {

  var title = $(line).find('.widget-name');
  title.find('.form-note').remove();

  var order = title.find('input[name*=order]');
  var link = $('<a href="#" alt="expand" class="aexpand"/>').append(title.text());
  var tools = title.find('.toolsWidget');
  var br = title.find('br');
  title.empty().append(order).append(link).append(tools).append(br);

  var b = document.createElement('button');
  b.setAttribute('type', 'button');
  b.className = 'details-cmd';
  b.value = dotclear.img_plus_txt;
  b.setAttribute('aria-label', dotclear.img_plus_alt);
  var t = document.createTextNode(dotclear.img_plus_txt);
  b.appendChild(t);
  b.onclick = function(e) {
    e.preventDefault();
    dotclear.viewPostContent($(this).parents('li'));
  };
  link.click(function(e) {
    e.preventDefault();
    dotclear.viewPostContent($(this).parents('li'));
  });
  title.prepend(b);
};

dotclear.viewPostContent = function(line, action) {
  action = action || 'toogle';
  var img = line.find('.details-cmd');
  var isopen = img.attr('aria-label') == dotclear.img_plus_alt;

  if (action == 'close' || (action == 'toogle' && !isopen)) {
    line.find('.widgetSettings').hide();
    img.html(dotclear.img_plus_txt);
    img.attr('value', dotclear.img_plus_txt);
    img.attr('aria-label', dotclear.img_plus_alt);
  } else if (action == 'open' || (action == 'toogle' && isopen)) {
    line.find('.widgetSettings').show();
    img.html(dotclear.img_minus_txt);
    img.attr('value', dotclear.img_minus_txt);
    img.attr('aria-label', dotclear.img_minus_alt);
  }

};

function reorder(ul) {
  // réordonne
  if (ul.attr('id')) {
    var $list = ul.find('li').not('.empty-widgets');
    $list.each(function(i) {
      var $this = $(this);

      // trouve la zone de réception
      var name = ul.attr('id').split('dnd').join('');

      // modifie le name en conséquence
      $this.find('*[name^=w]').each(function() {
        var tab = $(this).attr('name').split('][');
        tab[0] = 'w[' + name;
        tab[1] = i;
        $(this).attr('name', tab.join(']['));
      });

      // ainsi que le champ d'ordre sans js (au cas ou)
      $this.find('input[name*=order]').val(i);

      // active ou désactive les fléches
      if (i == 0) {
        $this.find('input.upWidget').prop('disabled', true);
        $this.find('input.upWidget').prop('src', 'images/disabled_up.png');
      } else {
        $this.find('input.upWidget').removeAttr('disabled');
        $this.find('input.upWidget').prop('src', 'images/up.png');
      }
      if (i == $list.length - 1) {
        $this.find('input.downWidget').prop('disabled', true);
        $this.find('input.downWidget').prop('src', 'images/disabled_down.png');
      } else {
        $this.find('input.downWidget').removeAttr('disabled');
        $this.find('input.downWidget').prop('src', 'images/down.png');
      }

    });
  }
}

$(function() {
  // reset
  $('input[name="wreset"]').click(function() {
    return window.confirm(dotclear.msg.confirm_widgets_reset);
  });

  // plier/déplier
  $('#dndnav > li, #dndextra > li, #dndcustom > li').each(function() {
    dotclear.postExpander(this);
    dotclear.viewPostContent($(this), 'close');
  });

  // remove
  $('input[name*=_rem]').click(function(e) {
    e.preventDefault();
    $(this).parents('li').remove();
  });

  // move
  $('input[name*=_down]').click(function(e) {
    e.preventDefault();
    var $this = $(this);
    var $li = $this.parents('li');
    $li.next().after($li);
    reorder($this.parents('ul.connected'));
  });
  $('input[name*=_up]').click(function(e) {
    e.preventDefault();
    var $this = $(this);
    var $li = $this.parents('li');
    $li.prev().before($li);
    reorder($this.parents('ul.connected'));
  });

  // HTML text editor
  if ($.isFunction(jsToolBar)) {
    $('#sidebarsWidgets textarea').each(function() {
      var tbWidgetText = new jsToolBar(this);
      tbWidgetText.draw('xhtml');
    });
  }

});
