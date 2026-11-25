import './bootstrap';
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { installCrossBundleNavigation } from './lib/crossBundleNav';

const appName = import.meta.env.VITE_APP_NAME || 'Younimatiq';

installCrossBundleNavigation();

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob([
                './Pages/Public/**/*.jsx',
                './Pages/Auth/**/*.jsx',
                './Pages/Marketing/**/*.jsx',
                // Guest checkout + payment callbacks live outside /student/*
                // but reuse Student page components.
                './Pages/Student/Checkout.jsx',
                './Pages/Student/Payment*.jsx',
            ])
        ),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#7130D8',
    },
});
