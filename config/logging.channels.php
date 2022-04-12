<?php

return [
    'oanda' => [
        'driver' => 'single',
        'path' => storage_path('logs/oanda.log'),
        'level' => env('LOG_LEVEL', 'debug'),
    ]
];
