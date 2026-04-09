import { test, expect } from '@playwright/test';

test('login as admin and add student skibidibi123 (slow mode)', async ({ page }) => {
  // Set a very long timeout so the test doesn't fail while you are looking at it
  test.setTimeout(0);

  // 1. Go to the login page
  await page.goto('http://127.0.0.1:8000/login');
  console.log('Navigated to login page.');
  await page.waitForTimeout(2000); // Pause to see the login page

  // 2. Fill in admin credentials
  await page.fill('input[name="login"]', 'superadmin');
  console.log('Filled in username.');
  await page.waitForTimeout(1000);
  
  await page.fill('input[name="password"]', 'password');
  console.log('Filled in password.');
  await page.waitForTimeout(1000);

  await page.click('button[type="submit"]');
  console.log('Clicked login button.');
  await page.waitForTimeout(2000); // Pause to see redirect

  // 3. Admin dashboard
  await page.waitForURL('**/admin**');
  console.log('Admin logged in successfully!');
  await page.waitForTimeout(2000);

  // 4. Navigate to Students index
  await page.goto('http://127.0.0.1:8000/admin/students');
  console.log('Navigated to students index.');
  await page.waitForTimeout(2000);

  // 5. Navigate to create page
  await page.goto('http://127.0.0.1:8000/admin/students/create');
  console.log('Navigated to student create page.');
  await page.waitForTimeout(2000);

  // 6. Fill in the new student details
  // Note: Using 8+ characters to pass validation
  const newStudent = 'skibidibi123';
  await page.fill('input[name="name"]', newStudent);
  console.log(`Filled in student name: ${newStudent}`);
  await page.waitForTimeout(1000);

  await page.fill('input[name="password"]', newStudent);
  console.log(`Filled in student password: ${newStudent}`);
  await page.waitForTimeout(1000);

  // 7. Submit the form
  await page.click('button[type="submit"]');
  console.log('Submitted student creation form.');
  await page.waitForTimeout(2000);

  // 8. Wait for redirect back to students index
  await page.waitForURL('**/admin/students');
  console.log('Redirected back to students index.');
  await page.waitForTimeout(2000);

  // 9. Verify the new student is visible in the list
  await expect(page.locator(`text=${newStudent}`)).toBeVisible();
  console.log(`Student "${newStudent}" is visible in the list!`);

  // 10. Stay open for the user to see
  console.log('Test complete. Browser will stay open.');
  await page.pause();
});
