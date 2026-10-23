<?php
namespace nrv\api\actions;

use nrv\core\application\usecases\ServicePanier;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class GetPanierAction
{
    public function __construct(private ServicePanier $servicePanier) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $panier = $this->servicePanier->getPanier($request->getAttribute('utilisateur')->id);

        return self::repondre($response, self::ressource($panier), 200);
    }

    public static function ressource(array $panier): array
    {
        return [
            'type' => 'resource',
            'panier' => $panier,
            'links' => [
                'self' => ['href' => '/panier'],
                'lignes' => ['href' => '/panier/lignes'],
                'validation' => ['href' => '/panier/validation'],
            ],
        ];
    }

    public static function repondre(Response $response, array $data, int $statut): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($statut);
    }
}
