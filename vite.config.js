import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { existsSync } from 'fs';
import path from "path";
import { viteStaticCopy } from 'vite-plugin-static-copy';

if (existsSync('.airdrop_skip')) {
    console.log('Assets already exist. Skipping compilation.');

    // eslint-disable-next-line no-undef
    process.exit(0);
}

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // SCSS
                'resources/scss/main.scss',

                // JS
                'resources/js/main.js',
            ],
            refresh: true,
        }),
        viteStaticCopy({
            targets: [
                {
                    src: 'resources/img',
                    dest: 'assets',
                },
            ],
        }),
    ],
    resolve: {
        alias: {
            'fonts-path': path.resolve(__dirname, 'resources/fonts'),
        },
    },
});
