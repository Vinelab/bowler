<?php

return [

    'log' => [
        'message' => [
            'truncate_length' => 16000,
        ],
    ],

    'lifecycle_hooks' => [
        'fail_on_error' => false,
    ],

    'rabbitmq' => [
        'host' => trim(env('RABBITMQ_HOST', 'localhost')),
        'port' => trim(env('RABBITMQ_PORT', '5672')),
        'username' => trim(env('RABBITMQ_USERNAME', 'guest')),
        'password' => trim(env('RABBITMQ_PASSWORD', 'guest')),
        'connection_timeout' => 60,
        'read_write_timeout' => 60,
        'heartbeat' => 30,
        'vhost' => '/'
    ],

];
