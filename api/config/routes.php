<?php

use Slim\App;
use nrv\api\actions\GetSpectaclesAction;
use nrv\api\actions\GetSpectacleByIdAction;
use nrv\api\actions\GetSoireeByIdAction;
use nrv\api\actions\GetStylesAction;

return function (App $app) {
    $app->get('/spectacles', GetSpectaclesAction::class);
    $app->get('/spectacles/{id:[0-9]+}', GetSpectacleByIdAction::class);
    $app->get('/soirees/{id:[0-9]+}', GetSoireeByIdAction::class);
    $app->get('/styles', GetStylesAction::class);
};