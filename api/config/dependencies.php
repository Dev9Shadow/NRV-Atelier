<?php

use Psr\Container\ContainerInterface;

use nrv\core\application\ports\SpectacleRepositoryInterface;
use nrv\core\application\ports\SoireeRepositoryInterface;
use nrv\core\application\ports\StyleRepositoryInterface;
use nrv\core\application\ports\TarifRepositoryInterface;
use nrv\core\application\ports\JaugeRepositoryInterface;

use nrv\infrastructure\repositories\PDOSpectacleRepository;
use nrv\infrastructure\repositories\PDOSoireeRepository;
use nrv\infrastructure\repositories\PDOStyleRepository;
use nrv\infrastructure\repositories\PDOTarifRepository;
use nrv\infrastructure\repositories\PDOJaugeRepository; 


return [
    SpectacleRepositoryInterface::class => function(ContainerInterface $c) {
        return new PDOSpectacleRepository($c->get(PDO::class));
    },

    SoireeRepositoryInterface::class => function(ContainerInterface $c) {
        return new PDOSoireeRepository(
                $c->get(PDO::class),
                $c->get(SpectacleRepositoryInterface::class) 
            );
    },

    StyleRepositoryInterface::class => function(ContainerInterface $c) {
        return new PDOStyleRepository($c->get(PDO::class));
    },

    TarifRepositoryInterface::class => function(ContainerInterface $c) {
        return new PDOTarifRepository($c->get(PDO::class));
    },

    JaugeRepositoryInterface::class => function (ContainerInterface $c) {
        return new PDOJaugeRepository($c->get(\PDO::class));
    },
    

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
    \nrv\core\application\ports\CommandeRepositoryInterface::class => \DI\autowire(\nrv\infrastructure\repositories\PDOCommandeRepository::class),
    \nrv\core\application\ports\BilletRepositoryInterface::class => \DI\autowire(\nrv\infrastructure\repositories\PDOBilletRepository::class),
    \nrv\core\application\ports\AuthTokenProviderInterface::class => \DI\autowire(\nrv\infrastructure\auth\JWTTokenProvider::class),
    \nrv\core\application\ports\UtilisateurRepositoryInterface::class => \DI\autowire(\nrv\infrastructure\repositories\PDOUtilisateurRepository::class),
];