import { test, expect } from '@playwright/test';
import { expectHealthy } from './helpers/assertPage.js';
import { expectFormReady } from './helpers/actions.js';

test.describe('Public deep actions', () => {
    test('free trial modal opens and enforces 48h date min', async ({ page }) => {
        await page.goto('/');
        await page.waitForFunction(() => typeof window.openLeadModal === 'function', null, { timeout: 20_000 });
        await page.evaluate(() => window.openLeadModal({ preventDefault() {} }, 'free_class'));

        await expect(page.locator('#leadModal')).toHaveClass(/open/, { timeout: 15_000 });
        await expect(page.locator('#leadForm')).toBeVisible();

        const date = page.locator('#consultDate');
        await expect(date).toBeVisible();
        const min = await date.getAttribute('min');
        expect(min, 'free trial date min must be set').toBeTruthy();

        // 48h ahead ⇒ min date must be at least ~2 days out
        const minDate = new Date(`${min}T12:00:00`);
        const earliestOk = new Date(Date.now() + 47 * 60 * 60 * 1000);
        earliestOk.setHours(0, 0, 0, 0);
        expect(minDate.getTime()).toBeGreaterThanOrEqual(earliestOk.getTime());

        await page.locator('#leadFormSubmit').click();
        await expect(page.locator('#leadModal')).toHaveClass(/open/);
        await page.locator('#leadModalClose').click();
        await expect(page.locator('#leadModal')).not.toHaveClass(/open/);
    });

    test('contact form shows validation on empty submit', async ({ page }) => {
        await page.goto('/contact');
        await expectFormReady(page);
        await page.locator('form').filter({ has: page.locator('#message') }).locator('button[type="submit"]').click();
        // HTML5 required or inertia errors — stay on contact
        await expect(page).toHaveURL(/\/contact/);
        const invalid = page.locator(':invalid, .text-error, .bg-error-container');
        await expect(invalid.first()).toBeVisible({ timeout: 10_000 });
    });

    test('course show enroll CTA opens checkout shell', async ({ page }) => {
        await page.goto('/courses');
        const courseHref = await page.locator('a[href^="/courses/"]').evaluateAll((els) => {
            return (
                els
                    .map((a) => a.getAttribute('href') || '')
                    .find((h) => /^\/courses\/[^/?#]+$/.test(h) && !/category|grades|age/.test(h)) || null
            );
        });
        test.skip(!courseHref, 'No course detail links');
        await page.goto(courseHref);

        const pay = page.locator('a[href^="/checkout/"]').first();
        if (await pay.count()) {
            const href = await pay.getAttribute('href');
            await expectHealthy(page, href);
            await expect(page.locator('body')).toContainText(/checkout|payment|pay|enroll|razorpay|stripe|paypal/i);
            // Guest checkout should offer account fields or gateway buttons
            const gateways = page.locator('button, a').filter({ hasText: /pay|razorpay|stripe|paypal|generate/i });
            expect(await gateways.count()).toBeGreaterThan(0);
        } else {
            await expect(page.locator('a:has-text("Continue Learning"), text=Continue Learning').first()).toBeVisible();
        }
    });

    test('register student form is interactive', async ({ page }) => {
        await page.goto('/register/student');
        await expectFormReady(page, { minInputs: 3 });
        await page.locator('button[type="submit"]').first().click();
        await expect(page).toHaveURL(/\/register\/student/);
    });

    test('pricing and FAQ CTAs remain usable', async ({ page }) => {
        await expectHealthy(page, '/pricing');
        await expect(page.locator('a[href*="login"], a[href="/courses"], a[href="#leadModal"], a.book-cta').first()).toBeVisible();
        await expectHealthy(page, '/faq');
        await expect(page.locator('body')).toContainText(/.{30,}/);
    });
});
