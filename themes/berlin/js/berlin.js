/*global $, dotclear */
'use strict';

const dotclear_berlin = dotclear.getData('dotclear_berlin');

$('html').addClass('js');
// Show/Hide main menu
$('.header__nav')
  .before(`<button id="hamburger" type="button" aria-label="${dotclear_berlin.navigation}" aria-expanded="false"></button>`)
  .toggle();
$('#hamburger').on('click', function () {
  $(this).attr('aria-expanded', $(this).attr('aria-expanded') == 'true' ? 'false' : 'true');
  $(this).toggleClass('open');
  $('.header__nav').toggle('easing', () => {
    if ($('#hamburger').hasClass('open')) {
      $('.header__nav li:first a')[0].focus();
    }
  });
});
// Show/Hide sidebar on small screens
$('#main').prepend(
  `<button id="offcanvas-on" type="button"><span class="visually-hidden">${dotclear_berlin.show_menu}</span></button>`
);
$('#offcanvas-on').on('click', () => {
  const btn = $(
    `<button id="offcanvas-off" type="button"><span class="visually-hidden">${dotclear_berlin.hide_menu}</span></button>`
  );
  $('#wrapper').addClass('off-canvas');
  $('#footer').addClass('off-canvas');
  $('#sidebar').prepend(btn);
  btn[0].focus({
    preventScroll: true,
  });
  btn.on('click', (evt) => {
    $('#wrapper').removeClass('off-canvas');
    $('#footer').removeClass('off-canvas');
    evt.target.remove();
    $('#offcanvas-on')[0].focus();
  });
});
$(document).ready(() => {
  // totop scroll
  $(window).scroll(function () {
    if ($(this).scrollTop() != 0) {
      $('#gotop').fadeIn();
    } else {
      $('#gotop').fadeOut();
    }
  });
  $('#gotop').on('click', (e) => {
    $('body,html').animate(
      {
        scrollTop: 0,
      },
      800
    );
    e.preventDefault();
  });
});
