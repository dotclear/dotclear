/*global $, getData */
'use strict';

if (typeof post_remember_str === 'undefined' && typeof getData !== 'undefined') {
  var post_remember_str = getData('dc_post_remember_str').post_remember_str;
}

$(function() {
	$('#comment-form p:has(input[type=submit][name=preview],button[type=submit][name=preview])').
		before(
			'<p class="remember"><input type="checkbox" id="c_remember" name="c_remember" /> ' +
			'<label for="c_remember">' + post_remember_str + '</label>' +
			'</p>'
		);

	var cookie = readCookie($.cookie('comment_info'));

	if (cookie != false) {
		$('#c_name').val(cookie[0]);
		$('#c_mail').val(cookie[1]);
		$('#c_site').val(cookie[2]);
		$('#c_remember').attr('checked','checked');
	}

	$('#c_remember').click(function() {
		if (this.checked) {
			setCookie();
		} else {
			dropCookie();
		}
	});

	$('#c_name').change(function() {
		if ($('#c_remember').get(0).checked) {
			setCookie();
		}
	});

	$('#c_mail').change(function() {
		if ($('#c_remember').get(0).checked) {
			setCookie();
		}
	});

	$('#c_site').change(function() {
		if ($('#c_remember').get(0).checked) {
			setCookie();
		}
	});

	function setCookie() {
		var name = $('#c_name').val();
		var mail = $('#c_mail').val();
		var site = $('#c_site').val();
		var cpath = $('h1 a').attr('href');
		if (!cpath) {
			cpath = '/';
		} else {
			cpath = cpath.replace(/.*:\/\/[^\/]*([^?]*).*/g,'$1');
		}

		$.cookie('comment_info', name + '\n' + mail + '\n' + site, {expires: 60, path: cpath});
	}

	function dropCookie() {
		$.cookie('comment_info','',{expires: -30, path: '/'});
	}

	function readCookie(c) {
		if (!c) {
			return false;
		}

		var s = c.split('\n');

		if (s.length != 3) {
			dropCookie();
			return false;
		}

		return s;
	}
});
