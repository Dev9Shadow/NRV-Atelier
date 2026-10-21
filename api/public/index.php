<?php
use DI\Container;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require __DIR__ . '/../vendor/autoload.php';

// 1. Initialisation de Slim avec PHP-DI
$container = new Container();

// Enregistrement explicite pour garantir l'injection du ServiceCatalogue
$container->set(\nrv\core\application\usecases\ServiceCatalogue::class, function() {
    return new \nrv\core\application\usecases\ServiceCatalogue();
});

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

// 2. Middleware CORS (Autorise le front à faire des fetch)
$app->add(function (Request $request, $handler) {
    $response = $handler->handle($request);
    return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

// 3. Middleware d'Erreurs personnalisé (Format JSON du contrat)
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->forceContentType('application/json');

$customErrorHandler = function (Request $request, \Throwable $exception, bool $displayErrorDetails, bool $logErrors, bool $logErrorDetails) use ($app) {
    $code = $exception->getCode() !== 0 ? $exception->getCode() : 500;
    if ($exception instanceof \Slim\Exception\HttpNotFoundException) { $code = 404; }

    $payload = [
        'type' => 'error',
        'code' => $code,
        'message' => $exception->getMessage()
    ];
    $response = $app->getResponseFactory()->createResponse($code >= 400 ? $code : 500);
    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
};
$errorMiddleware->setDefaultErrorHandler($customErrorHandler);

// 4. Test temporaire de l'API
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode(['status' => 'API OK']));
    return $response->withHeader('Content-Type', 'application/json');
});

// Les routes de l'API
$app->get('/spectacles', \nrv\api\actions\GetSpectaclesAction::class);
$app->get('/spectacles/{id}', \nrv\api\actions\GetSpectacleByIdAction::class);
$app->get('/soirees/{id}', \nrv\api\actions\GetSoireeByIdAction::class);
$app->get('/styles', \nrv\api\actions\GetStylesAction::class);

$app->run();