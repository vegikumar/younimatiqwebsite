import { test, expect } from '@playwright/test';
import { crawlPaths } from './helpers/assertPage.js';

test.describe('Parent dashboard', () => {
    test('storage session opens parent portal', async ({ page }) => {
        await page.goto('/parent/dashboard');
        await expect(page).toHaveURL(/\/parent\//);
        await expect(page.locator('body')).toContainText(/parent|family|dashboard|referral/i);
    });

    test('parent routes render', async ({ page }) => {
        const failures = await crawlPaths(page, ['/parent/dashboard', '/parent/referrals'], {
            expectUrl: /\/parent\//,
        });
        expect(failures, JSON.stringify(failures, null, 2)).toEqual([]);
    });
});
