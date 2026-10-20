<?php
namespace nrv\core\domain\entities;

class Tarif
{
    public const NORMAL = 'NORMAL';
    public const REDUIT = 'REDUIT';

    /** Réduction appliquée sur un pass 3 jours */
    public const REDUCTION_PASS_3J = 0.03;

    public function __construct(
        public readonly string $categorie,
        public readonly float $prix
    ) {
        if (!in_array($categorie, [self::NORMAL, self::REDUIT], true)) {
            throw new \InvalidArgumentException("Catégorie de tarif inconnue : $categorie");
        }
        if ($prix < 0) {
            throw new \InvalidArgumentException("Un tarif ne peut pas être négatif");
        }
    }

    /** Prix d'un billet simple (1 soirée) */
    public function prixBilletSimple(): float
    {
        return $this->prix;
    }

    /** Prix d'un pass 3 jours : 3 soirées avec 3 % de réduction, arrondi au centime */
    public function prixPass3Jours(): float
    {
        return round($this->prix * 3 * (1 - self::REDUCTION_PASS_3J), 2);
    }
}
