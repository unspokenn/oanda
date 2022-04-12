<?php

return [
    'environment' => 2, // 2 = Demo 1 = Production
    'api_key' => [
        'key' => env('OANDA_KEY'),
        'account' => env('OANDA_USER')
    ],

    'logging.channels' => [
        'oanda' => [
            'driver' => 'single',
            'path' => storage_path('logs/oanda.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ]
    ]
];
