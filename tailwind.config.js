import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Noto Sans Variable"', '"Noto Sans Lao Variable"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Material Tailwind's blue-gray, as used across the LCIS interface.
                'blue-gray': {
                    50: '#eceff1',
                    100: '#cfd8dc',
                    200: '#b0bec5',
                    300: '#90a4ae',
                    400: '#78909c',
                    500: '#607d8b',
                    600: '#546e7a',
                    700: '#455a64',
                    800: '#37474f',
                    900: '#263238',
                },
            },
        },
    },

    plugins: [forms],
};
