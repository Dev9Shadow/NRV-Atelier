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
        // Requête SQL adaptée au schéma réel et validée sur le seed
        $sql = "
            SELECT 
                s.id,
                s.titre,
                s.date,
                s.horaire,
                s.capacite,
                COALESCE(bs_count.places_vendues, 0) + COALESCE(pass_count.places_vendues, 0) AS places_vendues,
                COALESCE(bs_recette.recette, 0) + COALESCE(pass_recette.recette, 0) AS recette
            FROM soiree s
            LEFT JOIN (
                SELECT b.soiree_id, COUNT(b.id) AS places_vendues
                FROM billet b
                WHERE b.type = 'simple'
                GROUP BY b.soiree_id
            ) bs_count ON s.id = bs_count.soiree_id
            LEFT JOIN (
                SELECT p.soiree1_id AS soiree_id, COUNT(b.id) AS places_vendues
                FROM billet b JOIN pass p ON b.pass_id = p.id WHERE b.type = 'pass' GROUP BY p.soiree1_id
                UNION ALL
                SELECT p.soiree2_id AS soiree_id, COUNT(b.id) AS places_vendues
                FROM billet b JOIN pass p ON b.pass_id = p.id WHERE b.type = 'pass' GROUP BY p.soiree2_id
                UNION ALL
                SELECT p.soiree3_id AS soiree_id, COUNT(b.id) AS places_vendues
                FROM billet b JOIN pass p ON b.pass_id = p.id WHERE b.type = 'pass' GROUP BY p.soiree3_id
            ) pass_count ON s.id = pass_count.soiree_id
            LEFT JOIN (
                SELECT b.soiree_id, SUM(b.tarif) AS recette
                FROM billet b
                WHERE b.type = 'simple'
                GROUP BY b.soiree_id
            ) bs_recette ON s.id = bs_recette.soiree_id
            LEFT JOIN (
                SELECT p.soiree1_id AS soiree_id, SUM(b.tarif / 3.0) AS recette
                FROM billet b JOIN pass p ON b.pass_id = p.id WHERE b.type = 'pass' GROUP BY p.soiree1_id
                UNION ALL
                SELECT p.soiree2_id AS soiree_id, SUM(b.tarif / 3.0) AS recette
                FROM billet b JOIN pass p ON b.pass_id = p.id WHERE b.type = 'pass' GROUP BY p.soiree2_id
                UNION ALL
                SELECT p.soiree3_id AS soiree_id, SUM(b.tarif / 3.0) AS recette
                FROM billet b JOIN pass p ON b.pass_id = p.id WHERE b.type = 'pass' GROUP BY p.soiree3_id
            ) pass_recette ON s.id = pass_recette.soiree_id
            GROUP BY s.id, s.titre, s.date, s.horaire, s.capacite
            ORDER BY s.date ASC, s.horaire ASC;
        ";

        $stmt = $this->pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $jauges = [];
        foreach ($results as $row) {
            $jauges[] = new JaugeSoireeDTO($row);
        }

        return $jauges;
    }
}