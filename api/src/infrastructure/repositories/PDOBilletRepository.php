<?php
namespace nrv\infrastructure\repositories;

use nrv\core\application\ports\BilletRepositoryInterface;
use nrv\core\domain\entities\Billet;
use PDO;

class PDOBilletRepository implements BilletRepositoryInterface
{
    private const SELECT = "
        SELECT b.id, b.reference, b.token, b.type_billet, b.categorie, b.prix, b.id_commande,
               u.prenom || ' ' || u.nom AS acheteur,
               STRING_AGG(bs.id_soiree::text, ',' ORDER BY bs.id_soiree) AS soirees
        FROM billet b
        JOIN commande c ON c.id = b.id_commande
        JOIN utilisateur u ON u.id = c.id_utilisateur
        JOIN billet_soiree bs ON bs.id_billet = b.id";

    public function __construct(private PDO $pdo) {}

    public function findByToken(string $token): ?Billet
    {
        $stmt = $this->pdo->prepare(self::SELECT . ' WHERE b.token = :token GROUP BY b.id, u.id');
        $stmt->bindValue(':token', $token);
        $stmt->execute();

        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);
        return $ligne === false ? null : $this->hydrater($ligne);
    }

    public function findByCommande(int $idCommande): array
    {
        $stmt = $this->pdo->prepare(self::SELECT . ' WHERE b.id_commande = :commande GROUP BY b.id, u.id ORDER BY b.id');
        $stmt->bindValue(':commande', $idCommande, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(fn(array $l) => $this->hydrater($l), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function hydrater(array $l): Billet
    {
        return new Billet(
            (int)$l['id'],
            $l['reference'],
            $l['token'],
            $l['type_billet'],
            $l['categorie'],
            (float)$l['prix'],
            (int)$l['id_commande'],
            array_map('intval', explode(',', $l['soirees'])),
            $l['acheteur']
        );
    }
}
