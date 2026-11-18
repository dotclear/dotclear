/*global dotclear */
'use strict';

/**
 * Use a passkey instead of the password to confirm sensitive actions.
 *
 * A button is added next to each given password field. On successful passkey verification,
 * the REST service gives a one time token which is sent with the form instead of the password.
 *
 * @since 2.41
 */
dotclear.ready(() => {
  const data = dotclear.getData('webauthn_verify');
  if (!data?.fields?.length) return;

  // browser does not support passkey (WebAuthn)
  if (globalThis.PublicKeyCredential === undefined || !navigator.credentials) return;

  // (A) HELPER FUNCTIONS
  const wanHelper = {
    // (A1) ARRAY BUFFER TO BASE 64
    atb: (b) => {
      const u = new Uint8Array(b);
      let s = '';
      for (let i = 0; i < u.byteLength; i++) {
        s += String.fromCodePoint(u[i]);
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
              u[i] = b.codePointAt(i);
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
      error?.name === 'NotAllowedError' || error?.name === 'AbortError' ? data.cancelled : error?.message || data.failed;
    globalThis.alert(message);
  };

  // (C) VERIFICATION FLOW
  const verify = (onSuccess) => {
    // verify flow step 1: get arguments
    dotclear.jsonServicesPost(
      'webAuthnVerification',
      (prepareVerify) => {
        if (prepareVerify.success === false) {
          wanError({ message: prepareVerify.message || data.failed });
          return;
        }
        wanHelper.bta(prepareVerify.arguments);

        // verify flow step 2: query passkey
        navigator.credentials
          .get(prepareVerify.arguments)
          .then((publicKeyCredential) => {
            // verify flow step 3: check passkey vs current user
            dotclear.jsonServicesPost(
              'webAuthnVerification',
              (processVerify) => {
                if (processVerify.success === false || !processVerify.token) {
                  wanError({ message: processVerify.message || data.failed });
                  return;
                }
                onSuccess(processVerify.token);
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
                user: publicKeyCredential.response.userHandle
                  ? wanHelper.atb(publicKeyCredential.response.userHandle)
                  : null,
              },
              () => wanError(),
            );
          })
          .catch(wanError);
      },
      {
        json: 1,
        step: 'prepare',
      },
      () => wanError(),
    );
  };

  // (D) ADD A BUTTON NEXT TO EACH PASSWORD FIELD
  for (const name of data.fields) {
    for (const field of document.querySelectorAll(`input[type="password"][name="${CSS.escape(name)}"]`)) {
      const form = field.form;
      if (!form) continue;

      const button = document.createElement('button');
      button.type = 'button';
      button.classList.add('webauthn-verify');
      button.textContent = data.button;

      // Put button after the field (or after its label if field is inside it),
      // but keep the show/hide password button just after the field (see dotclear.passwordHelpers)
      let anchor = field.parentElement?.tagName === 'LABEL' ? field.parentElement : field;
      const next = anchor.nextElementSibling;
      if (next?.tagName === 'BUTTON' && (next.classList.contains('pw-show') || next.classList.contains('pw-hide'))) {
        anchor = next;
      }
      anchor.after(button);

      button.addEventListener('click', () => {
        verify((token) => {
          // Set (or update) the token field of the form
          let tokenField = form.querySelector(`input[type="hidden"][name="${CSS.escape(data.name)}"]`);
          if (!tokenField) {
            tokenField = document.createElement('input');
            tokenField.type = 'hidden';
            tokenField.name = data.name;
            form.append(tokenField);
          }
          tokenField.value = token;

          // Password is no more needed (a disabled field is not sent nor required)
          field.value = '';
          field.disabled = true;

          const status = document.createElement('span');
          status.classList.add('webauthn-verified');
          status.textContent = data.verified;
          button.replaceWith(status);
        });
      });
    }
  }
});
