/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  const currentPasswordField = document.getElementById('cur_pwd');
  if (!currentPasswordField) return;

  const emailField = document.getElementById('user_email');
  const newPasswordField = document.getElementById('new_pwd');

  const userEmail = emailField?.value; // Keep current user email

  // Helper to check if current password is required
  const needPassword = () => {
    if (emailField?.value !== userEmail) return true;
    return !!newPasswordField?.value;
  };

  const userprefsData = dotclear.getData('userprefs');

  emailField?.addEventListener('change', () => {
    if (needPassword()) currentPasswordField.setAttribute('required', 'true');
    else currentPasswordField.removeAttribute('required');
  });

  newPasswordField?.addEventListener('change', () => {
    if (needPassword()) currentPasswordField.setAttribute('required', 'true');
    else currentPasswordField.removeAttribute('required');
  });

  // Password strength
  dotclear.passwordStrength(dotclear.getData('pwstrength'));

  // Responsive tables
  dotclear.responsiveCellHeaders(
    document.querySelector('#user_options_lists_container table'),
    '#user_options_lists_container table',
    0,
    true,
  );

  // Confirm on fav removal
  const remove = document.getElementById('removeaction');
  remove?.addEventListener('click', (event) => dotclear.confirm(userprefsData.remove, event));

  // webauthn passkey registration
  dotclear.webAuthnRegistration = () => {
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
              const b = globalThis.atob(s.substring(pre.length, s.length - suf.length));
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

    // (B) ERROR HELPER
    const wanError = (error) => {
      if (dotclear.debug) console.log(error || 'unknown error occured');
      // NotAllowedError is thrown when user cancels or when request timed out
      const message =
        error?.name === 'NotAllowedError' || error?.name === 'AbortError'
          ? userprefsData.passkeycancelled
          : userprefsData.passkeyfailed;
      globalThis.alert(message);
    };

    const passkeyLabel = prompt(userprefsData.passkeylabel, '');
    if (passkeyLabel === null) {
      // user cancels registration
      return;
    }

    // register flow step 1: get arguments
    dotclear.jsonServicesPost(
      'webAuthnRegistration',
      (prepareCreate) => {
        // error handling
        if (prepareCreate.success === false) {
          wanError(prepareCreate.message || 'webauthn: prepareCreate failed');
          return;
        }

        wanHelper.bta(prepareCreate.arguments);

        // register flow step 2: query passkey
        navigator.credentials
          .create(prepareCreate.arguments)
          .then((publicKeyCredential) => {
            // register flow step 3: register passkey
            dotclear.jsonServicesPost(
              'webAuthnRegistration',
              (processCreate) => {
                // error handling
                if (processCreate.success === false) {
                  wanError(processCreate.message || 'webauthn: processCreate failed');
                  return;
                }
                globalThis.location.reload();
              },
              {
                json: 1,
                step: 'process',
                label: passkeyLabel.toString(),
                client: publicKeyCredential.response.clientDataJSON
                  ? wanHelper.atb(publicKeyCredential.response.clientDataJSON)
                  : null,
                attestation: publicKeyCredential.response.attestationObject
                  ? wanHelper.atb(publicKeyCredential.response.attestationObject)
                  : null,
                transports: publicKeyCredential.response.getTransports
                  ? btoa(JSON.stringify(publicKeyCredential.response.getTransports()))
                  : null,
              },
              wanError,
            );
          })
          .catch(wanError);
      },
      {
        json: 1,
        step: 'prepare',
      },
      wanError,
    );
  };

  // browser supports passkey (WebAuthn)
  if (globalThis.PublicKeyCredential && navigator.credentials) {
    $('#webauthn_action input').on('click', (e) => {
      dotclear.webAuthnRegistration();
      e.preventDefault();
      e.returnValue = '';
    });
  } else {
    $('#webauthn_action').hide();
    // todo: replace with a text 'passkey not supported'
  }
});
