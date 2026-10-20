<?php
namespace nrv\core\dto;

use nrv\core\domain\entities\Spectacle;

/** Spectacle tel qu'affiché dans la liste du programme */
class SpectacleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $titre,
        public readonly string $date,
        public readonly string $horaire,
        public readonly ?string $image,
        public readonly string $style,
        public readonly int $idSoiree
    ) {}

    public static function fromEntity(Spectacle $s): self
    {
        return new self($s->id, $s->titre, $s->date, $s->horaire, $s->imagePrincipale(), $s->style->libelle, $s->idSoiree);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'date' => $this->date,
            'horaire' => $this->horaire,
            'image' => $this->image,
            'style' => $this->style,
            'id_soiree' => $this->idSoiree,
        ];
    }
}
