<?php
namespace nrv\core\dto;

use nrv\core\domain\entities\Commande;
use nrv\core\domain\entities\LigneCommande;
use nrv\core\domain\entities\Soiree;

class CommandeDTO
{
    /**
     * @param array<int, Soiree> $soirees
     */
    public function __construct(
        private Commande $commande,
        private array $soirees
    ) {}

    public function toArray(): array
    {
        $c = $this->commande;
        return [
            'id' => $c->id,
            'statut' => $c->statut,
            'montantTotal' => $c->montantTotal(),
            'lignes' => array_map(fn(LigneCommande $l) => [
                'id' => $l->id,
                'typeBillet' => $l->typeBillet,
                'categorie' => $l->categorie,
                'quantite' => $l->quantite,
                'prixUnitaire' => $l->prixUnitaire,
                'sousTotal' => $l->sousTotal(),
                'soirees' => array_map(fn(int $id) => [
                    'id' => $id,
                    'nom' => $this->soirees[$id]->nom ?? null,
                    'date' => $this->soirees[$id]->date ?? null,
                ], $l->idSoirees),
                'links' => ['self' => ['href' => '/panier/lignes/' . $l->id]],
            ], $c->lignes),
        ];
    }
}
