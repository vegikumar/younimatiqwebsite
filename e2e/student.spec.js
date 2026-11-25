import { test, expect } from '@playwright/test';
import { crawlPaths } from './helpers/assertPage.js';

const STUDENT_PATHS = [
    '/student/dashboard',
    '/student/courses',
    '/student/bookings',
    '/student/recordings',
    '/student/activities',
    '/student/assignments',
    '/student/quizzes',
    '/student/python',
    '/student/rewards',
    '/student/career',
    '/student/qa',
    '/student/reviews',
    '/student/profile',
    '/student/wishlist',
    '/student/payment-history',
    '/student/referrals',
    '/student/settings',
];

test.describe('Student dashboard', () => {
    test('storage session opens student portal shell', async ({ page }) => {
        await page.goto('/student/dashboard');
        await expect(page).toHaveURL(/\/student\//);
        await expect(page.locator('body')).toContainText(/dashboard|courses|learning/i);
        await expect(page.locator('a[href="/student/dashboard"], a[href="/student/courses"]').first()).toBeVisible();
    });

    test('every student nav route renders', async ({ page }) => {
        const failures = await crawlPaths(page, STUDENT_PATHS, {
            expectUrl: /\/student\//,
        });
        expect(failures, JSON.stringify(failures, null, 2)).toEqual([]);
    });

    test('sidebar links navigate without blank pages', async ({ page }) => {
        await page.goto('/student/dashboard');
        const hrefs = await page.locator('a[href^="/student/"]').evaluateAll((els) =>
            [...new Set(els.map((a) => a.getAttribute('href')).filter(Boolean))]
        );
        expect(hrefs.length).toBeGreaterThan(5);

        const failures = await crawlPaths(page, hrefs.slice(0, 20));
        expect(failures, JSON.stringify(failures, null, 2)).toEqual([]);
    });

    test('public catalog from student uses full navigation', async ({ page }) => {
        await page.goto('/student/courses');
        const browse = page.locator('a[href="/courses"]').first();
        if (await browse.count()) {
            await browse.click();
            await expect(page).toHaveURL(/\/courses/);
            await expect(page.locator('body')).toContainText(/course/i);
        }
    });

    test('logout returns to public home', async ({ page }) => {
        await page.goto('/student/dashboard');
        const logout = page.locator('button:has-text("Sign out"), a:has-text("Sign out"), button:has-text("Logout"), a:has-text("Logout")').first();
        if (await logout.count()) {
            await logout.click();
        } else {
            await page.goto('/logout/student', { waitUntil: 'domcontentloaded' });
        }
        await page.waitForURL((url) => !url.pathname.startsWith('/student'), { timeout: 30_000 });
        await expect(page).not.toHaveURL(/\/student\//);
    });
});
