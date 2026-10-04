const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.tsx',
        './resources/js/**/*.ts',
        './resources/js/**/*.jsx',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                brand: {
                    DEFAULT: '#b91c1c',
                    dark: '#991b1b',
                    subtle: '#fef2f2',
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            transitionDuration: {
                350: '350ms',
                550: '550ms',
                700: '700ms',
                900: '900ms',
                1200: '1200ms',
                1500: '1500ms',
            },
            transitionTimingFunction: {
                DEFAULT: 'cubic-bezier(0.22, 1, 0.36, 1)',
            },
            boxShadow: {
                soft: '0 10px 30px -12px rgba(2, 6, 23, 0.08)',
                lifted: '0 16px 36px -18px rgba(15, 23, 42, 0.15)',
            },
            animation: {
                'fade-in': 'fade-in 800ms cubic-bezier(0.22, 1, 0.36, 1) both',
                'reveal-up': 'reveal-up 900ms cubic-bezier(0.22, 1, 0.36, 1) both',
                'pulse-soft': 'pulse 2.5s cubic-bezier(0.22, 1, 0.36, 1) infinite',
            },
        },
    },
    plugins: [],
};
