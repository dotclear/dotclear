// @ts-check
const { expect, test } = require('@playwright/test');

// Ensure required env vars are present and typed as strings for @ts-check
const BACKEND_URL = process.env.BACKEND_URL || 'https://localhost.local/';
const INSTALL_NAME = process.env.INSTALL_NAME || 'Dotclear';
const LOGIN = process.env.LOGIN || 'root';
const PASSWORD = process.env.PASSWORD || 'secret';

// Helpers

/**
 * @param {import('@playwright/test').Page} page
 */
async function login(page) {
  await page.goto(BACKEND_URL, { waitUntil: 'load' });

  // Fill login.
  await page.getByRole('textbox', { name: 'Username:' }).fill(LOGIN);

  // Fill password.
  await page.getByRole('textbox', { name: 'Password: Show password' }).fill(PASSWORD);

  // Click button.
  await page.getByRole('button', { name: 'log in' }).click();

  // Wait for complete loading
  await page.waitForLoadState();
}

// Tests

test('search', async ({ page }) => {
  // Login
  await login(page);

  // Fill search input
  await page.getByRole('combobox', { name: 'Search:' }).fill('test');
  await page.getByRole('button', { name: 'OK' }).click();

  // Check for a result
  await expect(page.getByText('Search options', { exact: true })).toBeVisible();
});
