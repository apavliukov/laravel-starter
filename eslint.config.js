import babelParser from '@babel/eslint-parser';
import js from '@eslint/js';
import eslintPluginPrettierRecommended from 'eslint-plugin-prettier/recommended';
import eslintImport from 'eslint-plugin-import';
import globals from 'globals';

export default [
    {
        ignores: [
            '.idea/**/*',
            'public/**/*',
            'storage/**/*',
            'vendor/**/*',
            'yarn.lock',
            'package-lock.json',
            'composer.lock',
        ],
    },
    js.configs.recommended,
    eslintPluginPrettierRecommended,
    {
        languageOptions: {
            globals: {
                ...globals.browser,
            },
            parser: babelParser,
            parserOptions: {
                requireConfigFile: false,
            },
        },
        plugins: {
            import: eslintImport,
        },
        rules: {
            'max-len': [
                'error',
                {
                    code: 120,
                    ignorePattern: '^import .*',
                },
            ],
            'arrow-body-style': ['error', 'as-needed'],
            indent: [
                'error',
                4,
                {
                    SwitchCase: 1,
                },
            ],
            'import/no-extraneous-dependencies': [
                'error',
                {
                    devDependencies: true,
                },
            ],
            'import/no-unresolved': [
                'error',
                {
                    ignore: ['^#', 'laravel-vite-plugin'],
                },
            ],
            'import/extensions': [
                'error',
                'never',
                {
                    ignorePackages: true,
                },
            ],
        },
    },
];
