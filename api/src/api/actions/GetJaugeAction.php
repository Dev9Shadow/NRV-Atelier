<?php

namespace nrv\api\actions;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use nrv\core\application\usecases\ServiceBackoffice;

class GetJaugeAction
{
    private ServiceBackoffice $serviceBackoffice;

    public function __construct(ServiceBackoffice $serviceBackoffice)
    {
        $this->serviceBackoffice = $serviceBackoffice;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $jauges = $this->serviceBackoffice->getJaugesSoirees();

        $data = [
            "type" => "collection",
            "count" => count($jauges),
            "soirees" => $jauges
        ];

        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}