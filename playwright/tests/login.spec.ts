import { test, expect } from '@playwright/test';

test('login page is accessible', async ({ page }) => {
  // Go to the login page
  await page.goto('/login');

  // Check if the page title is correct (adjust to your project's title)
  // await expect(page).toHaveTitle(/DME/);

  // Verify the login form elements are present
  await expect(page.locator('input[name="username"]')).toBeVisible();
  await expect(page.locator('input[name="password"]')).toBeVisible();
  await expect(page.locator('button[type="submit"]')).toBeVisible();
});

test('redirects guest from root to login', async ({ page }) => {
  // Visit the root URL as a guest
  await page.goto('/');

  // Expect to be redirected to the login page
  await expect(page).toHaveURL(/\/login/);
});
