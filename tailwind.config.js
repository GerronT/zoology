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
                'domain':  '#FBCFE8', // Light Pink
                'kingdom': '#BFDBFE', // Soft Blue
                'phylum':  '#C4B5FD', // Light Purple (distinct from kingdom)
                'class':   '#86EFAC', // Soft Green (not too close to species)
                'order':   '#FDE68A', // Pale Yellow
                'family':  '#FDBA74', // Orange-300 (anchor color)
                'genus':   '#FCA5A5', // Light Coral Red (contrast with pink & orange)
                'species': '#99F6E4', // Aqua Mint (lighter, cooler than class)
                'none':    '#E5E7EB'  // Neutral Gray
            }
        },
    },

    plugins: [forms, typography],
};
