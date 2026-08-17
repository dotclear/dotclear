// @ts-check
import { expect, test } from '@playwright/test';

// Ensure required env vars are present and typed as strings for @ts-check
const BACKEND_URL = process.env.BACKEND_URL || 'https://localhost.local/';
const INSTALL_NAME = process.env.INSTALL_NAME || 'Dotclear';
const LOGIN = process.env.LOGIN || 'root';
const PASSWORD = process.env.PASSWORD || 'secret';

test('has title', async ({ page }) => {
  await page.goto(BACKEND_URL);

  // Expect a title "to contain" a substring.
  await expect(page).toHaveTitle(INSTALL_NAME);
});

test('authentication', async ({ page }) => {
  await page.goto(BACKEND_URL);

  // Fill login.
  await page.getByRole('textbox', { name: 'Username:' }).fill(LOGIN);

  // Fill password.
  await page.getByRole('textbox', { name: 'Password: Show password' }).fill(PASSWORD);

  // Click button.
  await page.getByRole('button', { name: 'log in' }).click();

  // Wait for complete loading
  await page.waitForLoadState();

  // Expects page to have a disconnect link.
  await expect(page.locator('[href="index.php?process=Logout"]')).toBeVisible();
});

test('login and logout', async ({ page }) => {
  await page.goto(BACKEND_URL);

  // Fill login.
  await page.getByRole('textbox', { name: 'Username:' }).fill(LOGIN);

  // Fill password.
  await page.getByRole('textbox', { name: 'Password: Show password' }).fill(PASSWORD);

  // Click button.
  await page.getByRole('button', { name: 'log in' }).click();

  // Wait for complete loading
  await page.waitForLoadState();

  // Expects page to have a disconnect link.
  await expect(page.locator('[href="index.php?process=Logout"]')).toBeVisible();

  // Click logout link
  await page.locator('[href="index.php?process=Logout"]').click();

  // Wait for complete loading
  await page.waitForLoadState();

  // Expects page to have a connect form button.
  await expect(page.getByRole('button', { name: 'log in' })).toBeVisible();
});
