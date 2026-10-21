<?php
namespace nrv\infrastructure\repositories;

use nrv\core\application\ports\SoireeRepositoryInterface;
use nrv\core\application\ports\SpectacleRepositoryInterface;
use nrv\core\domain\entities\Lieu;
use nrv\core\domain\entities\Soiree;
use PDO;

class PDOSoireeRepository implements SoireeRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
        private SpectacleRepositoryInterface $spectacleRepository
    ) {}

    public function findById(int $id): ?Soiree
    {
        $stmt = $this->pdo->prepare(<<<SQL
            SELECT so.id, so.nom, so.thematique, TO_CHAR(so.date, 'YYYY-MM-DD') AS date,
                   TO_CHAR(so.heure_debut, 'HH24:MI') AS heure_debut,
                   so.nb_places_total, so.nb_places_vendues,
                   l.id AS id_lieu, l.nom AS lieu_nom, l.adresse AS lieu_adresse, l.capacite AS lieu_capacite
            FROM soiree so
            JOIN lieu l ON l.id = so.id_lieu
            WHERE so.id = :id
            SQL);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($ligne === false) {
            return null;
        }

        return new Soiree(
            (int)$ligne['id'],
            $ligne['nom'],
            $ligne['thematique'],
            $ligne['date'],
            $ligne['heure_debut'],
            (int)$ligne['nb_places_total'],
            (int)$ligne['nb_places_vendues'],
            new Lieu((int)$ligne['id_lieu'], $ligne['lieu_nom'], $ligne['lieu_adresse'], (int)$ligne['lieu_capacite']),
            $this->spectacleRepository->findBySoiree($id)
        );
    }
}
