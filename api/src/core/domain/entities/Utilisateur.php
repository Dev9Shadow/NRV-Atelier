<?php

namespace nrv\core\domain\entities;

class Utilisateur
{
    public ?int $id;
    public string $email;
    public string $password;
    public string $nom;
    public string $prenom;
    public string $role;

    public function __construct(string $email, string $password, string $nom, string $prenom, string $role = 'USER', ?int $id = null)
    {
        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->role = $role;
    }
}