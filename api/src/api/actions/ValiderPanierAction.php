<?php
namespace nrv\api\actions;

use nrv\core\application\usecases\ServicePanier;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ValiderPanierAction
{
    public function __construct(private ServicePanier $servicePanier) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $commande = $this->servicePanier->valider($request->getAttribute('utilisateur')->id);
        $id = $commande['id'];

        $data = [
            'type' => 'resource',
            'commande' => $commande,
            'links' => [
                'self' => ['href' => "/commandes/$id"],
                'paiement' => ['href' => "/commandes/$id/paiement"],
            ],
        ];

        return GetPanierAction::repondre($response, $data, 201);
    }
}
