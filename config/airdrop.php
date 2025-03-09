<?php

declare(strict_types=1);
use Hammerstone\Airdrop\Triggers\ConfigTrigger;
use Hammerstone\Airdrop\Triggers\FileTrigger;

return [
    /*
     * Here you can register all of the classes that will be used in determining
     * the freshness of previously built assets. When the output of any of the
     * following classes change, a new build will be required.
     */
    'triggers' => [
        /*
         * Trigger a rebuild when anything in this configuration array
         * changes. We've started you off with your app's APP_ENV
         * variable, but you are free to add anything else.
         */
        ConfigTrigger::class => [
            // This will keep your dev, test, and prod assets distinct
            // since they are usually built with different settings.
            'env' => env('APP_ENV'),
        ],
        /*
         * Trigger a rebuild when files change.
         */
        FileTrigger::class => [
            /*
             * Files or folders that should be included.
             */
            'include' => [
                // By default we include every file in your resource path. Usually
                // this makes the most sense and doesn't need to be changed.
                resource_path(),
                // Any time the webpack.mix.js file is changed, it could affect the
                // the build steps, and therefore the built files.
                base_path('vite.config.js'),
                // Depending on your package manager, you'll want to uncomment one
                // of the following lines. Whenever JS packages are updated or
                // removed, the assets will need to be rebuilt.
                base_path('yarn.lock'),
                // If you use NVM to manage your node versions, you'll want to
                // rebuild your assets anytime the node version changes.
                // base_path('.nvmrc'),
                // If you use Tailwind, uncomment the following line. Changing your
                // Tailwind config will change the CSS files that are generated.
                base_path('tailwind.config.js'),
            ],
            /*
             * Files or folders that should be excluded or ignored.
             */
            'exclude' => [
                //
            ],
            /*
             * Filename strings or patterns that should be excluded,
             * regardless of what directory they are found in.
             */
            'exclude_names' => [
                '.DS_Store',
            ],
        ],
    ],
    /*
     * The outputs section contains all the folders and files that are the result
     * of your asset build process. If the next deploy does not require a rebuild,
     * Airdrop will grab these built assets from the last deploy and put them
     * into the place from which they came.
     */
    'outputs' => [
        /*
         * Files or folders that should be included.
         */
        'include' => [
            // The mix-manifest file tells Laravel how to get your versioned assets.
            public_path('build/manifest.json'),
            public_path('build/assets'),
        ],
        /*
         * Files or folders that should be excluded or ignored.
         */
        'exclude' => [
            //
        ],
        /*
         * Filename strings or patterns that should be excluded,
         * regardless of what directory they are found in.
         */
        'exclude_names' => [
            '.DS_Store',
        ],
    ],
];
