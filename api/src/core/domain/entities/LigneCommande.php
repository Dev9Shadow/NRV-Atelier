<?php
namespace nrv\core\domain\entities;

use nrv\core\domain\exceptions\ValidationException;

class LigneCommande
{
    public const SIMPLE = 'SIMPLE';
    public const PASS_3J = 'PASS_3J';

    /**
     * @param int[] $idSoirees 1 soirée pour un billet simple, 3 soirées distinctes pour un pass
     * @throws ValidationException
     */
    public function __construct(
        public ?int $id,
        public readonly string $typeBillet,
        public readonly string $categorie,
        public int $quantite,
        public readonly float $prixUnitaire,
        public readonly array $idSoirees
    ) {
        if (!in_array($typeBillet, [self::SIMPLE, self::PASS_3J], true)) {
            throw new ValidationException("Type de billet inconnu : $typeBillet");
        }
        if (!in_array($categorie, [Tarif::NORMAL, Tarif::REDUIT], true)) {
            throw new ValidationException("Catégorie de tarif inconnue : $categorie");
        }
        self::validerQuantite($quantite);
        self::validerSoirees($typeBillet, $idSoirees);
    }

    /**
     * Crée une nouvelle ligne au prix du tarif en vigueur (billet simple ou pass 3 jours)
     * @param int[] $idSoirees
     * @throws ValidationException
     */
    public static function creer(string $typeBillet, Tarif $tarif, array $idSoirees, int $quantite): self
    {
        $prix = $typeBillet === self::PASS_3J ? $tarif->prixPass3Jours() : $tarif->prixBilletSimple();
        return new self(null, $typeBillet, $tarif->categorie, $quantite, $prix, array_values($idSoirees));
    }

    /**
     * Règle de quantité, aussi utilisée quand on modifie la quantité d'une ligne du panier
     * @throws ValidationException
     */
    public static function validerQuantite(int $quantite): void
    {
        if ($quantite <= 0) {
            throw new ValidationException('La quantité doit être au moins égale à 1');
        }
    }

    public function sousTotal(): float
    {
        return round($this->prixUnitaire * $this->quantite, 2);
    }

    public function estUnPass(): bool
    {
        return $this->typeBillet === self::PASS_3J;
    }

    private static function validerSoirees(string $typeBillet, array $idSoirees): void
    {
        foreach ($idSoirees as $id) {
            if (!is_int($id) || $id <= 0) {
                throw new ValidationException('Identifiant de soirée invalide');
            }
        }

        if ($typeBillet === self::SIMPLE && count($idSoirees) !== 1) {
            throw new ValidationException('Un billet simple porte sur une seule soirée');
        }
        if ($typeBillet === self::PASS_3J) {
            if (count($idSoirees) !== 3) {
                throw new ValidationException('Un pass 3 jours porte sur exactement 3 soirées');
            }
            if (count(array_unique($idSoirees)) !== 3) {
                throw new ValidationException('Les 3 soirées d\'un pass doivent être distinctes');
            }
        }
    }
}
