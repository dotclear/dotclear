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
    return !!(newPasswordField?.value);
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
    var wanHelper = {
      // (A1) ARRAY BUFFER TO BASE 64
      atb : b => {
        let u = new Uint8Array(b), s = "";
        for (let i=0; i<u.byteLength; i++) { s += String.fromCharCode(u[i]); }
        return btoa(s);
      },
     
      // (A2) BASE 64 TO ARRAY BUFFER
      bta : o => {
        let pre = "=?BINARY?B?", suf = "?=";
        for (let k in o) { if (typeof o[k] == "string") {
          let s = o[k];
          if (s.substring(0, pre.length)==pre && s.substring(s.length - suf.length)==suf) {
            let b = window.atob(s.substring(pre.length, s.length - suf.length)),
            u = new Uint8Array(b.length);
            for (let i=0; i<b.length; i++) { u[i] = b.charCodeAt(i); }
            o[k] = u.buffer;
          }
        } else { wanHelper.bta(o[k]); }}
      }
    };
    
    try {
      // browser does not support passkey
      if (!("credentials" in navigator)) {
        throw new Error('Browser not supported.');
      }

      // register flow step 1: get arguments
      dotclear.jsonServicesPost(
        'webAuthnRegistration',
        (prepareCreate) => {
          // error handling
          if (prepareCreate.success === false) {
            throw new Error(prepareCreate.message || 'unknown error occured');
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
                    throw new Error(prepareCreate.message || 'unknown error occured');
                  }
                  //window.alert(processCreate.message);
                  window.location.reload();
                },
                {
                  json: 1,
                  step: 'process',
                  client : publicKeyCredential.response.clientDataJSON ? wanHelper.atb(publicKeyCredential.response.clientDataJSON) : null,
                  attestation : publicKeyCredential.response.attestationObject ? wanHelper.atb(publicKeyCredential.response.attestationObject) : null,
                  transports : publicKeyCredential.response.getTransports ? wanHelper.atb(publicKeyCredential.response.getTransports()) : null,
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

  if ("credentials" in navigator) {
    $('#webauthn_action input').on('click', function (e) {
      dotclear.webAuthnRegistration();
      e.preventDefault();
    });
  } else {
    $('#webauthn_action').hide();
    // todo: replace with a text 'passkey not supported'
  }
});
