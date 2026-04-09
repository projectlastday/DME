import { chromium } from '@playwright/test';

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage({ viewport: { width: 691, height: 767 } });
await page.goto('http://127.0.0.1:8000/login');
await page.fill('input[name="login"]', 'qwe');
await page.fill('input[name="password"]', 'qwe');
await page.click('button[type="submit"]');
await page.waitForLoadState('networkidle');
await page.goto('http://127.0.0.1:8000/teacher/students/3');
await page.waitForTimeout(1500);
await page.locator('button[data-image-lightbox-open]').first().click();
await page.waitForTimeout(300);
await page.screenshot({ path: 'test-results/lightbox_blur_fix3.png', fullPage: true });
await browser.close();
