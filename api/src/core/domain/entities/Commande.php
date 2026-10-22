<?php
namespace nrv\core\domain\entities;

use nrv\core\domain\exceptions\EntityNotFoundException;
use nrv\core\domain\exceptions\TransitionStatutException;


class Commande
{
    public const PANIER = 'PANIER';
    public const VALIDEE = 'VALIDEE';
    public const PAYEE = 'PAYEE';

    /**
     * @param LigneCommande[] $lignes
     */
    public function __construct(
        public readonly int $id,
        public readonly int $idUtilisateur,
        public string $statut,
        public array $lignes = [],
        public readonly ?\DateTimeImmutable $dateCreation = null
    ) {}

    public function montantTotal(): float
    {
        return round(array_sum(array_map(fn(LigneCommande $l) => $l->sousTotal(), $this->lignes)), 2);
    }

    // seul le panier est modifiable
    public function estModifiable(): bool
    {
        return $this->statut === self::PANIER;
    }

    public function estVide(): bool
    {
        return $this->lignes === [];
    }

    public function appartientA(int $idUtilisateur): bool
    {
        return $this->idUtilisateur === $idUtilisateur;
    }

    public function ligne(int $idLigne): LigneCommande
    {
        foreach ($this->lignes as $ligne) {
            if ($ligne->id === $idLigne) {
                return $ligne;
            }
        }
        throw new EntityNotFoundException("Ligne $idLigne introuvable dans la commande {$this->id}"); // si la ligne n'est pas dans une commande
    }

    public function valider(): void
    {
        if ($this->statut !== self::PANIER) {
            throw new TransitionStatutException("Seul un panier peut être validé (statut actuel : {$this->statut})");
        }
        if ($this->estVide()) {
            throw new TransitionStatutException('Impossible de valider un panier vide');
        }
        $this->statut = self::VALIDEE;
    }

    public function payer(): void
    {
        if ($this->statut !== self::VALIDEE) {
            throw new TransitionStatutException("Seule une commande validée peut être payée (statut actuel : {$this->statut})");
        }
        $this->statut = self::PAYEE;
    }

    /**
     * Places demandées par soirée, toutes lignes confondues.
     * Un pass compte 1 place dans chacune de ses 3 soirées, multipliée par la quantité.
     * @return array<int, int>
     */
    public function placesParSoiree(): array
    {
        $places = [];
        foreach ($this->lignes as $ligne) {
            foreach ($ligne->idSoirees as $idSoiree) {
                $places[$idSoiree] = ($places[$idSoiree] ?? 0) + $ligne->quantite;
            }
        }
        ksort($places);
        return $places;
    }
}
