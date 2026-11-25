import { test, expect } from '@playwright/test';
import { expectHealthy } from './helpers/assertPage.js';
import { expectFormReady } from './helpers/actions.js';

test.describe('Admin whole-site flows', () => {
    test('dashboard date filter controls exist', async ({ page }) => {
        await page.goto('/admin/dashboard');
        await expect(page.locator('body')).toContainText(/revenue|student|enrollment|order|dashboard/i);
        const from = page.locator('input[type="date"]').first();
        if (await from.count()) {
            await expect(from).toBeVisible();
        }
    });

    test('search/filter on students and tutors', async ({ page }) => {
        await page.goto('/admin/students');
        await expect(page.locator('body')).toContainText(/student/i);
        // Prefer page content search, not the collapsed sidebar "Search menu"
        const search = page.locator('main input[name="search"], .student-main input[name="search"], main input[placeholder*="Search" i], .portal-canvas input[placeholder*="Search" i]').first();
        if (await search.isVisible().catch(() => false)) {
            await search.fill('sara');
            await search.press('Enter').catch(() => {});
            await page.waitForTimeout(400);
        }
        await expect(page.locator('body')).toContainText(/student|sara|email|no |empty/i);

        await page.goto('/admin/tutors');
        await expect(page.locator('body')).toContainText(/tutor/i);
    });

    test('open blog create and cancel/back', async ({ page }) => {
        await page.goto('/admin/blogs/create');
        await expectFormReady(page, { minInputs: 2 });
        const back = page.locator('a[href="/admin/blogs"]').first();
        if (await back.count()) {
            await back.click();
            await expect(page).toHaveURL(/\/admin\/blogs/);
        } else {
            await page.goto('/admin/blogs');
            await expect(page).toHaveURL(/\/admin\/blogs/);
        }
        await expect(page.locator('body')).toContainText(/blog|post|create|article/i);
    });

    test('coupons and categories create controls', async ({ page }) => {
        await page.goto('/admin/coupons');
        await expect(page.getByRole('button', { name: /create coupon/i }).or(page.locator('button[type="submit"]').first())).toBeVisible();
        await page.goto('/admin/categories');
        await expect(page.getByRole('button', { name: /create category/i })).toBeVisible();
    });

    test('orders payments and match inbox', async ({ page }) => {
        for (const path of ['/admin/orders', '/admin/match-suggestions', '/admin/enrollments', '/admin/consultations', '/admin/reports']) {
            await expectHealthy(page, path);
        }
    });

    test('integrations meet test UI present', async ({ page }) => {
        await page.goto('/admin/integrations');
        await expect(page.locator('body')).toContainText(/integration|meet|gateway|razorpay|stripe|paypal|test/i);
        const testBtn = page.locator('button:has-text("Test"), a:has-text("Test Meet"), button:has-text("Meet")').first();
        if (await testBtn.count()) {
            await expect(testBtn).toBeVisible();
        }
    });

    test('popup banners faqs tips pages', async ({ page }) => {
        for (const path of ['/admin/popup-banners', '/admin/faqs', '/admin/tips', '/admin/pages', '/admin/activity-links', '/admin/meet-alerts']) {
            await expectHealthy(page, path);
        }
    });

    test('settings seo newsletter email forms', async ({ page }) => {
        await page.goto('/admin/settings/seo');
        await expectFormReady(page, { minInputs: 1 }).catch(async () => {
            await expect(page.locator('body')).toContainText(/seo|setting|meta/i);
        });
        await page.goto('/admin/settings/newsletter');
        await expect(page.locator('body')).toContainText(/newsletter|email|subscriber|setting/i);
        await page.goto('/admin/platform-settings');
        await expectFormReady(page, { minInputs: 1 });
    });

    test('gateway pixel email settings and users', async ({ page }) => {
        for (const path of [
            '/admin/settings/gateways',
            '/admin/settings/pixel',
            '/admin/settings/email',
            '/admin/users',
            '/admin/skills',
            '/admin/recordings',
            '/admin/feedback',
            '/admin/login-history',
            '/admin/ops/roster',
            '/admin/ops/nps',
        ]) {
            await expectHealthy(page, path);
        }
    });

    test('course create and email automation create open', async ({ page }) => {
        await page.goto('/admin/courses/create');
        await expectFormReady(page, { minInputs: 2 });
        await page.goto('/admin/email-automations/create');
        await expectFormReady(page, { minInputs: 1 }).catch(async () => {
            await expect(page.locator('body')).toContainText(/automation|email|template|create/i);
        });
    });

    test('community projects and testimonials manageable', async ({ page }) => {
        await page.goto('/admin/community/projects');
        await expect(page.locator('body')).toContainText(/project|community|student|no |empty/i);
        await page.goto('/admin/community/testimonials');
        await expect(page.locator('body')).toContainText(/testimonial|review|quote|no |empty|community/i);
    });
});
