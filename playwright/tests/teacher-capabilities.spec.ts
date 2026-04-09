import { test, expect } from '@playwright/test';

test.describe('Teacher Capabilities', () => {
    const testStudentName = `Student ${Date.now()}`;

    test.beforeEach(async ({ page }) => {
        // Login as Teacher Demo
        await page.goto('/login');
        await page.fill('input[name="login"]', 'Teacher Demo');
        await page.fill('input[name="password"]', 'password');
        await page.click('button:has-text("Login")');
        // Redirection should be to teacher dashboard or home
        // Let's check the URL or content
        await page.waitForURL('**/teacher**');
    });

    test('Teacher Dashboard and Students', async ({ page }) => {
        // 1. Dashboard visibility - Teacher's home is "Daftar Murid"
        await expect(page.locator('h1')).toContainText('Daftar Murid');
    });

    test('Create and Manage Student Note', async ({ page }) => {
        // Go to students list
        await page.goto('/teacher/students');
        
        // Find "Student Demo" and click it
        // The link text is actually "Buka murid Student Demo" or similar in snapshot
        const studentLink = page.locator('a:has-text("Student Demo")').first();
        await studentLink.click();
        
        // Should be on student detail page
        await expect(page.locator('h1')).toContainText('Student Demo');
        
        // Add a note - based on snapshot we might need to click a "Catatan" button to show the form
        // or the form is already there under "Buat catatan"
        
        const noteText = `Test Note ${Date.now()}`;
        
        // Let's try to fill the textarea. If it's hidden, we might need to click "Catatan" first
        const textarea = page.locator('textarea');
        if (!await textarea.isVisible()) {
            await page.click('button:has-text("Catatan")');
        }
        
        await textarea.fill(noteText);
        
        // Click "Tambah" or similar button. Based on routes it's a POST.
        // We'll look for a button near the textarea or with a common label.
        await page.keyboard.press('Enter'); // Fallback if button is hard to find
        // Or find the button inside the main area
        await page.locator('main button:has-text("Simpan"), main button:has-text("Tambah")').first().click();
        
        // Verify note appears
        await expect(page.locator(`text=${noteText}`)).toBeVisible();
    });
});
