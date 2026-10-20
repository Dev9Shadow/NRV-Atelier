<?php
namespace nrv\core\domain\entities;

class Soiree
{
    /**
     * @param Spectacle[] $spectacles
     */
    public function __construct(
        public readonly int $id,
        public readonly string $nom,
        public readonly string $thematique,
        public readonly string $date,
        public readonly string $heureDebut,
        public readonly int $nbPlacesTotal,
        public readonly int $nbPlacesVendues,
        public readonly Lieu $lieu,
        public readonly array $spectacles = []
    ) {}

    public function placesRestantes(): int
    {
        return max(0, $this->nbPlacesTotal - $this->nbPlacesVendues);
    }

    public function estComplete(): bool
    {
        return $this->placesRestantes() === 0;
    }
}
