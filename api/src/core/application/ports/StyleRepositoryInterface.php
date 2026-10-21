<?php
namespace nrv\core\application\ports;

use nrv\core\domain\entities\Style;

interface StyleRepositoryInterface
{
    /** @return Style[] */
    public function findAll(): array;

    public function findById(int $id): ?Style;
}
