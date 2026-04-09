import { test, expect } from '@playwright/test';

test.describe('Visual Consistency: Teacher vs Student Notes', () => {
    test('Teacher student detail vs Student notes page', async ({ page }) => {
        // 1. Login as Teacher
        await page.goto('/login');
        await page.fill('input[name="login"]', 'Teacher Demo');
        await page.fill('input[name="password"]', 'password');
        await page.click('button:has-text("Login")');
        await page.waitForURL('**/teacher**');

        // 2. Go to a specific student (Student Demo)
        await page.goto('/teacher/students');
        await page.screenshot({ path: 'test-results/roster_debug.png' });
        // The link has an aria-label "Buka murid Student Demo"
        const studentLink = page.getByLabel('Buka murid Student Demo');
        await studentLink.click();
        
        // 3. Ensure there is at least one note so we can see the card
        const noteText = `Automated Test Note ${Date.now()}`;
        const textarea = page.locator('textarea');
        if (!await textarea.isVisible()) {
            await page.click('button:has-text("Catatan")');
        }
        await textarea.fill(noteText);
        await page.click('button:has-text("Buat catatan")');
        
        // Wait for the note card to appear
        await page.waitForSelector('article');
        const teacherCard = page.locator('article').filter({ hasText: noteText }).first();
        await teacherCard.scrollIntoViewIfNeeded();
        await page.screenshot({ path: 'test-results/teacher_view_note.png' });
        
        // Verify teacher card has modern classes
        const teacherClasses = await teacherCard.getAttribute('class');
        expect(teacherClasses).toContain('rounded-3xl');
        expect(teacherClasses).toContain('bg-white');
        expect(teacherClasses).toContain('shadow');

        // Logout
        await page.click('summary:has-text("Teacher Demo")');
        await page.click('button:has-text("Logout")');
        await page.waitForURL('**/login**');

        // 4. Login as Student Demo
        await page.fill('input[name="login"]', 'Student Demo');
        await page.fill('input[name="password"]', 'password');
        await page.click('button:has-text("Login")');
        // Student is redirected to /student/notes
        await page.waitForURL('**/student/notes**');

        // 5. Navigate to "Catatan Guru" tab (should be default, but let's be sure)
        await page.click('a:has-text("Catatan Guru")');

        // Wait for the same note to appear in student view
        await page.waitForSelector('article');
        const studentCard = page.locator('article').filter({ hasText: noteText }).first();
        await studentCard.scrollIntoViewIfNeeded();
        await page.screenshot({ path: 'test-results/student_view_note.png' });

        // Compare existence of key visual elements (rounded-3xl, shadow, etc.)
        const studentClasses = await studentCard.getAttribute('class');
        expect(studentClasses).toContain('rounded-3xl');
        expect(studentClasses).toContain('bg-white');
        expect(studentClasses).toContain('shadow');
    });
});
