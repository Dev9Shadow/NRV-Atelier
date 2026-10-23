<?php
namespace nrv\api\actions;

use nrv\core\application\usecases\ServicePanier;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SupprimerLignePanierAction
{
    public function __construct(private ServicePanier $servicePanier) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->servicePanier->supprimerLigne($request->getAttribute('utilisateur')->id, (int)$args['id']);

        return $response->withStatus(204);
    }
}
