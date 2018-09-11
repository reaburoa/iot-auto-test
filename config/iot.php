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
        'host' => env('MARIO_EMQTT_HOST', 'mqtt://47.99.116.111:1883'),
        'data_client' => [
            'client_id' => 'marioServerData-auto-test',
            "username" => 'marioServerData',
            'password' => 'marioServerData',
        ],
        'active_client' => [
            'client_id' => 'marioServerActive-auto-test',
            "username" => 'marioServerActive',
            'password' => 'marioServerActive',
        ],
        'watchdog_client' => [
            'client_id' => 'marioServerWatchdog-auto-test',
            "username" => 'marioServerWatchdog',
            'password' => 'marioServerWatchdog',
        ]
    ],
];