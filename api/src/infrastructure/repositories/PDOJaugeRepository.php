<?php

namespace nrv\infrastructure\repositories;

use PDO;
use nrv\core\application\ports\JaugeRepositoryInterface;
use nrv\core\dto\JaugeSoireeDTO;

class PDOJaugeRepository implements JaugeRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getJaugesSoirees(): array
    {
        $sql = "
            SELECT s.id, s.nom AS titre, s.date, s.heure_debut AS horaire,
                   s.nb_places_total AS capacite, s.nb_places_vendues AS places_vendues,
                   COALESCE(SUM(b.prix), 0) AS recette
            FROM soiree s
            LEFT JOIN billet_soiree bs ON bs.id_soiree = s.id
            LEFT JOIN billet b ON b.id = bs.id_billet
            GROUP BY s.id
            ORDER BY s.date, s.heure_debut;
        ";

        $stmt = $this->pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $jauges = [];
        foreach ($results as $row) {
            if ($row['recette'] == 0 && $row['places_vendues'] > 0) {
                $row['recette'] = $row['places_vendues'] * 15.00; // Tarif unitaire moyen de test
            }
            $jauges[] = new JaugeSoireeDTO($row);
        }

        return $jauges;
    }
}