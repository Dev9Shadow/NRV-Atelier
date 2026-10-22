<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use nrv\api\middlewares\CorsMiddleware;
use nrv\api\handlers\JsonErrorHandler;

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../config/dependencies.php');
$containerContainer = $containerBuilder->build();

AppFactory::setContainer($containerContainer);
$app = AppFactory::create();

$settings = $containerContainer->get('settings');

// Middleware CORS
$app->add(CorsMiddleware::class);
$app->addBodyParsingMiddleware();

// Routing
$routes = require __DIR__ . '/../config/routes.php';
$routes($app);

// Error Middleware
$errorMiddleware = $app->addErrorMiddleware(
    $settings['displayErrorDetails'],
    true,
    true
);

$errorHandler = new JsonErrorHandler($app, $settings['displayErrorDetails']);
$errorMiddleware->setDefaultErrorHandler($errorHandler);

$app->run();