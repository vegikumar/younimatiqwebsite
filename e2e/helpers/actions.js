import { expect } from '@playwright/test';

/** Assert a form/create screen rendered with interactive controls. */
export async function expectFormReady(page, { minInputs = 1 } = {}) {
    await expect(page.locator('body')).not.toHaveText(/this page could not be found|server error/i);
    const controls = page.locator(
        'input:not([type="hidden"]):visible, textarea:visible, select:visible, button[type="submit"]:visible'
    );
    await expect(controls.first()).toBeVisible({ timeout: 15_000 });
    expect(await controls.count()).toBeGreaterThanOrEqual(minInputs);
}

/** Click first matching button/link by name regex if present. */
export async function clickIfVisible(page, nameRe) {
    const el = page.getByRole('button', { name: nameRe }).or(page.getByRole('link', { name: nameRe })).first();
    if (await el.count()) {
        await el.click({ trial: false }).catch(async () => {
            await el.evaluate((node) => node.click());
        });
        return true;
    }
    return false;
}

export async function openFirstHref(page, prefix) {
    const href = await page.locator(`a[href^="${prefix}"]`).evaluateAll((els) => {
        const hit = els
            .map((a) => a.getAttribute('href') || '')
            .find((h) => h && !h.includes('#') && h !== prefix && h !== `${prefix}/`);
        return hit || null;
    });
    if (!href) return null;
    await page.goto(href);
    return href;
}
