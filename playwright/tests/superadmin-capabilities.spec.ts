import { test, expect } from '@playwright/test';

test.describe('Superadmin Capabilities', () => {
    test.beforeEach(async ({ page }) => {
        // Login before each test
        await page.goto('/login');
        await page.fill('input[name="login"]', 'superadmin');
        await page.fill('input[name="password"]', '123');
        await page.click('button:has-text("Login")');
        await expect(page).toHaveURL('/admin');
    });

    test('Manage Teachers (Create, Edit, Delete)', async ({ page }) => {
        const uniqueTeacher = `Teacher ${Date.now()}`;
        // 1. View Teachers List
        await page.goto('/admin/teachers');
        await expect(page).toHaveURL('/admin/teachers');

        // 2. Create Teacher
        await page.click('a:has-text("Tambah guru")');
        await expect(page).toHaveURL('/admin/teachers/create');
        await page.fill('input[name="name"]', uniqueTeacher);
        await page.fill('input[name="password"]', 'password123');
        await page.click('button:has-text("Tambah guru")');
        await expect(page).toHaveURL('/admin/teachers');
        await expect(page.locator(`text=${uniqueTeacher}`)).toBeVisible();

        // 3. Edit Teacher
        // Click Info first to get to detail page
        await page.locator('tr').filter({ hasText: uniqueTeacher }).first().locator('a:has-text("Info")').click();
        await expect(page.locator('h1')).toContainText('Detail Guru');
        await page.click('a:has-text("Ubah")');
        
        // Now on edit page
        await expect(page.locator('h1')).toContainText('Ubah Guru');
        const editedTeacher = `${uniqueTeacher} Edited`;
        await page.fill('input[name="name"]', editedTeacher);
        await page.click('button:has-text("Simpan guru")'); 
        
        // After save, it redirects back to edit page
        await expect(page.locator('h1')).toContainText('Ubah Guru');
        await expect(page.locator('input[name="name"]')).toHaveValue(editedTeacher);

        // 4. Delete Teacher
        // Go back to list to find the record again for deletion
        await page.goto('/admin/teachers');
        await page.locator('tr').filter({ hasText: editedTeacher }).first().locator('a:has-text("Info")').click();
        await expect(page.locator('h1')).toContainText('Detail Guru');
        
        // Open delete dialog
        await page.click('button:has-text("Hapus")');
        // Click Hapus inside the dialog
        await page.locator('[data-teacher-dialog] button:has-text("Hapus")').click();
        
        await expect(page).toHaveURL('/admin/teachers');
        await expect(page.locator(`text=${editedTeacher}`)).not.toBeVisible();
    });

    test('Manage Students (Create, Edit, Delete)', async ({ page }) => {
        const uniqueStudent = `Student ${Date.now()}`;
        // 1. View Students List
        await page.goto('/admin/students');
        await expect(page).toHaveURL('/admin/students');

        // 2. Create Student
        await page.click('a:has-text("Tambah murid")');
        await expect(page).toHaveURL('/admin/students/create');
        await page.fill('input[name="name"]', uniqueStudent);
        await page.fill('input[name="password"]', 'password123');
        await page.click('button:has-text("Tambah murid")');
        await expect(page).toHaveURL('/admin/students');
        await expect(page.locator(`text=${uniqueStudent}`)).toBeVisible();

        // 3. Edit Student
        // Click Info to get to detail page
        await page.locator('tr').filter({ hasText: uniqueStudent }).first().locator('a:has-text("Info")').click();
        await expect(page.locator('h1')).toContainText('Detail Murid');
        await page.click('a:has-text("Ubah")');
        
        // Now on edit page
        await expect(page.locator('h1')).toContainText('Ubah Murid');
        const editedStudent = `${uniqueStudent} Edited`;
        await page.fill('input[name="name"]', editedStudent);
        await page.click('button:has-text("Simpan murid")'); 
        
        // After save, it redirects back to edit page
        await expect(page.locator('h1')).toContainText('Ubah Murid');
        await expect(page.locator('input[name="name"]')).toHaveValue(editedStudent);

        // 4. Delete Student
        // Go back to list to find the record again for deletion
        await page.goto('/admin/students');
        await page.locator('tr').filter({ hasText: editedStudent }).first().locator('a:has-text("Info")').click();
        await expect(page.locator('h1')).toContainText('Detail Murid');
        
        // Open delete dialog
        await page.click('button:has-text("Hapus")');
        // Click Hapus inside the dialog
        await page.locator('[data-teacher-dialog] button:has-text("Hapus")').click();
        
        await expect(page).toHaveURL('/admin/students');
        await expect(page.locator(`text=${editedStudent}`)).not.toBeVisible();
    });
});
