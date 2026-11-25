/**
 * Country-agnostic price hydration.
 *
 * Public pages render USD prices server-side (so they can be edge-cached).
 * This module fetches the visitor's country/currency, then rewrites every
 * [data-price] element to the correct local price using the same conversion
 * rules as App\Services\CurrencyService, preserving bespoke per-currency
 * values supplied via data-price-<code> attributes.
 */
(function () {
    'use strict';

    var state = {
        currency: 'USD',
        rates: { USD: 1 },
        symbols: { USD: '$' },
        loaded: false,
    };

    function ceil(value) {
        return Math.ceil(value - 1e-9);
    }

    function localAmount(usd, currency) {
        if (currency === 'USD') {
            return ceil(usd);
        }
        var rate = state.rates[currency] || 1;
        return ceil(usd * rate * 1.05);
    }

    function formatLocal(amount, currency) {
        var symbol = state.symbols[currency] || currency + ' ';
        return symbol + Math.round(amount).toLocaleString('en-US');
    }

    function applyToElement(el) {
        var usd = parseFloat(el.getAttribute('data-price-usd') || el.getAttribute('data-price'));
        if (isNaN(usd)) {
            return;
        }

        var bespoke = el.getAttribute('data-price-' + state.currency.toLowerCase());
        var amount = bespoke !== null ? parseFloat(bespoke) : localAmount(usd, state.currency);
        if (isNaN(amount)) {
            return;
        }

        var text = formatLocal(amount, state.currency);
        if (el.textContent !== text) {
            el.textContent = text;
        }
    }

    function applyAll() {
        var nodes = document.querySelectorAll('[data-price]');
        for (var i = 0; i < nodes.length; i++) {
            applyToElement(nodes[i]);
        }
        applyVisibility();
        document.documentElement.setAttribute('data-currency', state.currency);
    }

    function matchesList(attrValue, currency) {
        if (!attrValue) {
            return false;
        }
        var parts = attrValue.toUpperCase().split(/[\s,]+/);
        for (var i = 0; i < parts.length; i++) {
            if (parts[i] === currency) {
                return true;
            }
        }
        return false;
    }

    function applyVisibility() {
        var only = document.querySelectorAll('[data-currency-only]');
        for (var i = 0; i < only.length; i++) {
            only[i].hidden = !matchesList(only[i].getAttribute('data-currency-only'), state.currency);
        }

        var except = document.querySelectorAll('[data-currency-except]');
        for (var j = 0; j < except.length; j++) {
            except[j].hidden = matchesList(except[j].getAttribute('data-currency-except'), state.currency);
        }
    }

    function hydrate() {
        fetch('/geo/country', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })
            .then(function (res) {
                return res.ok ? res.json() : null;
            })
            .then(function (data) {
                if (!data) {
                    return;
                }
                state.currency = (data.currency || 'USD').toUpperCase();
                state.rates = data.rates || state.rates;
                state.symbols = data.symbols || state.symbols;
                state.loaded = true;
                applyAll();
            })
            .catch(function () {
                /* keep USD defaults */
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hydrate);
    } else {
        hydrate();
    }

    window.YounimatiqCurrency = { applyAll: applyAll, state: state };
})();
