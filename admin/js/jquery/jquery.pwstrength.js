(function($) {
  $.pwstrength = function(password) {
    var score = 0;
    var symbols = '[!,@,#,$,%,^,&,*,?,_,~]';
    // Regexp
    var check = new RegExp('(' + symbols + ')');
    var doublecheck = new RegExp('(' + '.*' + symbols + '.*' + symbols + ')');
    // password length
    score += password.length * 4;
    score += checkRepetition(1, password).length - password.length;
    score += checkRepetition(2, password).length - password.length;
    score += checkRepetition(3, password).length - password.length;
    score += checkRepetition(4, password).length - password.length;
    // password has 3 numbers
    if (password.match(/(.*[0-9].*[0-9].*[0-9])/)) {
      score += 5;
    }
    // password has at least 2 symbols
    if (password.match(doublecheck)) {
      score += 5;
    }
    // password has Upper and Lower chars
    if (password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) {
      score += 10;
    }
    // password has number and chars
    if (password.match(/([a-zA-Z])/) && password.match(/([0-9])/)) {
      score += 15;
    }
    // password has number and symbol
    if (password.match(check) && password.match(/([0-9])/)) {
      score += 15;
    }
    // password has char and symbol
    if (password.match(check) && password.match(/([a-zA-Z])/)) {
      score += 15;
    }
    // password is just numbers or chars
    if (password.match(/^\w+$/) || password.match(/^\d+$/)) {
      score -= 10;
    }
    return Math.floor(Math.max(Math.min(score, 99), 0) / 20);
  };

  function checkRepetition(rLen, str) {
    var res = "",
      repeated = false;
    for (var i = 0; i < str.length; i++) {
      repeated = true;
      for (var j = 0; j < rLen && (j + i + rLen) < str.length; j++) {
        repeated = repeated && (str.charAt(j + i) === str.charAt(j + i + rLen));
      }
      if (j < rLen) {
        repeated = false;
      }
      if (repeated) {
        i += rLen - 1;
        repeated = false;
      } else {
        res += str.charAt(i);
      }
    }
    return res;
  }

  function updateIndicator(event) {
    var strength = $.pwstrength($(this).val()),
      options = event.data,
      klass;
    klass = options.classes[strength];
    options.indicator.removeClass(options.indicator.data('pwclass'));
    options.indicator.data('pwclass', klass);
    options.indicator.addClass(klass);
    options.indicator.find(options.label).html(options.texts[strength]);
  }
  $.fn.pwstrength = function(options) {
    var options = $.extend({
      label: '.label',
      classes: ['pw-very-weak', 'pw-weak', 'pw-mediocre', 'pw-strong', 'pw-very-strong'],
      texts: ['very weak', 'weak', 'mediocre', 'strong', 'very strong']
    }, options || {});
    options.indicator = $('#' + this.data('indicator'));
    return this.keyup(options, updateIndicator);
  };
})(jQuery);
