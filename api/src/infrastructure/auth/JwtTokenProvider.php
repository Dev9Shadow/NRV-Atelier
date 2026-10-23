<?php

namespace nrv\infrastructure\auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use nrv\core\application\ports\AuthTokenProviderInterface;
use nrv\core\dto\UtilisateurDTO;
use nrv\core\domain\entities\Utilisateur;

class JwtTokenProvider implements AuthTokenProviderInterface
{
    private string $secret;
    private int $lifetime;

    public function __construct(string $secret, int $lifetime = 10800)
    {
        $this->secret = $secret;
        $this->lifetime = $lifetime;
    }

    public function createToken(UtilisateurDTO $utilisateur): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->lifetime;
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'sub' => $utilisateur->id,
            'email' => $utilisateur->email,
            'role' => $utilisateur->role,
            'nom' => $utilisateur->nom,
            'prenom' => $utilisateur->prenom
        ];
        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function decodeToken(string $token): ?UtilisateurDTO
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            $u = new Utilisateur(
                $decoded->email,
                '',
                $decoded->nom,
                $decoded->prenom,
                $decoded->role,
                $decoded->sub
            );
            return new UtilisateurDTO($u);
        } catch (\Exception $e) {
            return null;
        }
    }
}