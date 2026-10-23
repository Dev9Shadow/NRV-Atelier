<?php
namespace nrv\core\application\ports;

use nrv\core\domain\entities\Soiree;

interface SoireeRepositoryInterface
{
    /** Retourne la soirée avec son lieu et ses spectacles, ou null */
    public function findById(int $id): ?Soiree;

    /** @return Soiree[] */
    public function findAll(): array;
}
