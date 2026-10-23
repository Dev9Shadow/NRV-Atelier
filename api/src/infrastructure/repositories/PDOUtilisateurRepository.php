<?php

namespace nrv\infrastructure\repositories;

use PDO;
use nrv\core\application\ports\UtilisateurRepositoryInterface;
use nrv\core\domain\entities\Utilisateur;

class PDOUtilisateurRepository implements UtilisateurRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByEmail(string $email): ?Utilisateur
    {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) return null;

        return new Utilisateur(
            $data['email'],
            $data['password_hash'],
            $data['nom'],
            $data['prenom'],
            $data['role'] ?? 'USER',
            (int)$data['id']
        );
    }

    public function save(Utilisateur $utilisateur): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO utilisateur (email, password_hash, nom, prenom, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $utilisateur->email,
            $utilisateur->password,
            $utilisateur->nom,
            $utilisateur->prenom,
            $utilisateur->role
        ]);
        return (int)$this->pdo->lastInsertId();
    }
}