<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable\disable request logger
    |--------------------------------------------------------------------------
    |
    | An option allowing to enable and disable requests logging and that
    | may be a boolean, or a callable.
    |
    */

    'enabled' => env('RL_ENABLE', false),

    /*
    | The names of the attributes that should not be logged.
    */
    'except' => [
        'password',
        'password_confirmation',
    ],

    /*
    | Casts options.
    */
    'casts' => [
        'compress_html' => env('RL_COMPRESS_HTML', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Store Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the store below you wish to use
    | as your default store for all requests logging work.
    |
    */

    'default' => env('RL_STORE', 'database'),

    'stores' => [

        'database' => [
            'connection' => null,
            'table' => 'requests_logs',
        ],

    ]
];