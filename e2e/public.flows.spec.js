import { test, expect } from '@playwright/test';
import { expectHealthy, crawlPaths } from './helpers/assertPage.js';
import { expectFormReady } from './helpers/actions.js';

const EXTRA_PUBLIC = [
    '/blog',
    '/community',
    '/become-a-tutor',
    '/back-to-school',
    '/faq',
    '/about',
    '/contact',
    '/pricing',
];

test.describe('Public whole-site flows', () => {
    test('extra marketing pages render', async ({ page }) => {
        const failures = await crawlPaths(page, EXTRA_PUBLIC);
        expect(failures, JSON.stringify(failures, null, 2)).toEqual([]);
    });

    test('free trial modal accepts field input and rejects incomplete submit', async ({ page }) => {
        await page.goto('/');
        await page.waitForFunction(() => typeof window.openLeadModal === 'function', null, { timeout: 20_000 });
        await page.evaluate(() => window.openLeadModal({ preventDefault() {} }, 'free_class'));
        await expect(page.locator('#leadModal')).toHaveClass(/open/);

        await page.locator('#leadChildName1').fill('E2E Child');
        await page.locator('#leadChildAge1').fill('10');
        await page.locator('#leadModalPhone').fill('+12025550100');
        await page.locator('#leadModalEmail').fill('e2e.parent+trial@example.com');

        const min = await page.locator('#consultDate').getAttribute('min');
        expect(min).toBeTruthy();
        await page.locator('#consultDate').fill(min);

        await page.locator('#leadFormSubmit').click();
        // Incomplete required fields (grade/category/time) keep modal open
        await expect(page.locator('#leadModal')).toHaveClass(/open/);
        await page.locator('#leadModalClose').click();
    });

    test('blog index opens a post when available', async ({ page }) => {
        await page.goto('/blog');
        const post = page.locator('a[href^="/blog/"]').first();
        if (await post.count()) {
            const href = await post.getAttribute('href');
            await expectHealthy(page, href);
            await expect(page.locator('body')).toContainText(/.{40,}/);
        } else {
            await expect(page.locator('body')).toContainText(/blog|post|coming|no /i);
        }
    });

    test('community page interactions are present', async ({ page }) => {
        await page.goto('/community');
        await expect(page.locator('body')).toContainText(/community|project|student|create|explore/i);
        const project = page.locator('a[href*="/community"], button, article, .clay-card').first();
        await expect(project).toBeVisible();
    });

    test('newsletter footer validates empty email', async ({ page }) => {
        await page.goto('/');
        // Dismiss popup banners that intercept footer clicks
        await page.locator('button:has-text("Close"), [aria-label="Close"], .popup-banner button').first().click({ timeout: 3000 }).catch(() => {});
        await page.evaluate(() => {
            document.querySelectorAll('.fixed.inset-0.z-\\[100\\], .fixed.inset-0[class*="z-[100]"]').forEach((el) => el.remove());
        }).catch(() => {});

        const email = page.locator('footer input[type="email"], footer input[placeholder*="Email" i]').first();
        if (await email.count()) {
            await email.scrollIntoViewIfNeeded();
            await email.fill('not-an-email');
            const send = page.locator('footer button[type="submit"]').first();
            if (await send.count()) {
                await send.evaluate((el) => el.click());
                await expect(page).toHaveURL(/younimatiq\.com/);
            }
        }
    });

    test('become a tutor and register tutor entry points', async ({ page }) => {
        await expectHealthy(page, '/become-a-tutor');
        await page.goto('/register/tutor');
        await expectFormReady(page, { minInputs: 3 });
        await page.locator('button[type="submit"]').first().click();
        await expect(page).toHaveURL(/\/register\/tutor/);
    });

    test('login pages for all roles render forms', async ({ page }) => {
        for (const path of ['/login/student', '/login/tutor', '/login/admin', '/login/parent']) {
            await page.goto(path);
            await expect(page.locator('#email')).toBeVisible();
            await expect(page.locator('#password')).toBeVisible();
            await expect(page.locator('button[type="submit"], button:has-text("Sign"), button:has-text("Login"), button:has-text("Secure")').first()).toBeVisible();
        }
    });

    test('course category and grade filters work', async ({ page }) => {
        await page.goto('/courses');
        const categoryHref = await page.locator('a[href*="/courses/category/"]').evaluateAll((els) => {
            const hit = els.map((a) => a.getAttribute('href') || '').find((h) => /\/courses\/category\/[^/?#]+/.test(h));
            return hit || null;
        });
        if (categoryHref) {
            await page.goto(categoryHref);
            await expect(page).toHaveURL(/\/courses\/category\//);
            await expect(page.locator('body')).not.toHaveText(/could not be found/i);
        }

        const gradeHref = await page.locator('a[href*="/courses/grades/"]').evaluateAll((els) => {
            const hit = els.map((a) => a.getAttribute('href') || '').find((h) => /\/courses\/grades\/[^/?#]+/.test(h));
            return hit || null;
        });
        // Grade links may only live in header dropdown HTML; navigate known band if present in nav payload links
        if (gradeHref) {
            await page.goto(gradeHref);
            await expect(page).toHaveURL(/\/courses\/grades\//);
        } else {
            await page.goto('/courses/grades/middle');
            // 200 or soft empty is fine; 404 is not
            await expect(page.locator('body')).not.toHaveText(/Page not found \(404\)/i);
        }
    });

    test('register parent form and contact navigation', async ({ page }) => {
        await page.goto('/register/parent');
        await expectFormReady(page, { minInputs: 2 });
        await page.goto('/contact');
        await expectFormReady(page);
        await expect(page.locator('#message, textarea[name="message"]').first()).toBeVisible();
    });

    test('footer legal/marketing links that exist stay healthy', async ({ page }) => {
        await page.goto('/');
        const hrefs = await page.locator('footer a[href^="/"]').evaluateAll((els) =>
            [...new Set(els.map((a) => a.getAttribute('href')).filter((h) => h && h.length > 1 && !h.includes('#')))].slice(0, 12)
        );
        for (const href of hrefs) {
            await expectHealthy(page, href);
        }
    });
});
