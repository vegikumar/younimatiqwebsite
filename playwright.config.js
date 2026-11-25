import { defineConfig, devices } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.dirname(fileURLToPath(import.meta.url));
const envPath = path.join(root, '.env.e2e');
if (fs.existsSync(envPath)) {
    for (const line of fs.readFileSync(envPath, 'utf8').split('\n')) {
        const trimmed = line.trim();
        if (!trimmed || trimmed.startsWith('#')) continue;
        const eq = trimmed.indexOf('=');
        if (eq < 1) continue;
        const key = trimmed.slice(0, eq).trim();
        const value = trimmed.slice(eq + 1).trim();
        if (!(key in process.env)) process.env[key] = value;
    }
}

const baseURL = process.env.E2E_BASE_URL || 'https://younimatiq.com';
const authDir = path.join(root, 'e2e/.auth');

export default defineConfig({
    testDir: './e2e',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    workers: 1,
    timeout: 90_000,
    expect: { timeout: 15_000 },
    reporter: [['list'], ['html', { open: 'never', outputFolder: 'e2e-report' }]],
    outputDir: 'e2e-results',
    use: {
        baseURL,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        ignoreHTTPSErrors: true,
        actionTimeout: 20_000,
        navigationTimeout: 45_000,
    },
    projects: [
        { name: 'setup', testMatch: /auth\.setup\.js/ },
        {
            name: 'public',
            testMatch: /(public(\.(deep|flows))?|payments\.currency|cross\.flows)\.spec\.js/,
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'student',
            testMatch: /student(\.(deep|flows))?\.spec\.js/,
            dependencies: ['setup'],
            use: {
                ...devices['Desktop Chrome'],
                storageState: path.join(authDir, 'student.json'),
            },
        },
        {
            name: 'tutor',
            testMatch: /tutor(\.(deep|flows))?\.spec\.js/,
            dependencies: ['setup'],
            use: {
                ...devices['Desktop Chrome'],
                storageState: path.join(authDir, 'tutor.json'),
            },
        },
        {
            name: 'parent',
            testMatch: /parent(\.flows)?\.spec\.js/,
            dependencies: ['setup'],
            use: {
                ...devices['Desktop Chrome'],
                storageState: path.join(authDir, 'parent.json'),
            },
        },
        {
            name: 'admin',
            testMatch: /admin(\.(deep|flows))?\.spec\.js/,
            dependencies: ['setup'],
            use: {
                ...devices['Desktop Chrome'],
                storageState: path.join(authDir, 'admin.json'),
            },
        },
    ],
});
