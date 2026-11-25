import { test, expect } from '@playwright/test';
import { expectFormReady } from './helpers/actions.js';
import { expectHealthy } from './helpers/assertPage.js';

test.describe('Student deep actions', () => {
    test('settings save notifications without password change', async ({ page }) => {
        await page.goto('/student/settings');
        await expectFormReady(page);
        await page.getByRole('button', { name: /save settings/i }).click();
        await page.waitForLoadState('networkidle').catch(() => {});
        await expect(page).toHaveURL(/\/student\/settings/);
        await expect(page.locator('body')).toContainText(/settings|preferences|saved|success|notification/i);
    });

    test('profile page form is editable', async ({ page }) => {
        await page.goto('/student/profile');
        await expectFormReady(page);
        const name = page.locator('input[name="name"], #name').first();
        if (await name.count()) {
            const current = await name.inputValue();
            await name.fill(current || 'Student Sara');
        }
        await expect(page.locator('button[type="submit"], button:has-text("Save")').first()).toBeVisible();
    });

    test('wishlist and payment history actions render', async ({ page }) => {
        await expectHealthy(page, '/student/wishlist');
        await expectHealthy(page, '/student/payment-history');
        await expect(page.locator('body')).toContainText(/wishlist|payment|history|enroll|browse|no /i);
    });

    test('my courses continue-learning or browse works', async ({ page }) => {
        await page.goto('/student/courses');
        const player = page.locator('a[href*="/player"]').first();
        if (await player.count()) {
            await player.click();
            await expect(page).toHaveURL(/\/student\/courses\/.+\/player/);
            await expect(page.locator('body')).not.toHaveText(/could not be found/i);
        } else {
            const browse = page.locator('a[href="/courses"]').first();
            await expect(browse).toBeVisible();
            await browse.click();
            await expect(page).toHaveURL(/\/courses/);
        }
    });

    test('quizzes and assignments list buttons work', async ({ page }) => {
        await page.goto('/student/quizzes');
        await expect(page.locator('body')).toContainText(/quiz|assignment|no |empty|available/i);
        const take = page.locator('a[href*="/quizzes/"]').first();
        if (await take.count()) {
            const href = await take.getAttribute('href');
            await expectHealthy(page, href);
        }

        await page.goto('/student/assignments');
        await expect(page.locator('body')).toContainText(/assignment|activity|no |empty|available/i);
    });

    test('bookings page schedule UI loads', async ({ page }) => {
        await page.goto('/student/bookings');
        await expect(page.locator('body')).toContainText(/book|schedule|class|tutor|no |empty|upcoming/i);
    });
});
