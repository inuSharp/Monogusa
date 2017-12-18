<?php
$system_mode = 'develop';
$settings = [
    'develop' => [
        'DB_HOST'          => 'localhost',
        'DB_PORT'          => '3306',
        'DB_NAME'          => '',
        'DB_USER'          => '',
        'DB_PASS'          => '',
        'app_key'          => '',
        'use_registration' => false,
        'debug'            => false,
        'mesure_time'      => true,
        'mesure_memory'    => true,
    ],
    'product' => [
        'DB_HOST'          => 'localhost',
        'DB_PORT'          => '3306',
        'DB_NAME'          => '',
        'DB_USER'          => '',
        'DB_PASS'          => '',
        'app_key'          => '',
        'use_registration' => false,
        'debug'            => false,
        'mesure_time'      => true,
        'mesure_memory'    => true,
    ],
];
return $settings[$system_mode];

