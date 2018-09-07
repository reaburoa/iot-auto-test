<?php

return [
    'scan_box' => [
        'host' => env('SCANBOX_EMQTT_HOST', 'mqtt://scanbox.sunmi.com:30111'),
        'data_client' => [
            'client_id' => 'ScanBox Data',
            "username" => 'ecanboxServerData',
            'password' => 'ecanboxServerData',
        ],
        'active_client' => [
            'client_id' => 'ScanBox Tester',
            "username" => 'ecanboxServerActive',
            'password' => 'ecanboxServerActive',
        ]
    ],
    'mario' => [
        'host' => env('MARIO_EMQTT_HOST', 'mqtt://mario.sunmi.com:81'),
        'data_client' => [
            'client_id' => 'marioServerData',
            "username" => 'marioServerData',
            'password' => 'marioServerData',
        ],
        'active_client' => [
            'client_id' => 'marioServerActive',
            "username" => 'marioServerActive',
            'password' => 'marioServerActive',
        ],
        'watchdog_client' => [
            'client_id' => 'marioServerWatchdog',
            "username" => 'marioServerWatchdog',
            'password' => 'marioServerWatchdog',
        ]
    ],
];