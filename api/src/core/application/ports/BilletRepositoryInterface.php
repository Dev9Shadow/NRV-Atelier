<?php
namespace nrv\core\application\ports;

use nrv\core\domain\entities\Billet;

interface BilletRepositoryInterface
{
    public function findByToken(string $token): ?Billet;

    public function findByCommande(int $idCommande): array;
}
