import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/public-chrome.css',
                'resources/js/app.js',
                'resources/js/app.jsx',
                'resources/js/app-portal.jsx',
                'resources/js/marketing.js',
            ],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) return;
                    // Keep React/Inertia shared. Do NOT force a vendor-chakra chunk —
                    // that pulled Emotion/React into the public entry via shared deps.
                    if (
                        id.includes('react-dom')
                        || id.includes('react/jsx-runtime')
                        || id.includes('react/jsx-dev-runtime')
                        || id.includes('/scheduler/')
                        || id.includes('/react/')
                    ) {
                        return 'vendor-react';
                    }
                    if (id.includes('@inertiajs')) return 'vendor-inertia';
                },
            },
        },
        cssCodeSplit: true,
        sourcemap: false,
        chunkSizeWarningLimit: 600,
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
