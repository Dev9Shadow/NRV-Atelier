<?php
namespace nrv\core\domain\entities;

class Lieu
{
    public function __construct(
        public readonly int $id,
        public readonly string $nom,
        public readonly string $adresse,
        public readonly int $capacite
    ) {}
}
