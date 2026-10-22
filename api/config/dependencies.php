<?php

use Psr\Container\ContainerInterface;

return [
    'settings' => function(ContainerInterface $c) {
        $settings = require __DIR__ . '/settings.php';
        return $settings['settings'];
    },

    PDO::class => function(ContainerInterface $c) {
        $settings = $c->get('settings')['db'];
        $dsn = "pgsql:host={$settings['host']};dbname={$settings['dbname']}";
        $pdo = new PDO($dsn, $settings['user'], $settings['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    },
];