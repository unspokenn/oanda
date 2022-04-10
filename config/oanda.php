<?php

return [
    'environment' => 2, // 2 = Demo 1 = Production
    'api_key' => [
        'key' => env('OANDA_KEY'),
        'account' => env('OANDA_USER')
    ],
];
