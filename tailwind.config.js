import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],
    safelist: [
        'bg-domain',
        'bg-kingdom',
        'bg-phylum',
        'bg-class',
        'bg-order',
        'bg-family',
        'bg-genus',
        'bg-species',
        'bg-none',

        'from-domain',
        'from-kingdom',
        'from-phylum',
        'from-class',
        'from-order',
        'from-family',
        'from-genus',
        'from-species',
        'from-none',

        'to-domain',
        'to-kingdom',
        'to-phylum',
        'to-class',
        'to-order',
        'to-family',
        'to-genus',
        'to-species',
        'to-none',

        'text-domain',
        'text-kingdom',
        'text-phylum',
        'text-class',
        'text-order',
        'text-family',
        'text-genus',
        'text-species',
        'text-none',
        // Add more dynamic colors if necessary
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                    // Add your custom background colors here
                    'domain': '#FFD1DC',
                    'kingdom': '#AEC6CF',
                    'phylum': '#B0EACD',
                    'class': '#D7BDE2',
                    'order': '#FFFACD',
                    'family': '#FFD8B1',
                    'genus': '#B2DFDB',
                    'species': '#E6E6FA',
                    'none': '#C1F0DC',
            },
        },
    },

    plugins: [forms, typography],
};
