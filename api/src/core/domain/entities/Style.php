<?php
namespace nrv\core\domain\entities;

class Style
{
    public function __construct(
        public readonly int $id,
        public readonly string $libelle
    ) {}
}
