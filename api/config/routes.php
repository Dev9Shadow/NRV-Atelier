<?php

use Slim\App;
use nrv\api\actions\GetSpectaclesAction;
use nrv\api\actions\GetSpectacleByIdAction;
use nrv\api\actions\GetSoireeByIdAction;
use nrv\api\actions\GetStylesAction;
use nrv\api\actions\GetCommandeAction;
use nrv\api\actions\PayerCommandeAction;
use nrv\api\actions\GetBilletAction;
use nrv\api\middlewares\AuthnMiddleware;

return function (App $app) {
    $app->get('/spectacles', GetSpectaclesAction::class);
    $app->get('/spectacles/{id:[0-9]+}', GetSpectacleByIdAction::class);
    $app->get('/soirees/{id:[0-9]+}', GetSoireeByIdAction::class);
    $app->get('/styles', GetStylesAction::class);
    $app->get('/commandes/{id:[0-9]+}', GetCommandeAction::class)->add(AuthnMiddleware::class);
    $app->post('/commandes/{id:[0-9]+}/paiement', PayerCommandeAction::class)->add(AuthnMiddleware::class);
    $app->get('/billets/{token:[a-f0-9]{64}}', GetBilletAction::class);
};