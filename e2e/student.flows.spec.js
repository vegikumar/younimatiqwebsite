import { test, expect } from '@playwright/test';
import { expectHealthy } from './helpers/assertPage.js';
import { expectFormReady } from './helpers/actions.js';

test.describe('Student whole-site flows', () => {
    test('dashboard quick links work', async ({ page }) => {
        await page.goto('/student/dashboard');
        await expect(page).toHaveURL(/\/student\/dashboard/);
        const links = await page.locator('a[href^="/student/"]').evaluateAll((els) =>
            [...new Set(els.map((a) => a.getAttribute('href')).filter(Boolean))].slice(0, 8)
        );
        for (const href of links) {
            await expectHealthy(page, href);
        }
    });

    test('profile save keeps user on profile', async ({ page }) => {
        await page.goto('/student/profile');
        await expectFormReady(page);
        const name = page.locator('input[name="name"], #name').first();
        if (await name.count()) {
            const val = (await name.inputValue()) || 'Student Sara';
            await name.fill(val);
        }
        const save = page.locator('button[type="submit"], button:has-text("Save")').first();
        await save.click();
        await page.waitForLoadState('networkidle').catch(() => {});
        await expect(page).toHaveURL(/\/student\/profile/);
        await expect(page.locator('body')).not.toHaveText(/server error|could not be found/i);
    });

    test('referrals and rewards pages are interactive', async ({ page }) => {
        await expectHealthy(page, '/student/referrals');
        await expect(page.locator('body')).toContainText(/refer|share|code|invite|reward|point|no /i);
        await expectHealthy(page, '/student/rewards');
        await expect(page.locator('body')).toContainText(/reward|badge|point|earn|no |empty/i);
    });

    test('python lab and career explorer load', async ({ page }) => {
        await expectHealthy(page, '/student/python');
        await expectHealthy(page, '/student/career');
    });

    test('reviews page form or empty state', async ({ page }) => {
        await page.goto('/student/reviews');
        await expect(page.locator('body')).toContainText(/review|feedback|rate|no |empty|course/i);
    });

    test('qa page accepts question UI', async ({ page }) => {
        await page.goto('/student/qa');
        await expect(page.locator('body')).toContainText(/question|ask|q & a|qa|tutor|no |empty/i);
        const box = page.locator('textarea, input[name="question"], input[name="body"]').first();
        if (await box.count()) {
            await expect(box).toBeVisible();
        }
    });

    test('activities page loads game/activity cards', async ({ page }) => {
        await page.goto('/student/activities');
        await expect(page.locator('body')).toContainText(/activit|game|course|browse|no |empty|enroll/i);
    });

    test('recordings page loads', async ({ page }) => {
        await expectHealthy(page, '/student/recordings');
    });

    test('header website link leaves portal shell', async ({ page }) => {
        await page.goto('/student/dashboard');
        const website = page.locator('a[href="/"][aria-label="Website"], a.student-rail-link[href="/"]').first();
        if (await website.count()) {
            await Promise.all([
                page.waitForURL((url) => !url.pathname.startsWith('/student'), { timeout: 30_000 }),
                website.click(),
            ]);
            await expect(page).not.toHaveURL(/\/student\//);
            await expect(page.locator('body')).toContainText(/younimatiq|course|trial|learn/i);
        }
    });

    test('settings and wishlist interact without fatal errors', async ({ page }) => {
        await page.goto('/student/settings');
        await expectFormReady(page);
        await page.goto('/student/wishlist');
        await expect(page.locator('body')).toContainText(/wishlist|course|browse|no |empty|heart|save/i);
        const browse = page.locator('a[href="/courses"]').first();
        if (await browse.count()) {
            await browse.click();
            await expect(page).toHaveURL(/\/courses/);
        }
    });

    test('schedule wizard opens when enrolled course link exists', async ({ page }) => {
        await page.goto('/student/courses');
        const wizard = page.locator('a[href*="/schedule-wizard"]').first();
        if (await wizard.count()) {
            const href = await wizard.getAttribute('href');
            await expectHealthy(page, href);
            await expect(page.locator('body')).toContainText(/schedule|slot|time|class|wizard|book/i);
        } else {
            await expect(page.locator('body')).toContainText(/course|enroll|browse|my courses|learning/i);
        }
    });

    test('payment history and bookings remain reachable from dashboard', async ({ page }) => {
        await page.goto('/student/dashboard');
        for (const path of ['/student/payment-history', '/student/bookings', '/student/assignments']) {
            await expectHealthy(page, path);
        }
    });
});
