import { sentryVitePlugin } from "@sentry/vite-plugin";
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from "path";
import { viteStaticCopy } from 'vite-plugin-static-copy';
import tailwindcss from "@tailwindcss/vite";

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
                'resources/css/main.css',

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
        sentryVitePlugin({
            org: process.env.VITE_SENTRY_ORGANIZATION,
            project: process.env.VITE_SENTRY_PROJECT,
            telemetry: false,
        }),
    ],
    resolve: {
        alias: {
            'fonts-path': path.resolve(__dirname, 'resources/fonts'),
        },
    },
    build: {
        sourcemap: true
    },
    server: {
        watch: {
            ignored: [
                '**/.idea',
                '**/vendor/**',
                '**/bootstrap/cache/**',
                '**/docker/**',
                '**/storage/**',
            ]
        }
    },
});
