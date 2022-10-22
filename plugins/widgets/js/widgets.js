/*global $, dotclear, jsToolBar */
'use strict';

dotclear.widgetExpander = (line) => {
  const title = $(line).find('.widget-name');
  title.find('.form-note').remove();

  const order = title.find('input[name*=order]');
  const link = $('<a href="#" alt="expand" class="aexpand"/>').append(title.text());
  const tools = title.find('.toolsWidget');
  const br = title.find('br');
  title.empty().append(order).append(link).append(tools).append(br);

  const b = document.createElement('button');
  b.setAttribute('type', 'button');
  b.className = 'details-cmd';
  b.value = dotclear.img_plus_txt;
  b.setAttribute('aria-label', dotclear.img_plus_alt);
  const t = document.createTextNode(dotclear.img_plus_txt);
  b.appendChild(t);
  b.onclick = function (e) {
    e.preventDefault();
    dotclear.viewWidgetContent($(this).parents('li'));
  };
  link.on('click', function (e) {
    e.preventDefault();
    dotclear.viewWidgetContent($(this).parents('li'));
  });
  title.prepend(b);
};

dotclear.viewWidgetContent = (line, action = 'toogle') => {
  const img = line.find('.details-cmd');
  const isopen = img.attr('aria-label') == dotclear.img_plus_alt;

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

dotclear.reorder = (ul) => {
  // réordonne
  if (ul.attr('id')) {
    const $list = ul.find('li').not('.empty-widgets');
    $list.each(function (i) {
      const $this = $(this);

      // trouve la zone de réception
      const name = ul.attr('id').split('dnd').join('');

      // modifie le name en conséquence
      $this.find('*[name^=w]').each(function () {
        const tab = $(this).attr('name').split('][');
        tab[0] = `w[${name}`;
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
        $this.find('input.upWidget').prop('disabled', false);
        $this.find('input.upWidget').prop('src', 'images/up.png');
      }
      if (i == $list.length - 1) {
        $this.find('input.downWidget').prop('disabled', true);
        $this.find('input.downWidget').prop('src', 'images/disabled_down.png');
      } else {
        $this.find('input.downWidget').prop('disabled', false);
        $this.find('input.downWidget').prop('src', 'images/down.png');
      }
    });
  }
};

$(() => {
  dotclear.mergeDeep(dotclear, dotclear.getData('widgets'));

  // Blocs
  $('#sidebarNav h3').toggleWithLegend($('#sidebarNav').children().not('h3'), {
    legend_click: true,
    hide: false,
  });
  $('#sidebarExtra h3').toggleWithLegend($('#sidebarExtra').children().not('h3'), {
    legend_click: true,
    hide: false,
  });
  $('#sidebarCustom h3').toggleWithLegend($('#sidebarCustom').children().not('h3'), {
    legend_click: true,
    hide: false,
  });

  // reset
  $('input[name="wreset"]').on('click', () => window.confirm(dotclear.msg.confirm_widgets_reset));

  // plier/déplier
  $('#dndnav > li, #dndextra > li, #dndcustom > li').each(function () {
    dotclear.widgetExpander(this);
    dotclear.viewWidgetContent($(this), 'close');
  });

  // remove
  $('input[name*=_rem]').on('click', function (e) {
    e.preventDefault();
    $(this).parents('li').remove();
  });

  // move
  $('input[name*=_down]').on('click', function (e) {
    e.preventDefault();
    const $li = $(this).parents('li');
    $li.next().after($li);
    dotclear.reorder($(this).parents('ul.connected'));
  });
  $('input[name*=_up]').on('click', function (e) {
    e.preventDefault();
    const $li = $(this).parents('li');
    $li.prev().before($li);
    dotclear.reorder($(this).parents('ul.connected'));
  });

  // HTML text editor
  if (typeof jsToolBar === 'function') {
    $('#sidebarsWidgets textarea:not(.noeditor)').each(function () {
      const tbWidgetText = new jsToolBar(this);
      tbWidgetText.draw('xhtml');
    });
  }
});
