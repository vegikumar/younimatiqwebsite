import { createSystem, defaultConfig, defineConfig } from '@chakra-ui/react';

const config = defineConfig({
    preflight: false,
    cssVarsRoot: '.chakra-portal',
    theme: {
        tokens: {
            fonts: {
                heading: { value: 'Nunito, ui-sans-serif, system-ui, sans-serif' },
                body: { value: 'Nunito, ui-sans-serif, system-ui, sans-serif' },
            },
            colors: {
                brand: {
                    50: { value: '#F6F1FF' },
                    100: { value: '#E9DCFF' },
                    500: { value: '#7130D8' },
                    600: { value: '#5B22B5' },
                    700: { value: '#45198C' },
                },
            },
        },
    },
});

export const system = createSystem(defaultConfig, config);
