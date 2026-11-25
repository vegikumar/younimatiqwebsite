import { test, expect } from '@playwright/test';

/**
 * Verifies checkout UI + server currency routing for USD/AED/INR
 * without completing a live card charge.
 */
test.describe('Payment currency routing', () => {
    test('USD checkout labels Stripe in USD and Razorpay in INR', async ({ page }) => {
        await page.goto('/courses');
        const courseHref = await page.locator('a[href^="/courses/"]').evaluateAll((els) => {
            return (
                els
                    .map((a) => a.getAttribute('href') || '')
                    .find((h) => /^\/courses\/[^/?#]+$/.test(h) && !/category|grades|age/.test(h)) || null
            );
        });
        test.skip(!courseHref, 'No course');
        const slug = courseHref.split('/').pop();

        await page.goto(`/checkout/${slug}?classes=1&currency=USD`);
        await expect(page.locator('body')).toContainText(/checkout|enroll|pay/i);

        const stripe = page.getByRole('button', { name: /pay with stripe/i });
        if (await stripe.count()) {
            await expect(stripe).toContainText(/USD/i);
        }
        const razorpay = page.getByRole('button', { name: /pay with razorpay/i });
        if (await razorpay.count()) {
            await expect(razorpay).toContainText(/INR/i);
            await expect(page.locator('body')).toContainText(/Indian Rupees|INR course price/i);
        }
    });

    test('AED checkout labels Stripe in AED and Razorpay note', async ({ page }) => {
        await page.goto('/courses');
        const courseHref = await page.locator('a[href^="/courses/"]').evaluateAll((els) => {
            return (
                els
                    .map((a) => a.getAttribute('href') || '')
                    .find((h) => /^\/courses\/[^/?#]+$/.test(h) && !/category|grades|age/.test(h)) || null
            );
        });
        test.skip(!courseHref, 'No course');
        const slug = courseHref.split('/').pop();

        await page.goto(`/checkout/${slug}?classes=1&currency=AED`);
        const stripe = page.getByRole('button', { name: /pay with stripe/i });
        if (await stripe.count()) {
            await expect(stripe).toContainText(/AED/i);
        }
        const paypal = page.getByRole('button', { name: /pay with paypal/i });
        if (await paypal.count()) {
            await expect(paypal).toContainText(/USD/i);
        }
        const razorpay = page.getByRole('button', { name: /pay with razorpay/i });
        if (await razorpay.count()) {
            await expect(razorpay).toContainText(/INR/i);
        }
    });

    test('INR checkout keeps Stripe and Razorpay coherent', async ({ page }) => {
        await page.goto('/courses');
        const courseHref = await page.locator('a[href^="/courses/"]').evaluateAll((els) => {
            return (
                els
                    .map((a) => a.getAttribute('href') || '')
                    .find((h) => /^\/courses\/[^/?#]+$/.test(h) && !/category|grades|age/.test(h)) || null
            );
        });
        test.skip(!courseHref, 'No course');
        const slug = courseHref.split('/').pop();

        await page.goto(`/checkout/${slug}?classes=1&currency=INR`);
        const stripe = page.getByRole('button', { name: /pay with stripe/i });
        if (await stripe.count()) {
            await expect(stripe).toContainText(/INR/i);
        }
        const razorpay = page.getByRole('button', { name: /pay with razorpay/i });
        if (await razorpay.count()) {
            await expect(razorpay).toContainText(/INR/i);
        }
    });
});
