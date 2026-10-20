<?php
namespace Nrv\api\actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Nrv\core\application\usecases\ServiceCatalogue;

class GetSpectacleByIdAction {
    private ServiceCatalogue $catalogue;
    public function __construct(ServiceCatalogue $catalogue) { $this->catalogue = $catalogue; }

    public function __invoke(Request $request, Response $response, array $args): Response {
        $spectacle = $this->catalogue->getSpectacle((int)$args['id']);
        $data = [
            "type" => "resource",
            "spectacle" => $spectacle,
            "links" => [
                "self" => ["href" => "/spectacles/" . $args['id']],
                "soiree" => ["href" => "/soirees/" . $spectacle['id_soiree']]
            ]
        ];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}