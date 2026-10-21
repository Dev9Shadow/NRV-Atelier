<?php
namespace nrv\infrastructure\repositories;

use nrv\core\application\ports\TarifRepositoryInterface;
use nrv\core\domain\entities\Tarif;
use PDO;

class PDOTarifRepository implements TarifRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function findAll(): array
    {
        $stmt = $this->pdo->prepare('SELECT categorie, prix FROM tarif');
        $stmt->execute();

        $tarifs = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $tarifs[$ligne['categorie']] = new Tarif($ligne['categorie'], (float)$ligne['prix']);
        }
        return $tarifs;
    }
}
