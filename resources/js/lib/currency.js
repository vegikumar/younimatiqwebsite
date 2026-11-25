export function formatPrice(course, currency = 'USD') {
    const c = String(currency || 'USD').toUpperCase();
    if (c === 'AED') {
        return { symbol: 'د.إ', amount: Number(course.price_aed ?? 30) };
    }
    if (c === 'INR') {
        return { symbol: '₹', amount: Number(course.price_inr ?? 600) };
    }
    return { symbol: '$', amount: Number(course.price_usd ?? 18) };
}
