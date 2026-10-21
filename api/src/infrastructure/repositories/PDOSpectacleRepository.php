<?php
namespace nrv\infrastructure\repositories;

use nrv\core\application\ports\SpectacleRepositoryInterface;
use nrv\core\domain\entities\Spectacle;
use nrv\core\domain\entities\Style;
use PDO;

class PDOSpectacleRepository implements SpectacleRepositoryInterface
{
    private const SELECT = <<<SQL
        SELECT sp.id, sp.titre, sp.description, sp.url_video,
               TO_CHAR(sp.horaire_previsionnel, 'HH24:MI') AS horaire,
               sp.id_soiree, TO_CHAR(so.date, 'YYYY-MM-DD') AS date,
               st.id AS id_style, st.libelle AS style
        FROM spectacle sp
        JOIN soiree so ON so.id = sp.id_soiree
        JOIN style st ON st.id = sp.id_style
        SQL;

    public function __construct(private PDO $pdo) {}

    public function findAll(array $filtres = []): array
    {
        $conditions = [];
        $params = [];

        if (isset($filtres['date'])) {
            $conditions[] = 'so.date = :date';
            $params['date'] = $filtres['date'];
        }
        if (isset($filtres['style'])) {
            $conditions[] = 'sp.id_style = :style';
            $params['style'] = (int)$filtres['style'];
        }
        if (isset($filtres['soiree'])) {
            $conditions[] = 'sp.id_soiree = :soiree';
            $params['soiree'] = (int)$filtres['soiree'];
        }

        $sql = self::SELECT
            . ($conditions ? ' WHERE ' . implode(' AND ', $conditions) : '')
            . ' ORDER BY so.date, sp.horaire_previsionnel, sp.id';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $nom => $valeur) {
            $stmt->bindValue(":$nom", $valeur, is_int($valeur) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $this->hydrater($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findById(int $id): ?Spectacle
    {
        $stmt = $this->pdo->prepare(self::SELECT . ' WHERE sp.id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $this->hydrater($stmt->fetchAll(PDO::FETCH_ASSOC))[0] ?? null;
    }

    public function findBySoiree(int $idSoiree): array
    {
        return $this->findAll(['soiree' => $idSoiree]);
    }

    /**
     * Construit les entités et charge artistes + images en 2 requêtes (évite le N+1).
     * @return Spectacle[]
     */
    private function hydrater(array $lignes): array
    {
        if (!$lignes) {
            return [];
        }

        $ids = array_map(fn($l) => (int)$l['id'], $lignes);
        $artistes = $this->chargerParSpectacle(
            'SELECT sa.id_spectacle, a.nom AS valeur FROM spectacle_artiste sa
             JOIN artiste a ON a.id = sa.id_artiste
             WHERE sa.id_spectacle IN (%s) ORDER BY a.id',
            $ids
        );
        $images = $this->chargerParSpectacle(
            'SELECT id_spectacle, url AS valeur FROM image WHERE id_spectacle IN (%s) ORDER BY id',
            $ids
        );

        return array_map(fn($l) => new Spectacle(
            (int)$l['id'],
            $l['titre'],
            $l['description'],
            $l['url_video'],
            $l['horaire'],
            (int)$l['id_soiree'],
            $l['date'],
            new Style((int)$l['id_style'], $l['style']),
            $artistes[(int)$l['id']] ?? [],
            $images[(int)$l['id']] ?? []
        ), $lignes);
    }

    /** @return array<int, string[]> */
    private function chargerParSpectacle(string $sql, array $ids): array
    {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(sprintf($sql, $placeholders));
        foreach ($ids as $i => $id) {
            $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();

        $resultat = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $resultat[(int)$ligne['id_spectacle']][] = $ligne['valeur'];
        }
        return $resultat;
    }
}
