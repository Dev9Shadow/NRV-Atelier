<?php
namespace nrv\api\actions;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use nrv\core\application\usecases\ServiceCatalogue;

class GetStylesAction {
    private ServiceCatalogue $catalogue;
    public function __construct(ServiceCatalogue $catalogue) { $this->catalogue = $catalogue; }

    public function __invoke(Request $request, Response $response, array $args): Response {
        $styles = $this->catalogue->listerStyles();
        $data = [
            "type" => "collection",
            "styles" => $styles
        ];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}