import { test, expect } from '@playwright/test';
import { expectHealthy } from './helpers/assertPage.js';
import { expectFormReady } from './helpers/actions.js';

test.describe('Tutor whole-site flows', () => {
    test('dashboard shortcuts navigate', async ({ page }) => {
        await page.goto('/tutor/dashboard');
        const hrefs = await page.locator('a[href^="/tutor/"]').evaluateAll((els) =>
            [...new Set(els.map((a) => a.getAttribute('href')).filter(Boolean))].slice(0, 10)
        );
        expect(hrefs.length).toBeGreaterThan(3);
        for (const href of hrefs) {
            await expectHealthy(page, href);
            await expect(page.locator('body')).not.toHaveText(/Page not found \(404\)/i);
        }
    });

    test('earnings growth resources training pages', async ({ page }) => {
        for (const path of ['/tutor/earnings', '/tutor/growth', '/tutor/resources', '/tutor/training', '/tutor/policy-training', '/tutor/rewards', '/tutor/qa', '/tutor/ai-assistant']) {
            await expectHealthy(page, path);
        }
    });

    test('availability form can be interacted with', async ({ page }) => {
        await page.goto('/tutor/availability');
        await expectFormReady(page);
        const checkbox = page.locator('input[type="checkbox"]').first();
        const time = page.locator('input[type="time"], input[type="number"], select').first();
        if (await checkbox.count()) {
            await checkbox.click({ force: true });
        }
        await expect(time.or(page.locator('button[type="submit"]')).first()).toBeVisible();
    });

    test('assigned classes create-course entry if present', async ({ page }) => {
        await page.goto('/tutor/assigned-classes');
        const create = page.locator('a[href="/tutor/courses/create"], a:has-text("Create")').first();
        if (await create.count()) {
            await create.click();
            await expect(page).toHaveURL(/\/tutor\/courses\/create/);
            await expectFormReady(page);
        }
    });

    test('messages compose/list UI', async ({ page }) => {
        await page.goto('/tutor/messages');
        await expect(page.locator('body')).toContainText(/message|inbox|conversation|chat|no |empty|student/i);
    });

    test('analytics payouts sessions feedback', async ({ page }) => {
        for (const path of ['/tutor/analytics', '/tutor/payouts', '/tutor/sessions']) {
            await expectHealthy(page, path);
        }
        // Feedback create requires a booking; bare URL should redirect to classes (not 404)
        await page.goto('/tutor/feedback/create');
        await expect(page).toHaveURL(/\/tutor\/(classes|feedback\/create)/);
        await expect(page.locator('body')).not.toHaveText(/Page not found \(404\)/i);

        await page.goto('/tutor/classes');
        const feedbackHref = await page.locator('a[href*="/tutor/feedback/create"]').evaluateAll((els) => {
            const hit = els.map((a) => a.getAttribute('href') || '').find((h) => /booking_id=/.test(h));
            return hit || null;
        });
        if (feedbackHref) {
            await page.goto(feedbackHref);
            // Form opens when pending; already-submitted feedback redirects back to classes
            await expect(page).toHaveURL(/\/tutor\/(feedback\/create|classes)/);
            await expect(page.locator('body')).not.toHaveText(/Page not found \(404\)|server error/i);
            if (/\/feedback\/create/.test(page.url())) {
                await expectFormReady(page);
            }
        }
    });

    test('quiz create and announcements compose', async ({ page }) => {
        await page.goto('/tutor/quizzes/create');
        await expectFormReady(page).catch(async () => {
            await expect(page.locator('body')).toContainText(/quiz|question|create|form/i);
        });
        await page.goto('/tutor/announcements');
        await expect(page.locator('body')).toContainText(/announce|post|class|student|no |empty|create/i);
        const compose = page.locator('textarea, input[name="title"], button:has-text("Create"), button:has-text("Post")').first();
        if (await compose.count()) {
            await expect(compose).toBeVisible();
        }
    });

    test('profile save keeps tutor on profile', async ({ page }) => {
        await page.goto('/tutor/profile');
        await expectFormReady(page);
        const name = page.locator('input[name="name"], #name').first();
        if (await name.count()) {
            const val = (await name.inputValue()) || 'Tutor Demo';
            await name.fill(val);
        }
        const save = page.locator('button[type="submit"], button:has-text("Save")').first();
        if (await save.count()) {
            await save.click();
            await page.waitForLoadState('networkidle').catch(() => {});
        }
        await expect(page).toHaveURL(/\/tutor\/profile/);
        await expect(page.locator('body')).not.toHaveText(/server error|could not be found/i);
    });
});
