/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  // Give focus to user field
  /**
   * @type {HTMLInputElement|null}
   */
  const uid = document.querySelector('input[name=user_id]');
  if (uid) uid?.focus();

  /**
   * @type {HTMLElement|null}
   */
  const ckh = document.getElementById('cookie_help');
  if (ckh) ckh.style.display = navigator.cookieEnabled ? 'none' : '';

  // Password strength
  dotclear.passwordStrength(dotclear.getData('pwstrength'));

  /**
   * @type {HTMLInputElement|null}
   */
  const upw = document.querySelector('input[name=user_pwd]');
  if (!upw || !uid) {
    return;
  }

  // Add an event listener to capture Enter key press in user field to give to password field if it is empty
  uid.addEventListener('keypress', (/** @type {KeyboardEvent} */ event) => {
    if (event.key === 'Enter' && upw.value === '') {
      // Password is empty, give focus to it
      upw.focus();
      // Stop handling of this event (Enter key pressed)
      event.preventDefault();
    }
  });

  // webauthn passkey authentication
  dotclear.webAuthnAuthentication = () => {
    // (A) HELPER FUNCTIONS
    const wanHelper = {
      // (A1) ARRAY BUFFER TO BASE 64
      atb: (b) => {
        const u = new Uint8Array(b);
        let s = '';
        for (let i = 0; i < u.byteLength; i++) {
          s += String.fromCharCode(u[i]);
        }
        return btoa(s);
      },

      // (A2) BASE 64 TO ARRAY BUFFER
      bta: (o) => {
        const pre = '=?BINARY?B?';
        const suf = '?=';
        for (const k in o) {
          if (typeof o[k] === 'string') {
            const s = o[k];
            if (s.startsWith(pre) && s.endsWith(suf)) {
              const b = window.atob(s.substring(pre.length, s.length - suf.length));
              const u = new Uint8Array(b.length);
              for (let i = 0; i < b.length; i++) {
                u[i] = b.charCodeAt(i);
              }
              o[k] = u.buffer;
            }
          } else {
            wanHelper.bta(o[k]);
          }
        }
      },
    };

    try {
      // browser does not support passkey
      if (!('credentials' in navigator)) {
        throw new Error('Browser not supported.');
      }
      // authenticate flow step 1: get arguments
      dotclear.jsonServicesPost(
        'webAuthnAuthentication',
        (prepareValidate) => {
          // error handling
          if (prepareValidate.success === false) {
            throw new Error(prepareValidate.message || 'webauthn: prepareValidate failed');
          }

          wanHelper.bta(prepareValidate.arguments);

          // authenticate flow step 2: query passkey
          navigator.credentials
            .get(prepareValidate.arguments)
            .then((publicKeyCredential) => {
              // authenticate flow step 3: check passkey vs user
              dotclear.jsonServicesPost(
                'webAuthnAuthentication',
                (processValidate) => {
                  // error handling
                  if (processValidate.success === false) {
                    window.alert(processValidate.message || 'failed to authenticate with passkey');
                    //throw new Error(processValidate.message || 'webauthn: processValidate failed');
                  } else {
                    // on success, reload page to get user session from rest service
                    window.location.reload();
                  }
                },
                {
                  json: 1,
                  step: 'process',
                  id: publicKeyCredential.rawId ? wanHelper.atb(publicKeyCredential.rawId) : null,
                  client: publicKeyCredential.response.clientDataJSON
                    ? wanHelper.atb(publicKeyCredential.response.clientDataJSON)
                    : null,
                  authenticator: publicKeyCredential.response.authenticatorData
                    ? wanHelper.atb(publicKeyCredential.response.authenticatorData)
                    : null,
                  signature: publicKeyCredential.response.signature
                    ? wanHelper.atb(publicKeyCredential.response.signature)
                    : null,
                  user: publicKeyCredential.response.userHandle ? wanHelper.atb(publicKeyCredential.response.userHandle) : null,
                },
                (error) => {
                  console.log(error || 'unknown error occured');
                },
              );
            })
            .catch((error) => {
              console.log(error || 'unknown error occured');
            });
        },
        {
          json: 1,
          step: 'prepare',
        },
        (error) => {
          console.log(error || 'unknown error occured');
        },
      );
    } catch (error) {
      console.log(error.message || 'unknown error occured');
    }
  };

  if ('credentials' in navigator) {
    $('#webauthn_action input').on('click', (e) => {
      dotclear.webAuthnAuthentication();
      e.preventDefault();
    });
  } else {
    $('#webauthn_action').hide();
  }
});
