<?php
namespace nrv\api\actions;

use nrv\core\application\usecases\ServicePaiement;
use nrv\core\domain\entities\CarteBancaire;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PayerCommandeAction
{
    public function __construct(private ServicePaiement $servicePaiement) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $corps = $request->getParsedBody();
        $corps = is_array($corps) ? $corps : [];
        $carte = CarteBancaire::depuis($corps['numero'] ?? null, $corps['expiration'] ?? null, $corps['cvc'] ?? null);

        $commande = $this->servicePaiement->payer(
            (int)$args['id'],
            $request->getAttribute('utilisateur')->id,
            $carte
        );

        $response->getBody()->write(json_encode(GetCommandeAction::ressource($commande), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
