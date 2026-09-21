// @ts-check
import { test, expect } from '@playwright/test';
const AxeBuilder = require('@axe-core/playwright').default;

// Ensure required env vars are present and typed as strings for @ts-check
const BACKEND_URL = process.env.BACKEND_URL || 'https://localhost.local/';
const LOGIN = process.env.LOGIN || 'root';
const PASSWORD = process.env.PASSWORD || 'secret';

test('a11y', async ({ page }) => {
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

  // Run Axe tests
  const accessibilityScanResults = await new AxeBuilder({ page }).analyze();
  expect(accessibilityScanResults.violations).toEqual([]);
});
