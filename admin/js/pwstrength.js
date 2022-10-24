/*global dotclear */
'use strict';

dotclear.passwordStrength = (opts) => {
  /**
   * Calculates the entropy (source: https://github.com/autonomoussoftware/fast-password-entropy).
   *
   * @param      DOMElement   e       input password field
   * @return     integer              The entropy (from 0 to 99).
   */
  const computeEntropy = (e) => {
    const stdCharsets = [
      {
        re: /[a-z]/, // abcdefghijklmnopqrstuvwxyz
        length: 26,
      },
      {
        re: /[A-Z]/, // ABCDEFGHIJKLMNOPQRSTUVWXYZ
        length: 26,
      },
      {
        re: /[0-9]/, // 1234567890
        length: 10,
      },
      {
        re: /[^a-zA-Z0-9]/, //  !"#$%&'()*+,-./:;<=>?@[\]^_`{|}~ (and any other)
        length: 33,
      },
    ];
    const calcCharsetLengthWith = (charsets) => (string) =>
      charsets.reduce((length, charset) => length + (charset.re.test(string) ? charset.length : 0), 0);

    const calcCharsetLength = calcCharsetLengthWith(stdCharsets);
    const calcEntropy = (charset, length) => Math.round((length * Math.log(charset)) / Math.LN2);

    const passwordEntropy = (string) => (string ? calcEntropy(calcCharsetLength(string), string.length) : 0);

    return Math.max(Math.min(passwordEntropy(e.value), 99), 0);
  };

  const updateMeter = (e) => {
    e.preventDefault();
    const password = e.currentTarget;

    // Find associated meter
    let meter = password.nextElementSibling;
    while (meter) {
      if (meter.matches('.pw-strength-meter')) break;
      meter = meter.nextElementSibling;
    }
    if (!meter) {
      return;
    }

    // Get current strength
    const meterValue = computeEntropy(password);
    let meterContent = '';
    if (meterValue >= meter.getAttribute('high')) {
      meterContent = options.max;
    } else if (meterValue <= meter.getAttribute('low')) {
      meterContent = options.min;
    } else {
      meterContent = options.avg;
    }

    // Update meter
    meter.setAttribute('value', meterValue);
    meter.setAttribute('title', meterContent);
    meter.innerHTML = meterContent;
  };

  // Compose meter
  const meterTemplate = new DOMParser().parseFromString(
    `<meter aria-live="polite" class="pw-strength-meter" value="" title="" min="0" max="99" optimum="99" low="30" high="60"></meter>`,
    'text/html',
  ).body.firstChild;

  const options = opts || {
    min: '-',
    avg: '~',
    max: '+',
  };

  const passwordFields = document.querySelectorAll('input[type=password].pw-strength');

  // Add a meter to each password.pw-strength
  for (const passwordField of passwordFields) {
    const meter = meterTemplate.cloneNode(true);
    const sibling = passwordField.nextElementSibling;
    if (sibling?.classList.contains('pw-show') || sibling?.classList.contains('pw-hide')) {
      sibling.after(meter);
    } else {
      passwordField.after(meter);
    }
    // Adjust meter size (displayed below password field)
    meter.style.width = window.getComputedStyle(passwordField).getPropertyValue('width');
    passwordField.addEventListener('keyup', updateMeter);
  }
};
