<?php

namespace nrv\core\application\usecases;

use nrv\core\application\ports\UtilisateurRepositoryInterface;
use nrv\core\application\ports\AuthTokenProviderInterface;
use nrv\core\domain\entities\Utilisateur;
use nrv\core\dto\UtilisateurDTO;
use nrv\core\domain\exceptions\NonAuthentifieException;
use nrv\core\domain\exceptions\ValidationException;

class ServiceAuth
{
    private UtilisateurRepositoryInterface $repo;
    private AuthTokenProviderInterface $tokenProvider;

    public function __construct(UtilisateurRepositoryInterface $repo, AuthTokenProviderInterface $tokenProvider)
    {
        $this->repo = $repo;
        $this->tokenProvider = $tokenProvider;
    }

    public function signin(string $email, string $password): array
    {
        $user = $this->repo->findByEmail($email);
        if (!$user || !password_verify($password, $user->password)) {
            throw new NonAuthentifieException("Identifiants invalides");
        }

        $dto = new UtilisateurDTO($user);
        $token = $this->tokenProvider->createToken($dto);

        return [
            'type' => 'resource',
            'token' => $token,
            'utilisateur' => $dto->toArray()
        ];
    }

    public function signup(array $data): UtilisateurDTO
    {
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException("Email invalide");
        }
        if (empty($data['password']) || mb_strlen($data['password']) < 8) {
            throw new ValidationException("Mot de passe trop court (minimum 8 caractères)");
        }
        if (empty($data['nom']) || empty($data['prenom'])) {
            throw new ValidationException("Nom et prénom requis");
        }

        if ($this->repo->findByEmail($data['email'])) {
            throw new ValidationException("Cet email est déjà utilisé");
        }

        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $user = new Utilisateur(
            $data['email'],
            $hashedPassword,
            $data['nom'],
            $data['prenom'],
            'USER'
        );

        $id = $this->repo->save($user);
        $user->id = $id;

        return new UtilisateurDTO($user);
    }
}