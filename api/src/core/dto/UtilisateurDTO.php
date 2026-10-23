<?php

namespace nrv\core\dto;

use nrv\core\domain\entities\Utilisateur;

class UtilisateurDTO
{
    public int $id;
    public string $email;
    public string $nom;
    public string $prenom;
    public string $role;

    public function __construct(Utilisateur $u)
    {
        $this->id = $u->id;
        $this->email = $u->email;
        $this->nom = $u->nom;
        $this->prenom = $u->prenom;
        $this->role = $u->role;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'role' => $this->role,
        ];
    }
}