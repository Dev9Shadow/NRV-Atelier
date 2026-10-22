<?php

namespace nrv\core\application\ports;

use nrv\core\domain\entities\Utilisateur;

interface UtilisateurRepositoryInterface
{
    public function findByEmail(string $email): ?Utilisateur;
    public function save(Utilisateur $utilisateur): int;
}