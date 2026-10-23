<?php
namespace nrv\api\actions;

use nrv\core\application\usecases\ServicePanier;
use nrv\core\domain\exceptions\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AjouterLignePanierAction
{
    public function __construct(private ServicePanier $servicePanier) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $corps = $request->getParsedBody();
        $corps = is_array($corps) ? $corps : [];

        $typeBillet = $corps['typeBillet'] ?? null;
        $categorie = $corps['categorie'] ?? null;
        $soirees = $corps['soirees'] ?? null;

        if (!is_string($typeBillet) || !is_string($categorie)) {
            throw new ValidationException('typeBillet et categorie sont obligatoires');
        }
        if (!is_array($soirees) || $soirees === []) {
            throw new ValidationException('soirees doit être une liste d\'identifiants de soirées');
        }
        foreach ($soirees as $id) {
            if (filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                throw new ValidationException('Identifiant de soirée invalide');
            }
        }

        $panier = $this->servicePanier->ajouterLigne(
            $request->getAttribute('utilisateur')->id,
            $typeBillet,
            $categorie,
            array_map('intval', array_values($soirees)),
            QuantiteValide::depuis($corps['quantite'] ?? null)
        );

        return GetPanierAction::repondre($response, GetPanierAction::ressource($panier), 201);
    }
}
