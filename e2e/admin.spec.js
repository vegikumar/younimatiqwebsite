import { test, expect } from '@playwright/test';
import { crawlPaths } from './helpers/assertPage.js';

const ADMIN_PATHS = [
    '/admin/dashboard',
    '/admin/reports',
    '/admin/orders',
    '/admin/bundles',
    '/admin/students',
    '/admin/tutors',
    '/admin/tutor-training',
    '/admin/tutor-training/reviews',
    '/admin/skills',
    '/admin/match-suggestions',
    '/admin/consultations',
    '/admin/enrollments',
    '/admin/courses',
    '/admin/assignments',
    '/admin/quizzes',
    '/admin/activity-links',
    '/admin/tips',
    '/admin/recordings',
    '/admin/meet-alerts',
    '/admin/feedback',
    '/admin/categories',
    '/admin/age-groups',
    '/admin/community/projects',
    '/admin/community/testimonials',
    '/admin/blogs',
    '/admin/pages',
    '/admin/faqs',
    '/admin/coupons',
    '/admin/popup-banners',
    '/admin/ops/roster',
    '/admin/ops/nps',
    '/admin/contact-messages',
    '/admin/login-history',
    '/admin/chatbot',
    '/admin/platform-settings',
    '/admin/integrations',
    '/admin/settings/seo',
    '/admin/settings/newsletter',
    '/admin/settings/email',
    '/admin/settings/gateways',
    '/admin/settings/pixel',
    '/admin/users',
    '/admin/email',
    '/admin/email-automations',
    '/admin/courses/create',
];

test.describe('Admin panel', () => {
    test('storage session opens admin dashboard', async ({ page }) => {
        await page.goto('/admin/dashboard');
        await expect(page).toHaveURL(/\/admin\//);
        await expect(page.locator('a[href="/admin/dashboard"], a[href="/admin/students"]').first()).toBeVisible();
    });

    test('every admin nav route renders', async ({ page }) => {
        const failures = await crawlPaths(page, ADMIN_PATHS, {
            expectUrl: /\/admin\//,
        });
        expect(failures, JSON.stringify(failures, null, 2)).toEqual([]);
    });

    test('sidebar links navigate without blank pages', async ({ page }) => {
        await page.goto('/admin/dashboard');
        const hrefs = await page.locator('a[href^="/admin/"]').evaluateAll((els) =>
            [...new Set(els.map((a) => a.getAttribute('href')).filter(Boolean))]
        );
        expect(hrefs.length).toBeGreaterThan(10);
        const failures = await crawlPaths(page, hrefs.slice(0, 40));
        expect(failures, JSON.stringify(failures, null, 2)).toEqual([]);
    });
});
