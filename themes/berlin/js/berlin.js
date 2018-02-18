/*global $, dotclear_berlin_navigation, dotclear_berlin_show_menu, dotclear_berlin_hide_menu */
'use strict';

$('html').addClass('js');
// Show/Hide main menu
$('.header__nav').
before('<button id="hamburger" type="button"><span class="visually-hidden">' + dotclear_berlin_navigation + '</span></button>').
toggle();
$('#hamburger').click(function() {
  $(this).toggleClass('open');
  $('.header__nav').toggle('easing');
});
// Show/Hide sidebar on small screens
$('#main').prepend('<button id="offcanvas-on" type="button"><span class="visually-hidden">' + dotclear_berlin_show_menu + '</span></button>');
$('#offcanvas-on').click(function() {
  var btn = $('<button id="offcanvas-off" type="button"><span class="visually-hidden">' + dotclear_berlin_hide_menu + '</span></button>');
  $('#wrapper').addClass('off-canvas');
  $('#footer').addClass('off-canvas');
  $('#sidebar').prepend(btn);
  btn.click(function(evt) {
    $('#wrapper').removeClass('off-canvas');
    $('#footer').removeClass('off-canvas');
    evt.target.remove();
  });
});
$(document).ready(function() {
  // totop scroll
  $(window).scroll(function() {
    if ($(this).scrollTop() != 0) {
      $('#gotop').fadeIn();
    } else {
      $('#gotop').fadeOut();
    }
  });
  $('#gotop').click(function(e) {
    $('body,html').animate({
      scrollTop: 0
    }, 800);
    e.preventDefault();
  });
});
