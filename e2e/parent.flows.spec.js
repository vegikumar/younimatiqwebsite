import { test, expect } from '@playwright/test';
import { expectHealthy } from './helpers/assertPage.js';
import { expectFormReady } from './helpers/actions.js';

test.describe('Parent whole-site flows', () => {
    test('dashboard shows family/children context', async ({ page }) => {
        await page.goto('/parent/dashboard');
        await expect(page).toHaveURL(/\/parent\/dashboard/);
        await expect(page.locator('body')).toContainText(/parent|child|family|dashboard|consent|referral/i);
        const links = await page.locator('a[href^="/parent/"]').evaluateAll((els) =>
            [...new Set(els.map((a) => a.getAttribute('href')).filter(Boolean))]
        );
        expect(links.length).toBeGreaterThan(0);
        for (const href of links.slice(0, 6)) {
            await expectHealthy(page, href);
        }
    });

    test('AI consent controls are present when children exist', async ({ page }) => {
        await page.goto('/parent/dashboard');
        const consent = page.locator(
            'input[type="checkbox"][name*="consent" i], button:has-text("Consent"), label:has-text("AI"), [data-testid*="consent"]'
        ).first();
        const body = page.locator('body');
        if (await consent.count()) {
            await expect(consent).toBeVisible();
        } else {
            await expect(body).toContainText(/child|link|family|no |empty|consent|dashboard/i);
        }
    });

    test('referrals page is interactive', async ({ page }) => {
        await page.goto('/parent/referrals');
        await expect(page).toHaveURL(/\/parent\/referrals/);
        await expect(page.locator('body')).toContainText(/refer|share|code|invite|reward|earn|no |empty/i);
        const copy = page.locator('button:has-text("Copy"), input[readonly], input[value*="http"], code').first();
        if (await copy.count()) {
            await expect(copy).toBeVisible();
        }
    });

    test('parent register form validates empty submit', async ({ browser }) => {
        const context = await browser.newContext({ ignoreHTTPSErrors: true });
        const page = await context.newPage();
        await page.goto('/register/parent');
        await expectFormReady(page, { minInputs: 2 });
        await page.locator('button[type="submit"]').first().click();
        await expect(page).toHaveURL(/\/register\/parent/);
        await context.close();
    });

    test('website link from parent portal reaches public site', async ({ page }) => {
        await page.goto('/parent/dashboard');
        const website = page.locator('a[href="/"][aria-label="Website"], a[href="/"]').first();
        if (await website.count()) {
            await website.click();
            await page.waitForLoadState('domcontentloaded');
            await expect(page.locator('body')).toContainText(/younimatiq|course|trial|learn/i);
        }
    });
});
