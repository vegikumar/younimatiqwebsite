import { test, expect } from '@playwright/test';
import { expectFormReady } from './helpers/actions.js';
import { expectHealthy } from './helpers/assertPage.js';

test.describe('Tutor deep actions', () => {
    test('profile form and availability link work', async ({ page }) => {
        await page.goto('/tutor/profile');
        await expectFormReady(page);
        await expect(page.locator('button[type="submit"], button:has-text("Save")').first()).toBeVisible();

        const avail = page.locator('a[href="/tutor/availability"]').first();
        await expect(avail).toBeVisible();
        await avail.click();
        await expect(page).toHaveURL(/\/tutor\/availability/);
        await expect(page.locator('body')).not.toHaveText(/could not be found/i);
    });

    test('availability page has interactive controls', async ({ page }) => {
        await page.goto('/tutor/availability');
        await expectFormReady(page, { minInputs: 1 });
    });

    test('classes and students lists are usable', async ({ page }) => {
        await expectHealthy(page, '/tutor/classes');
        await expect(page.locator('body')).toContainText(/class|upcoming|session|no |empty|student/i);
        await expectHealthy(page, '/tutor/students');
        await expect(page.locator('body')).toContainText(/student|learner|no |empty|class/i);
    });

    test('create quiz/assignment entry points open', async ({ page }) => {
        await page.goto('/tutor/quizzes');
        const createQuiz = page.locator('a[href*="quizzes/create"], a:has-text("Create"), button:has-text("Create")').first();
        if (await createQuiz.count()) {
            await createQuiz.click();
            await expect(page).toHaveURL(/\/tutor\/quizzes/);
            await expectFormReady(page);
        }

        await page.goto('/tutor/assignments');
        await expect(page.locator('body')).toContainText(/assignment|activity|create|no |empty/i);
    });

    test('messages and announcements pages respond', async ({ page }) => {
        await expectHealthy(page, '/tutor/messages');
        await expectHealthy(page, '/tutor/announcements');
    });

    test('manage classes shortcut lands on assigned-classes', async ({ page }) => {
        await page.goto('/tutor/dashboard');
        const manage = page.locator('a[href="/tutor/assigned-classes"], a[href="/tutor/manage-courses"]').first();
        await expect(manage).toBeVisible();
        await manage.click();
        await expect(page).toHaveURL(/\/tutor\/(assigned-classes|manage-courses)/);
        await expect(page.locator('body')).not.toHaveText(/Page not found \(404\)/i);
    });
});
