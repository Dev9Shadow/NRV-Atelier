<?php
namespace nrv\api\actions;

use nrv\core\application\usecases\ServicePanier;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ModifierLignePanierAction
{
    public function __construct(private ServicePanier $servicePanier) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $corps = $request->getParsedBody();
        $corps = is_array($corps) ? $corps : [];

        $panier = $this->servicePanier->modifierQuantite(
            $request->getAttribute('utilisateur')->id,
            (int)$args['id'],
            QuantiteValide::depuis($corps['quantite'] ?? null)
        );

        return GetPanierAction::repondre($response, GetPanierAction::ressource($panier), 200);
    }
}
