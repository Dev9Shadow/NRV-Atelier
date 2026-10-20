<?php
namespace nrv\infrastructure\repositories;

use nrv\core\application\ports\StyleRepositoryInterface;
use nrv\core\domain\entities\Style;
use PDO;

class PDOStyleRepository implements StyleRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function findAll(): array
    {
        $stmt = $this->pdo->prepare('SELECT id, libelle FROM style ORDER BY libelle');
        $stmt->execute();

        return array_map(
            fn($l) => new Style((int)$l['id'], $l['libelle']),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function findById(int $id): ?Style
    {
        $stmt = $this->pdo->prepare('SELECT id, libelle FROM style WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);
        return $ligne === false ? null : new Style((int)$ligne['id'], $ligne['libelle']);
    }
}
