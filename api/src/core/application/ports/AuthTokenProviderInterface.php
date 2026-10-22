<?php

namespace nrv\core\application\ports;

use nrv\core\dto\UtilisateurDTO;

interface AuthTokenProviderInterface
{
    public function createToken(UtilisateurDTO $utilisateur): string;
    public function decodeToken(string $token): ?UtilisateurDTO;
}