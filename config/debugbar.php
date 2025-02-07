<?php

declare(strict_types=1);

return [
    /*
     |--------------------------------------------------------------------------
     | Debugbar Settings
     |--------------------------------------------------------------------------
     |
     | Debugbar is enabled by default, when debug is set to true in app.php.
     | You can override the value by setting enable to true or false instead of null.
     |
     | You can provide an array of URI's that must be ignored (eg. 'api/*')
     |
     */
    'except' => [
        'telescope*',
        'horizon*',
        'api*',
    ],
];
