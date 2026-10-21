<?php
namespace nrv\core\application\ports;

use nrv\core\domain\entities\Spectacle;

interface SpectacleRepositoryInterface
{
    /**
     * @param array{date?: string, style?: int, soiree?: int} $filtres
     * @return Spectacle[]
     */
    public function findAll(array $filtres = []): array;

    public function findById(int $id): ?Spectacle;

    /** @return Spectacle[] */
    public function findBySoiree(int $idSoiree): array;
}
