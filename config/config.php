<?php

declare(strict_types=1);

return [

    'app' => [
        'name' => 'BT Queue Enterprise',
        'version' => '4.0.0',
        'timezone' => 'America/Bahia',
        'debug' => false
    ],

    'database' => [
        'driver' => 'mariadb',
        'host' => '127.0.0.1',
        'dbname' => 'bt_enterprise_saas',
        'username' => 'bt_saas_user',
        'password' => 'BrandaoElite2026!',
        'charset' => 'utf8mb4'
    ],

    'license' => [
        'offline_days' => 7
    ],

    'sync' => [
        'enabled' => true,
        'endpoint' => 'http://api.brandaotech.com.br:8080',
        'token' => 'LITE-TOKEN-2026' // Substituir pelo token da VPS
    ],

    'logs' => [
        'path' => dirname(__DIR__) . '/logs'
    ]

];
