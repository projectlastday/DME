import { test, expect } from '@playwright/test';

test('debug student creation failure', async ({ page }) => {
  const baseUrl = 'http://localhost:8000';

  // 1. Login
  await page.goto(`${baseUrl}/login`);
  await page.fill('input[name="login"]', 'superadmin');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');

  // 2. Wait for admin dashboard
  await page.waitForURL('**/admin**');
  console.log('Login successful.');

  // 3. Go to Create Student
  await page.goto(`${baseUrl}/admin/students/create`);

  // 4. Fill in details
  const uniqueName = 'test-' + Math.floor(Math.random() * 1000);
  await page.fill('input[name="name"]', uniqueName);
  await page.fill('input[name="password"]', 'test12345678');
  console.log(`Adding student: ${uniqueName}`);

  // 5. Submit and wait for redirect
  await page.click('button[type="submit"]');
  
  try {
    // Wait for the redirect back to the index
    await page.waitForURL('**/admin/students', { timeout: 10000 });
    console.log(`Success: ${uniqueName} added.`);
  } catch (error) {
    console.log('Test failed. Current URL:', page.url());
    // Take a screenshot to see what's on the screen
    await page.screenshot({ path: 'playwright/failure.png' });
    console.log('Screenshot saved to playwright/failure.png');
    throw error;
  }
});
