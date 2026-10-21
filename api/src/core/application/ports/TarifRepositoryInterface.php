<?php
namespace nrv\core\application\ports;

use nrv\core\domain\entities\Tarif;

interface TarifRepositoryInterface
{
    /** @return array<string, Tarif> indexé par catégorie (NORMAL, REDUIT) */
    public function findAll(): array;
}
