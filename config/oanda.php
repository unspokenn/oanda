<?php

return [
    'env' => '0', // 0 = Demo 1 = Production
//    'api_key' => [
//        'key' => env('OANDA_KEY'),
//        'user' => env('OANDA_USER')
//    ],
    'api_keys' => [ //multiple keys for fail over
        ['key' => env('OANDA_KEY'), 'user' => env('OANDA_USER')],
        ['key' => env('OANDA_KEY_1'), 'user' => env('OANDA_USER_1')],
        ['key' => env('OANDA_KEY_2'), 'user' => env('OANDA_USER_2')],
        ['key' => env('OANDA_KEY_3'), 'user' => env('OANDA_USER_3')],
        ['key' => env('OANDA_KEY_4'), 'user' => env('OANDA_USER_4')],
    ],

    'logging.channels' => [
        'oanda' => [
            'driver' => 'single',
            'path' => storage_path('logs/oanda.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ]]
];
