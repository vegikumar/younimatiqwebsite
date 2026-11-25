import { expect } from '@playwright/test';

export function creds() {
    return {
        password: process.env.E2E_PASSWORD || 'E2eTest!2026Ym',
        student: process.env.E2E_STUDENT_EMAIL || 'student@younimatiq.com',
        tutor: process.env.E2E_TUTOR_EMAIL || 'tutor@younimatiq.com',
        admin: process.env.E2E_ADMIN_EMAIL || 'admin@younimatiq.com',
        e2eSecret: process.env.E2E_SECRET || '',
    };
}

async function fillLogin(page, email, password) {
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(password);
    await page.getByRole('button', { name: /start your adventure|sign in|secure login/i }).click();
}

export async function loginStudent(page) {
    const { student, password } = creds();
    await page.goto('/login/student');
    await fillLogin(page, student, password);
    await page.waitForURL(/\/student\//, { timeout: 45_000 });
    await expect(page).not.toHaveURL(/\/login\//);
}

export async function loginTutor(page) {
    const { tutor, password } = creds();
    await page.goto('/login/tutor');
    await fillLogin(page, tutor, password);
    await page.waitForURL(/\/tutor\//, { timeout: 45_000 });
    await expect(page).not.toHaveURL(/\/login\//);
}

async function resolveAdminOtp(page, adminEmail) {
    // Prefer on-page flash when mail delivery failed and code is shown.
    const status = page.locator('body');
    const text = await status.innerText();
    const onPage = text.match(/verification code is:\s*(\d{6})/i);
    if (onPage) return onPage[1];

    const { e2eSecret } = creds();
    if (!e2eSecret) {
        throw new Error('Admin OTP not on page and E2E_SECRET is not set');
    }

    // Mail succeeded — fetch OTP via secret bridge.
    for (let i = 0; i < 10; i++) {
        const res = await page.request.get(`/e2e/admin-otp?email=${encodeURIComponent(adminEmail)}`, {
            headers: { 'X-E2E-Secret': e2eSecret },
        });
        if (res.ok()) {
            const data = await res.json();
            if (data?.otp) return String(data.otp);
        }
        await page.waitForTimeout(500);
    }

    throw new Error('Could not resolve admin OTP via page flash or /e2e/admin-otp');
}

export async function loginAdmin(page) {
    const { admin, password } = creds();
    await page.goto('/login/admin');
    await fillLogin(page, admin, password);
    await page.waitForURL(/\/login\/admin\/otp/, { timeout: 45_000 });

    const otp = await resolveAdminOtp(page, admin);
    await page.locator('#otp').fill(otp);
    await page.getByRole('button', { name: /verify/i }).click();
    await page.waitForURL(/\/admin\//, { timeout: 45_000 });
    await expect(page).not.toHaveURL(/\/login\//);
}
