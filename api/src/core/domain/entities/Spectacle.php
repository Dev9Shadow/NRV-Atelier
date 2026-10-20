<?php
namespace nrv\core\domain\entities;

class Spectacle
{
    /**
     * @param string[] $artistes
     * @param string[] $images
     */
    public function __construct(
        public readonly int $id,
        public readonly string $titre,
        public readonly string $description,
        public readonly ?string $urlVideo,
        public readonly string $horaire,
        public readonly int $idSoiree,
        public readonly string $date,
        public readonly Style $style,
        public readonly array $artistes = [],
        public readonly array $images = []
    ) {}

    public function imagePrincipale(): ?string
    {
        return $this->images[0] ?? null;
    }
}
