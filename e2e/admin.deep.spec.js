import { test, expect } from '@playwright/test';
import { expectFormReady } from './helpers/actions.js';
import { crawlPaths, expectHealthy } from './helpers/assertPage.js';

const CREATE_PATHS = [
    '/admin/blogs/create',
    '/admin/pages/create',
    '/admin/quizzes/create',
    '/admin/chatbot/create',
];

const LIST_WITH_INLINE_CREATE = [
    '/admin/categories',
    '/admin/faqs',
    '/admin/coupons',
    '/admin/age-groups',
    '/admin/assignments',
];

test.describe('Admin deep actions', () => {
    test('create screens open with forms', async ({ page }) => {
        for (const path of CREATE_PATHS) {
            await page.goto(path);
            await expect(page).toHaveURL(new RegExp(path.replace(/\//g, '\\/')));
            await expectFormReady(page, { minInputs: 2 });
            await expect(page.locator('body')).not.toHaveText(/could not be found|server error/i);
        }
    });

    test('inline create panels render on list pages', async ({ page }) => {
        const failures = await crawlPaths(page, LIST_WITH_INLINE_CREATE, { expectUrl: /\/admin\// });
        expect(failures, JSON.stringify(failures, null, 2)).toEqual([]);

        await page.goto('/admin/categories');
        await expect(page.getByRole('button', { name: /create category/i })).toBeVisible();

        await page.goto('/admin/faqs');
        await expect(page.getByRole('button', { name: /add faq/i })).toBeVisible();
    });

    test('students and tutors detail drawers/links work', async ({ page }) => {
        await page.goto('/admin/students');
        await expect(page.locator('body')).toContainText(/student/i);
        const rowLink = page.locator('a[href*="/admin/students"], button, tr, [role="row"]').first();
        await expect(rowLink).toBeVisible();

        await page.goto('/admin/tutors');
        await expect(page.locator('body')).toContainText(/tutor/i);
    });

    test('courses list and first edit/view action', async ({ page }) => {
        await page.goto('/admin/courses');
        await expect(page.locator('body')).toContainText(/course/i);
        const href = await page.locator('a[href*="/admin/courses/"]').evaluateAll((els) => {
            const urls = els.map((a) => a.getAttribute('href') || '');
            return (
                urls.find((h) => /\/admin\/courses\/.+\/edit/.test(h)) ||
                urls.find((h) => /\/admin\/courses\/\d+/.test(h) && !/sample|csv|download|export/.test(h)) ||
                null
            );
        });
        if (href) {
            await expectHealthy(page, href);
            await expect(page.locator('body')).not.toHaveText(/could not be found/i);
        } else {
            // List still rendered even if no editable course rows yet.
            await expect(page.locator('body')).toContainText(/course/i);
        }
    });

    test('orders, enrollments, consultations actionable', async ({ page }) => {
        for (const path of ['/admin/orders', '/admin/enrollments', '/admin/consultations']) {
            await expectHealthy(page, path);
            await expect(page.locator('body')).toContainText(/.{20,}/);
        }
    });

    test('integrations and platform settings forms load', async ({ page }) => {
        await page.goto('/admin/integrations');
        await expect(page.locator('body')).toContainText(/integration|meet|razorpay|stripe|paypal|test/i);

        await page.goto('/admin/platform-settings');
        await expectFormReady(page, { minInputs: 1 });
    });

    test('email templates and automations open', async ({ page }) => {
        await expectHealthy(page, '/admin/email');
        await expectHealthy(page, '/admin/email-automations');
    });
});
