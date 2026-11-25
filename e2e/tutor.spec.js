import { test, expect } from '@playwright/test';
import { crawlPaths } from './helpers/assertPage.js';

const TUTOR_PATHS = [
    '/tutor/dashboard',
    '/tutor/profile',
    '/tutor/classes',
    '/tutor/students',
    '/tutor/assigned-classes',
    '/tutor/availability',
    '/tutor/assignments',
    '/tutor/quizzes',
    '/tutor/rewards',
    '/tutor/qa',
    '/tutor/ai-assistant',
    '/tutor/messages',
    '/tutor/announcements',
    '/tutor/growth',
    '/tutor/earnings',
    '/tutor/resources',
    '/tutor/training',
    '/tutor/policy-training',
    '/tutor/analytics',
    '/tutor/payouts',
    '/tutor/sessions',
];

test.describe('Tutor dashboard', () => {
    test('storage session opens tutor portal', async ({ page }) => {
        await page.goto('/tutor/dashboard');
        await expect(page).toHaveURL(/\/tutor\//);
        await expect(page.locator('a[href="/tutor/dashboard"], a[href="/tutor/classes"]').first()).toBeVisible();
    });

    test('every tutor nav route renders', async ({ page }) => {
        const failures = await crawlPaths(page, TUTOR_PATHS, {
            expectUrl: /\/tutor\//,
        });
        expect(failures, JSON.stringify(failures, null, 2)).toEqual([]);
    });

    test('sidebar links navigate without blank pages', async ({ page }) => {
        await page.goto('/tutor/dashboard');
        const hrefs = await page.locator('a[href^="/tutor/"]').evaluateAll((els) =>
            [...new Set(els.map((a) => a.getAttribute('href')).filter(Boolean))]
        );
        expect(hrefs.length).toBeGreaterThan(5);
        const failures = await crawlPaths(page, hrefs.slice(0, 25));
        expect(failures, JSON.stringify(failures, null, 2)).toEqual([]);
    });
});
