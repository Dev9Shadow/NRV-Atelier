<?php
namespace Nrv\api\actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Nrv\core\application\usecases\ServiceCatalogue;

class GetSoireeByIdAction {
    private ServiceCatalogue $catalogue;
    public function __construct(ServiceCatalogue $catalogue) { $this->catalogue = $catalogue; }

    public function __invoke(Request $request, Response $response, array $args): Response {
        $soiree = $this->catalogue->getSoiree((int)$args['id']);
        $data = [
            "type" => "resource",
            "soiree" => $soiree,
            "links" => [
                "self" => ["href" => "/soirees/" . $args['id']],
                "spectacles" => ["href" => "/spectacles?soiree=" . $args['id']]
            ]
        ];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}