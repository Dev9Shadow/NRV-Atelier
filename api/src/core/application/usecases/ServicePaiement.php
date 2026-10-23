<?php
namespace nrv\core\application\usecases;

use nrv\core\application\ports\BilletRepositoryInterface;
use nrv\core\application\ports\CommandeRepositoryInterface;
use nrv\core\application\ports\SoireeRepositoryInterface;
use nrv\core\domain\entities\Billet;
use nrv\core\domain\entities\CarteBancaire;
use nrv\core\domain\entities\Commande;
use nrv\core\domain\exceptions\AccesInterditException;
use nrv\core\domain\exceptions\EntityNotFoundException;
use nrv\core\dto\CommandeDTO;

class ServicePaiement
{
    public function __construct(
        private CommandeRepositoryInterface $commandeRepository,
        private BilletRepositoryInterface $billetRepository,
        private SoireeRepositoryInterface $soireeRepository
    ) {}

    public function payer(int $idCommande, int $idUtilisateur, CarteBancaire $carte): array
    {
        $commande = $this->commandeDe($idCommande, $idUtilisateur);
        $commande->payer();

        $billets = [];
        foreach ($commande->lignes as $ligne) {
            for ($i = 0; $i < $ligne->quantite; $i++) {
                $billets[] = Billet::creer($ligne, $commande->id);
            }
        }

        $this->commandeRepository->enregistrerPaiement($commande, $billets);

        return $this->enTableau($commande, $billets);
    }

    public function getCommande(int $idCommande, int $idUtilisateur): array
    {
        $commande = $this->commandeDe($idCommande, $idUtilisateur);
        $billets = $commande->statut === Commande::PAYEE
            ? $this->billetRepository->findByCommande($idCommande)
            : [];

        return $this->enTableau($commande, $billets);
    }

    private function commandeDe(int $idCommande, int $idUtilisateur): Commande
    {
        $commande = $this->commandeRepository->findById($idCommande);
        if ($commande === null) {
            throw new EntityNotFoundException("Commande $idCommande introuvable");
        }
        if (!$commande->appartientA($idUtilisateur)) {
            throw new AccesInterditException('Cette commande ne vous appartient pas');
        }
        return $commande;
    }

    private function enTableau(Commande $commande, array $billets): array
    {
        $soirees = [];
        foreach ($commande->lignes as $ligne) {
            foreach ($ligne->idSoirees as $idSoiree) {
                $soirees[$idSoiree] ??= $this->soireeRepository->findById($idSoiree);
            }
        }

        $donnees = (new CommandeDTO($commande, array_filter($soirees)))->toArray();
        $donnees['billets'] = array_map(fn(Billet $b) => [
            'reference' => $b->reference,
            'typeBillet' => $b->typeBillet,
            'categorie' => $b->categorie,
            'prix' => $b->prix,
            'token' => $b->token,
        ], $billets);

        return $donnees;
    }
}
