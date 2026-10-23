<?php
namespace nrv\api\actions;

use nrv\core\application\usecases\ServicePaiement;
use nrv\core\domain\entities\Commande;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class GetCommandeAction
{
    public function __construct(private ServicePaiement $servicePaiement) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $commande = $this->servicePaiement->getCommande(
            (int)$args['id'],
            $request->getAttribute('utilisateur')->id
        );

        $response->getBody()->write(json_encode(self::ressource($commande), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public static function ressource(array $commande): array
    {
        $id = $commande['id'];
        $commande['billets'] = array_map(fn(array $b) => [
            'billet' => [
                'reference' => $b['reference'],
                'typeBillet' => $b['typeBillet'],
                'categorie' => $b['categorie'],
                'prix' => $b['prix'],
            ],
            'links' => ['self' => ['href' => '/billets/' . $b['token']]],
        ], $commande['billets']);

        $links = ['self' => ['href' => "/commandes/$id"]];
        if ($commande['statut'] === Commande::VALIDEE) {
            $links['paiement'] = ['href' => "/commandes/$id/paiement"];
        }

        return ['type' => 'resource', 'commande' => $commande, 'links' => $links];
    }
}
