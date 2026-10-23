<?php

namespace nrv\core\dto;

class JaugeSoireeDTO
{
    public int $id;
    public string $titre;
    public string $date;
    public string $horaire;
    public int $capacite;
    public int $places_vendues;
    public float $recette;
    public float $taux_remplissage;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? 0;
        $this->titre = $data['titre'] ?? '';
        $this->date = $data['date'] ?? '';
        $this->horaire = $data['horaire'] ?? '';
        $this->capacite = (int)($data['capacite'] ?? 0);
        $this->places_vendues = (int)($data['places_vendues'] ?? 0);
        $this->recette = (float)($data['recette'] ?? 0.0);
        
        // Calcul du taux de remplissage dans le use case / DTO (pas en SQL)
        $this->taux_remplissage = $this->capacite > 0 
            ? round(($this->places_vendues / $this->capacite) * 100, 2) 
            : 0.0;
    }
}