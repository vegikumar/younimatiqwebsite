import { test, expect } from '@playwright/test';
import { crawlPaths, expectHealthy } from './helpers/assertPage.js';

const PUBLIC_PATHS = [
    '/',
    '/courses',
    '/about',
    '/contact',
    '/pricing',
    '/blog',
    '/faq',
    '/community',
    '/become-a-tutor',
    '/login/student',
    '/login/tutor',
    '/login/admin',
    '/login/parent',
    '/register/student',
    '/register/tutor',
    '/register/parent',
];

test.describe('Public website', () => {
    test('core pages render without blank/fatal errors', async ({ page }) => {
        const failures = await crawlPaths(page, PUBLIC_PATHS);
        expect(failures, JSON.stringify(failures, null, 2)).toEqual([]);
    });

    test('home CTAs and primary nav work', async ({ page }) => {
        await page.goto('/');
        await expect(page.locator('body')).toContainText(/younimatiq/i);

        // Courses nav
        await page.locator('a[href="/courses"]').first().click();
        await expect(page).toHaveURL(/\/courses/);
        await expect(page.locator('body')).not.toHaveText(/this page could not be found/i);

        // Open first real course detail link (not category/grade listings)
        const courseHref = await page.locator('a[href^="/courses/"]').evaluateAll((els) => {
            const hit = els
                .map((a) => a.getAttribute('href') || '')
                .find((h) => /^\/courses\/[^/?#]+$/.test(h) && !/\/(category|grades|age)\b/.test(h));
            return hit || null;
        });
        if (courseHref) {
            await page.goto(courseHref);
            await expect(page).toHaveURL(/\/courses\//);
            await expect(page.locator('body')).toContainText(/.{20,}/);
        }
    });

    test('student login page submits validation', async ({ page }) => {
        await page.goto('/login/student');
        await page.locator('#email').fill('not-a-user@example.com');
        await page.locator('#password').fill('wrong-password');
        await page.getByRole('button', { name: /start your adventure|sign in/i }).click();
        await expect(page.locator('body')).toContainText(/credentials|not match|records/i);
        await expect(page).toHaveURL(/\/login\/student/);
    });

    test('checkout path for published course loads', async ({ page }) => {
        await page.goto('/courses');
        const courseHref = await page.locator('a[href^="/courses/"]').evaluateAll((els) => {
            const hit = els
                .map((a) => a.getAttribute('href') || '')
                .find((h) => /^\/courses\/[^/]+$/.test(h) && !h.includes('category') && !h.includes('grades'));
            return hit || null;
        });
        test.skip(!courseHref, 'No course links on catalog');

        await page.goto(courseHref);
        const pay = page.locator('a[href^="/checkout/"]').first();
        if (await pay.count()) {
            const href = await pay.getAttribute('href');
            await expectHealthy(page, href);
        } else {
            // Enrolled-only page still must render.
            await expect(page.locator('body')).toContainText(/.+/);
        }
    });
});
