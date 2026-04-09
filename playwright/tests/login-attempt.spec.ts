import { test, expect } from '@playwright/test';

test('login attempt as teacher', async ({ page }) => {
  // Use the specific URL provided
  await page.goto('http://127.0.0.1:8000/login');

  // Fill in the login form
  // The form uses name="login" for the username/name field.
  await page.fill('input[name="login"]', 'Teacher Demo');
  await page.fill('input[name="password"]', 'password');

  // Click the submit button
  await page.click('button[type="submit"]');

  // Wait for the URL to change - we'll be more flexible with the target URL
  // to see where it actually lands if the login is successful.
  try {
    await page.waitForURL('**/teacher**', { timeout: 10000 });
    console.log('Login successful! Redirected to teacher area.');
  } catch (error) {
    // If it didn't redirect to /teacher, let's see if there's an error message
    const errorText = await page.locator('.error, .alert, .text-red-500').first().innerText().catch(() => 'No error message found');
    console.error('Login failed or redirected elsewhere. Current URL:', page.url());
    console.error('Error message on page:', errorText);
    
    // Take a screenshot for debugging (it will be in the test-results folder)
    await page.screenshot({ path: 'playwright/login-failure.png' });
    throw new Error(`Login failed. Check playwright/login-failure.png. URL: ${page.url()}`);
  }

  // Final verification
  await expect(page).toHaveURL(/\/teacher/);
});
