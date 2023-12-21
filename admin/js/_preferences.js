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
    if (newPasswordField?.value) return true;
    return false;
  };

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
});
