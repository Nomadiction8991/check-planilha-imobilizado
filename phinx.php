<?php
return [
    'paths' => [
        'migrations' => __DIR__ . '/database/migrations',
        'seeds'      => __DIR__ . '/database/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment'     => 'development',
        'development' => [
            'adapter' => 'mysql',
            'host'    => getenv('DB_HOST') ?: 'db',
            'name'    => getenv('DB_NAME') ?: 'checkplanilha',
            'user'    => getenv('DB_USER') ?: 'checkplanilha',
            'pass'    => getenv('DB_PASS') ?: 'checkplanilha123',
            'port'    => (int) (getenv('DB_PORT') ?: 3306),
            'charset' => 'utf8mb4',
        ],
    ],
    'version_order' => 'creation',
];
