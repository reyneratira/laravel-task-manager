import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    safelist: [
        // Dynamic status/priority badge colors used via {{ $enum->color() }}
        // Status: gray (Pending), blue (InProgress), green (Done), red (Cancelled)
        // Priority: green (Low), yellow (Medium), red (High)
        {
            pattern: /bg-(gray|blue|green|red|yellow|purple)-(50|100|600|700)/,
            variants: ['hover'],
        },
        {
            pattern: /text-(gray|blue|green|red|yellow|purple)-(100|500|600|700)/,
        },
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
