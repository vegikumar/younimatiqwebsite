import { test as setup, expect } from '@playwright/test';
import { loginAdmin, loginStudent, loginTutor, creds } from './helpers/auth.js';
import fs from 'node:fs';
import path from 'node:path';

const authDir = path.join(process.cwd(), 'e2e/.auth');
fs.mkdirSync(authDir, { recursive: true });

setup('authenticate student', async ({ page }) => {
    await loginStudent(page);
    await page.context().storageState({ path: path.join(authDir, 'student.json') });
});

setup('authenticate tutor', async ({ page }) => {
    await loginTutor(page);
    await page.context().storageState({ path: path.join(authDir, 'tutor.json') });
});

setup('authenticate parent', async ({ page }) => {
    const { password } = creds();
    const email = process.env.E2E_PARENT_EMAIL || 'e2e.parent@younimatiq.com';
    await page.goto('/login/parent');
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(password);
    await page.getByRole('button', { name: /sign in/i }).click();
    await page.waitForURL(/\/parent\//, { timeout: 45_000 });
    await page.context().storageState({ path: path.join(authDir, 'parent.json') });
});

setup('authenticate admin', async ({ page }) => {
    await loginAdmin(page);
    await expect(page).toHaveURL(/\/admin\//);
    await page.context().storageState({ path: path.join(authDir, 'admin.json') });
});
