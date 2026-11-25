import { test, expect } from '@playwright/test';
import { loginStudent, loginTutor, loginAdmin, creds } from './helpers/auth.js';

test.describe('Cross-role auth and shell flows', () => {
    test('guest hitting student dashboard is sent to login (full load)', async ({ page }) => {
        await page.goto('/student/dashboard');
        await expect(page).toHaveURL(/\/login\/student/);
        await expect(page.locator('#email')).toBeVisible();
    });

    test('student session cannot open admin dashboard', async ({ browser }) => {
        const context = await browser.newContext({
            storageState: 'e2e/.auth/student.json',
            ignoreHTTPSErrors: true,
        });
        const page = await context.newPage();
        await page.goto('/admin/dashboard');
        // Should bounce to admin login or forbidden/public — not render admin shell as student
        await expect(page).not.toHaveURL(/\/admin\/dashboard$/);
        await context.close();
    });

    test('tutor session cannot open student courses as owner shell mixup', async ({ browser }) => {
        const context = await browser.newContext({
            storageState: 'e2e/.auth/tutor.json',
            ignoreHTTPSErrors: true,
        });
        const page = await context.newPage();
        await page.goto('/student/dashboard');
        // Tutor guard should not keep a student dashboard session
        const url = page.url();
        expect(url.includes('/login') || url.includes('/tutor') || !url.includes('/student/dashboard')).toBeTruthy();
        await context.close();
    });

    test('fresh student login still lands in portal after public visit', async ({ page }) => {
        await page.goto('/courses');
        await loginStudent(page);
        await expect(page).toHaveURL(/\/student\//);
        await expect(page.locator('a[href="/student/dashboard"], a[href="/student/courses"]').first()).toBeVisible();
    });

    test('parent auth file opens parent home', async ({ browser }) => {
        const context = await browser.newContext({
            storageState: 'e2e/.auth/parent.json',
            ignoreHTTPSErrors: true,
        });
        const page = await context.newPage();
        await page.goto('/parent/dashboard');
        await expect(page).toHaveURL(/\/parent\//);
        await context.close();
    });

    test('admin auth file opens admin and public home still works', async ({ browser }) => {
        const context = await browser.newContext({
            storageState: 'e2e/.auth/admin.json',
            ignoreHTTPSErrors: true,
        });
        const page = await context.newPage();
        await page.goto('/admin/dashboard');
        await expect(page).toHaveURL(/\/admin\/dashboard/);
        await page.goto('/');
        await expect(page.locator('body')).toContainText(/younimatiq|course|trial/i);
        await context.close();
    });

    test('parent session cannot open admin dashboard', async ({ browser }) => {
        const context = await browser.newContext({
            storageState: 'e2e/.auth/parent.json',
            ignoreHTTPSErrors: true,
        });
        const page = await context.newPage();
        await page.goto('/admin/dashboard');
        await expect(page).not.toHaveURL(/\/admin\/dashboard$/);
        await context.close();
    });

    test('payment cancel and success pages render for guests', async ({ page }) => {
        await page.goto('/payment/cancel');
        await expect(page.locator('body')).toContainText(/cancel|payment|checkout|try again|course|home/i);
        await expect(page.locator('body')).not.toHaveText(/server error|Page not found \(404\)/i);
        await page.goto('/payment/success');
        await expect(page.locator('body')).toContainText(/success|payment|enroll|thank|course|dashboard|home/i);
        await expect(page.locator('body')).not.toHaveText(/server error/i);
    });

    test('fresh tutor login after public visit stays in tutor shell', async ({ page }) => {
        await page.goto('/become-a-tutor');
        await loginTutor(page);
        await expect(page).toHaveURL(/\/tutor\//);
        await expect(page.locator('a[href="/tutor/dashboard"], a[href="/tutor/classes"]').first()).toBeVisible();
    });
});
