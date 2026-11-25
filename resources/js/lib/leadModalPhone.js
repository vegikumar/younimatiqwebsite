let phoneInputsReady = false;
let phoneInputsLoading = null;
let itiPhoneModal = null;
let itiWhatsappModal = null;

function loadStylesheet(href, id) {
    const existing = document.getElementById(id);
    if (existing) {
        return Promise.resolve();
    }
    return new Promise((resolve, reject) => {
        const link = document.createElement('link');
        link.id = id;
        link.rel = 'stylesheet';
        link.href = href;
        link.onload = resolve;
        link.onerror = reject;
        document.head.appendChild(link);
    });
}

function loadScript(src, id) {
    const existing = document.getElementById(id);
    if (existing) {
        if (id === 'intl-tel-input-js' && window.intlTelInput) {
            return Promise.resolve();
        }
        return new Promise((resolve, reject) => {
            existing.addEventListener('load', resolve, { once: true });
            existing.addEventListener('error', reject, { once: true });
        });
    }
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.id = id;
        script.src = src;
        script.async = true;
        script.onload = resolve;
        script.onerror = reject;
        document.body.appendChild(script);
    });
}

export function resolveCountry(detectedCountry, callback) {
    if (detectedCountry && /^[a-z]{2}$/.test(detectedCountry)) {
        callback(detectedCountry);
        return;
    }

    fetch('/geo/country', { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then((res) => (res.ok ? res.json() : Promise.reject()))
        .then((data) => {
            const code = (data.country_code || data.countryCode || '').toString().toLowerCase();
            callback(/^[a-z]{2}$/.test(code) ? code : 'us');
        })
        .catch(() =>
            fetch('https://ipapi.co/json/')
                .then((res) => res.json())
                .then((data) => {
                    callback((data.country_code || 'us').toLowerCase());
                })
        )
        .catch(() => {
            callback('us');
        });
}

function phoneInputOptions(detectedCountry) {
    return {
        initialCountry: 'auto',
        preferredCountries: ['us', 'in', 'au', 'gb', 'ae', 'sg', 'ca'],
        nationalMode: false,
        formatOnDisplay: true,
        autoPlaceholder: 'aggressive',
        separateDialCode: true,
        dropdownContainer: document.body,
        geoIpLookup: (callback) => resolveCountry(detectedCountry, callback),
        utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js',
    };
}

function initPhoneInputs(phoneInputModal, whatsappInputModal, detectedCountry) {
    if (phoneInputsReady || !phoneInputModal || !window.intlTelInput) {
        return;
    }

    if (phoneInputModal.getAttribute('data-intl-tel-input-id') != null && window.intlTelInputGlobals) {
        try {
            const existing = window.intlTelInputGlobals.getInstance(phoneInputModal);
            if (existing) existing.destroy();
        } catch {
            /* noop */
        }
    }

    itiPhoneModal = window.intlTelInput(phoneInputModal, phoneInputOptions(detectedCountry));
    phoneInputModal.setAttribute('autocomplete', 'tel');
    phoneInputModal.setAttribute('inputmode', 'tel');

    if (whatsappInputModal) {
        if (whatsappInputModal.getAttribute('data-intl-tel-input-id') != null && window.intlTelInputGlobals) {
            try {
                const existingWa = window.intlTelInputGlobals.getInstance(whatsappInputModal);
                if (existingWa) existingWa.destroy();
            } catch {
                /* noop */
            }
        }
        itiWhatsappModal = window.intlTelInput(whatsappInputModal, phoneInputOptions(detectedCountry));
        whatsappInputModal.setAttribute('autocomplete', 'tel');
        whatsappInputModal.setAttribute('inputmode', 'tel');

        phoneInputModal.addEventListener('countrychange', () => {
            if (!itiPhoneModal || !itiWhatsappModal) return;
            const country = itiPhoneModal.getSelectedCountryData();
            if (country && country.iso2) {
                itiWhatsappModal.setCountry(country.iso2);
            }
        });
    }

    phoneInputsReady = true;
}

export function ensurePhoneInputs(phoneInputModal, whatsappInputModal, detectedCountry) {
    if (phoneInputsReady && itiPhoneModal) {
        return Promise.resolve();
    }
    if (phoneInputsLoading) {
        return phoneInputsLoading;
    }

    phoneInputsLoading = loadStylesheet(
        'https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css',
        'intl-tel-input-css'
    )
        .then(() =>
            loadScript(
                'https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js',
                'intl-tel-input-js'
            )
        )
        .then(() => {
            initPhoneInputs(phoneInputModal, whatsappInputModal, detectedCountry);
            phoneInputsLoading = null;
        })
        .catch((error) => {
            phoneInputsLoading = null;
            console.error('Phone input library failed to load', error);
        });

    return phoneInputsLoading;
}

export function normalizePhoneOnSubmit(iti, input) {
    if (!iti || !input) return true;
    if (input.value.trim() === '') {
        if (input.hasAttribute('required')) {
            input.style.borderColor = 'red';
            return false;
        }
        return true;
    }
    if (window.intlTelInputUtils && !iti.isValidNumber()) {
        input.style.borderColor = 'red';
        return false;
    }
    input.style.borderColor = '';
    const full = iti.getNumber();
    if (full) input.value = full;
    return true;
}

export function getItiInstances() {
    return { itiPhoneModal, itiWhatsappModal };
}

export function resetPhoneInputsReady() {
    phoneInputsReady = false;
    itiPhoneModal = null;
    itiWhatsappModal = null;
}
