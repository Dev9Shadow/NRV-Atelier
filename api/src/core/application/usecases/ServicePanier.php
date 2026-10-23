<?php
namespace nrv\core\application\usecases;

use nrv\core\application\ports\CommandeRepositoryInterface;
use nrv\core\application\ports\SoireeRepositoryInterface;
use nrv\core\application\ports\TarifRepositoryInterface;
use nrv\core\domain\entities\Commande;
use nrv\core\domain\entities\LigneCommande;
use nrv\core\domain\exceptions\EntityNotFoundException;
use nrv\core\domain\exceptions\PlacesInsuffisantesException;
use nrv\core\domain\exceptions\ValidationException;
use nrv\core\dto\CommandeDTO;

class ServicePanier
{
    public function __construct(
        private CommandeRepositoryInterface $commandeRepository,
        private SoireeRepositoryInterface $soireeRepository,
        private TarifRepositoryInterface $tarifRepository
    ) {}

    public function getPanier(int $idUtilisateur): array
    {
        return $this->enTableau($this->panierDe($idUtilisateur));
    }

    public function ajouterLigne(int $idUtilisateur, string $typeBillet, string $categorie, array $idSoirees, int $quantite): array
    {
        $tarifs = $this->tarifRepository->findAll();
        if (!isset($tarifs[$categorie])) {
            throw new ValidationException("Catégorie de tarif inconnue : $categorie");
        }

        $ligne = LigneCommande::creer($typeBillet, $tarifs[$categorie], $idSoirees, $quantite);
        $panier = $this->panierDe($idUtilisateur);
        $panier->lignes[] = $ligne;
        $this->verifierPlaces($panier);

        $ligne->id = $this->commandeRepository->ajouterLigne($panier->id, $ligne);

        return $this->enTableau($panier);
    }

    private function panierDe(int $idUtilisateur): Commande
    {
        return $this->commandeRepository->findPanierByUtilisateur($idUtilisateur)
            ?? $this->commandeRepository->creerPanier($idUtilisateur);
    }

    private function verifierPlaces(Commande $panier): void
    {
        foreach ($panier->placesParSoiree() as $idSoiree => $places) {
            $soiree = $this->soireeRepository->findById($idSoiree);
            if ($soiree === null) {
                throw new EntityNotFoundException("Soirée $idSoiree introuvable");
            }
            if ($places > $soiree->placesRestantes()) {
                throw new PlacesInsuffisantesException(
                    "Plus que {$soiree->placesRestantes()} place(s) disponible(s) pour {$soiree->nom}"
                );
            }
        }
    }

    private function enTableau(Commande $panier): array
    {
        $soirees = [];
        foreach ($panier->placesParSoiree() as $idSoiree => $places) {
            $soirees[$idSoiree] = $this->soireeRepository->findById($idSoiree);
        }

        return (new CommandeDTO($panier, array_filter($soirees)))->toArray();
    }
}
