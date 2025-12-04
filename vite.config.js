import { sentryVitePlugin } from '@sentry/vite-plugin';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { existsSync } from 'fs';
import path from 'path';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import tailwindcss from '@tailwindcss/vite';

if (existsSync('.airdrop_skip')) {
    console.log('Assets already exist. Skipping compilation.');

    // eslint-disable-next-line no-undef
    process.exit(0);
}

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                // CSS
                'resources/css/admin/admin.css',
                'resources/css/web/web.css',

                // JS
                'resources/js/admin/admin.js',
                'resources/js/web/web.js',
            ],
            refresh: true,
        }),
        // viteStaticCopy({
        //     targets: [
        //         {
        //             src: 'resources/img',
        //             dest: 'assets',
        //         },
        //     ],
        // }),
        sentryVitePlugin({
            org: process.env.VITE_SENTRY_ORGANIZATION,
            project: process.env.VITE_SENTRY_PROJECT,
            telemetry: false,
        }),
    ],
    resolve: {
        alias: {
            '~fonts-path': path.resolve(__dirname, 'resources/fonts'),
            '~node-modules': path.resolve(__dirname, 'node_modules'),
        },
    },
    build: {
        sourcemap: true,
    },
    server: {
        cors: true,
        watch: {
            ignored: [
                '**/.idea',
                '**/vendor/**',
                '**/bootstrap/cache/**',
                '**/docker/**',
                '**/storage/**',
            ],
        },
    },
});
