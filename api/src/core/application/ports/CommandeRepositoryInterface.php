<?php
namespace nrv\core\application\ports;

use nrv\core\domain\entities\Billet;
use nrv\core\domain\entities\Commande;
use nrv\core\domain\entities\LigneCommande;
use nrv\core\domain\exceptions\PlacesInsuffisantesException;

interface CommandeRepositoryInterface
{
    // Panier courant de l'utilisateur ou null si il en a pas
    public function findPanierByUtilisateur(int $idUtilisateur): ?Commande;

    // commande avec ses lignes ou null
    public function findById(int $idCommande): ?Commande;

    // crée un panier vide pour l'user
    public function creerPanier(int $idUtilisateur): Commande;

    // ajoute une ligne a la commande
    public function ajouterLigne(int $idCommande, LigneCommande $ligne): int;

    public function modifierQuantite(int $idLigne, int $quantite): void;

    public function supprimerLigne(int $idLigne): void;

    public function changerStatut(int $idCommande, string $statut): void;

    public function enregistrerPaiement(Commande $commande, array $billets): void;
}
