<?php

return [
    'settings' => [
        'displayErrorDetails' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'logErrors' => true,
        'logErrorDetails' => true,
        'jwt_secret' => $_ENV['JWT_SECRET'] ?? 'secret_par_defaut',
        'jwt_lifetime' => 10800, // 3 heures
        'db' => [
            'host' => $_ENV['DB_HOST'] ?? 'mysql',
            'dbname' => $_ENV['DB_NAME'] ?? 'nrv',
            'user' => $_ENV['DB_USER'] ?? 'nrv',
            'pass' => $_ENV['DB_PASS'] ?? 'nrv',
        ]
    ]
];