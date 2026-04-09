import { test, expect } from '@playwright/test';

test('add student test (headless)', async ({ page }) => {
  // 1. Login
  await page.goto('http://127.0.0.1:8000/login');
  await page.fill('input[name="login"]', 'superadmin');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');

  // 2. Wait for admin dashboard
  await page.waitForURL('**/admin**');

  // 3. Go to Create Student
  await page.goto('http://127.0.0.1:8000/admin/students/create');

  // 4. Fill in details (Name: test, Password: test12345678)
  const studentName = 'test' + Math.floor(Math.random() * 1000); // Unique name to avoid "already exists" errors
  await page.fill('input[name="name"]', studentName);
  await page.fill('input[name="password"]', 'test12345678');
  
  // 5. Submit
  await page.click('button[type="submit"]');

  // 6. Verify redirect and presence
  await page.waitForURL('**/admin/students');
  await expect(page.locator(`text=${studentName}`)).toBeVisible();
  
  console.log(`Successfully added student: ${studentName}`);
});
