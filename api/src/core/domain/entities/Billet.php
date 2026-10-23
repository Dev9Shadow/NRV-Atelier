<?php
namespace nrv\core\domain\entities;


class Billet
{
    /**
     * @param int|null $id 
     * @param string|null $reference
     * @param string $token 
     * @param int[] $idSoirees 
     * @param string|null $nomAcheteur
     */
    public function __construct(
        public ?int $id,
        public ?string $reference,
        public readonly string $token,
        public readonly string $typeBillet,
        public readonly string $categorie,
        public readonly float $prix,
        public readonly int $idCommande,
        public readonly array $idSoirees,
        public readonly ?string $nomAcheteur = null
    ) {}

    public static function creer(LigneCommande $ligne, int $idCommande): self
    {
        return new self(
            null,
            null,
            bin2hex(random_bytes(32)),
            $ligne->typeBillet,
            $ligne->categorie,
            $ligne->prixUnitaire,
            $idCommande,
            $ligne->idSoirees
        );
    }

    public function estUnPass(): bool
    {
        return $this->typeBillet === LigneCommande::PASS_3J;
    }
}
