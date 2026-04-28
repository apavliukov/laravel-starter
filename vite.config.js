import { sentryVitePlugin } from '@sentry/vite-plugin';
import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), "");

    return {
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
                org: env.SENTRY_ORGANIZATION,
                project: env.SENTRY_PROJECT,
                authToken: env.SENTRY_AUTH_TOKEN,
                telemetry: false,
                sourcemaps: {
                    filesToDeleteAfterUpload: ['./**/*.map', '.*/**/public/**/*.map'],
                },
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
                    './vendor/**',
                    '**/bootstrap/cache/**',
                    '**/docker/**',
                    '**/storage/**',
                ],
            },
        },
    };
});
