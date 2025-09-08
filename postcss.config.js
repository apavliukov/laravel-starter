import { purgeCSSPlugin } from '@fullhuman/postcss-purgecss';
import cssnano from 'cssnano';

// eslint-disable-next-line no-undef
const isProduction = process.env.VITE_APP_ENV === 'production';

/** @type {import('postcss-load-config').Config} */
const config = {
    plugins: [
        // purgeCSSPlugin({
        //     content: [
        //         './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        //         './resources/**/*.{html,js,jsx,ts,tsx,vue,php,twig}',
        //     ],
        //     defaultExtractor: (content) => {
        //         // Extract class names using Tailwind's standard regex patterns
        //         const classNames = content.match(/[^<>"'`\s]*[^<>"'`\s:]/g) || [];
        //
        //         // Extract attributes and values starting with "data-"
        //         const dataAttributes = content.match(/data-[\w-]+/g) || [];
        //
        //         // Extract arbitrary values and attributes in square brackets
        //         const arbitraryValues = content.match(/\[.*?]/g) || [];
        //
        //         return [...classNames, ...dataAttributes, ...arbitraryValues];
        //     },
        //     safelist: {
        //         standard: [/^media-library/],
        //     },
        // }),
        isProduction ? cssnano({ preset: 'default' }) : null,
    ],
};

export default config;
