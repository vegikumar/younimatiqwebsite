import { expect } from '@playwright/test';

const FATAL_PATTERNS = [
    /this page could not be found/i,
    /server error/i,
    /something went wrong/i,
    /inertia.*error/i,
    /failed to fetch dynamically imported module/i,
    /cannot read properties of undefined/i,
];

/**
 * Visit a path and assert it rendered a usable page (not blank / not fatal).
 * Returns { ok, status, title, errors[] }.
 */
export async function softVisit(page, path, { expectUrl = null, minBody = 40 } = {}) {
    const consoleErrors = [];
    const pageErrors = [];
    const failedRequests = [];

    const onConsole = (msg) => {
        if (msg.type() === 'error') consoleErrors.push(msg.text());
    };
    const onPageError = (err) => pageErrors.push(String(err));
    const onFail = (req) => {
        const url = req.url();
        if (url.includes('/_boost') || url.includes('chrome-extension')) return;
        failedRequests.push(`${req.failure()?.errorText || 'failed'} ${url}`);
    };

    page.on('console', onConsole);
    page.on('pageerror', onPageError);
    page.on('requestfailed', onFail);

    let response;
    try {
        response = await page.goto(path, { waitUntil: 'domcontentloaded' });
    } catch (e) {
        page.off('console', onConsole);
        page.off('pageerror', onPageError);
        page.off('requestfailed', onFail);
        return { ok: false, path, error: String(e), consoleErrors, pageErrors, failedRequests };
    }

    await page.waitForTimeout(400);
    const status = response?.status() ?? 0;
    const title = await page.title().catch(() => '');
    const bodyText = (await page.locator('body').innerText().catch(() => '')).trim();
    const htmlLen = (await page.content()).length;

    const errors = [];
    if (status >= 400) errors.push(`HTTP ${status}`);
    if (htmlLen < 800) errors.push(`thin HTML (${htmlLen} bytes)`);
    if (bodyText.length < minBody) errors.push(`near-blank body (${bodyText.length} chars)`);
    for (const re of FATAL_PATTERNS) {
        if (re.test(bodyText) || re.test(title)) errors.push(`fatal text: ${re}`);
    }
    if (expectUrl && !expectUrl.test(page.url())) {
        errors.push(`URL mismatch: ${page.url()}`);
    }
    // Ignore noisy third-party console noise; keep app-looking failures.
    const appConsole = consoleErrors.filter(
        (t) => !/favicon|third-party|gtag|facebook|clarity|cdn\.|razorpay/i.test(t)
    );
    if (pageErrors.length) errors.push(`pageerror: ${pageErrors[0]}`);
    if (appConsole.some((t) => /failed to fetch dynamically imported module|chunkloaderror|is not defined/i.test(t))) {
        errors.push(`console: ${appConsole.find((t) => /failed to fetch|chunkloaderror|is not defined/i.test(t))}`);
    }

    page.off('console', onConsole);
    page.off('pageerror', onPageError);
    page.off('requestfailed', onFail);

    return {
        ok: errors.length === 0,
        path,
        url: page.url(),
        status,
        title,
        errors,
        consoleErrors: appConsole.slice(0, 5),
        pageErrors,
        failedRequests: failedRequests.slice(0, 5),
    };
}

export async function expectHealthy(page, path, opts = {}) {
    const result = await softVisit(page, path, opts);
    expect(result.ok, `${path} → ${result.errors?.join('; ') || result.error}`).toBeTruthy();
    return result;
}

export async function crawlPaths(page, paths, opts = {}) {
    const failures = [];
    for (const path of paths) {
        const result = await softVisit(page, path, opts);
        if (!result.ok) failures.push(result);
    }
    return failures;
}
