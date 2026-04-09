import { test, expect } from '@playwright/test';

test('login as superadmin and add student (final try)', async ({ page }) => {
  // Use localhost to match the config's baseURL
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
  // We'll use a unique name in case "test" already exists in your database
  const uniqueName = 'test-' + Math.floor(Math.random() * 1000);
  await page.fill('input[name="name"]', uniqueName);
  await page.fill('input[name="password"]', 'test12345678');
  console.log(`Adding student: ${uniqueName}`);

  // 5. Submit and wait for redirect
  await page.click('button[type="submit"]');
  
  // Wait for the redirect back to the index
  await page.waitForURL('**/admin/students');
  
  // 6. Confirm the student is in the list
  await expect(page.locator(`text=${uniqueName}`)).toBeVisible();
  console.log(`Success: ${uniqueName} added and verified.`);
});
