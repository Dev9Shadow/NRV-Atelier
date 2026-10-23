<?php
namespace nrv\infrastructure\repositories;

use nrv\core\application\ports\CommandeRepositoryInterface;
use nrv\core\domain\entities\Commande;
use nrv\core\domain\entities\LigneCommande;
use nrv\core\domain\exceptions\EntityNotFoundException;
use nrv\core\domain\exceptions\PlacesInsuffisantesException;
use PDO;

class PDOCommandeRepository implements CommandeRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function findPanierByUtilisateur(int $idUtilisateur): ?Commande
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, id_utilisateur, statut, date_creation FROM commande
             WHERE id_utilisateur = :utilisateur AND statut = :statut
             ORDER BY date_creation DESC, id DESC LIMIT 1'
        );
        $stmt->bindValue(':utilisateur', $idUtilisateur, PDO::PARAM_INT);
        $stmt->bindValue(':statut', Commande::PANIER);
        $stmt->execute();

        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);
        return $ligne === false ? null : $this->hydrater($ligne);
    }

    public function findById(int $idCommande): ?Commande
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, id_utilisateur, statut, date_creation FROM commande WHERE id = :id'
        );
        $stmt->bindValue(':id', $idCommande, PDO::PARAM_INT);
        $stmt->execute();

        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);
        return $ligne === false ? null : $this->hydrater($ligne);
    }

    public function creerPanier(int $idUtilisateur): Commande
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO commande (statut, id_utilisateur) VALUES (:statut, :utilisateur)
             RETURNING id, id_utilisateur, statut, date_creation'
        );
        $stmt->bindValue(':statut', Commande::PANIER);
        $stmt->bindValue(':utilisateur', $idUtilisateur, PDO::PARAM_INT);
        $stmt->execute();

        return $this->hydrater($stmt->fetch(PDO::FETCH_ASSOC));
    }

    public function ajouterLigne(int $idCommande, LigneCommande $ligne): int
    {
        return $this->dansUneTransaction(function () use ($idCommande, $ligne) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO ligne_commande (type_billet, categorie, quantite, prix_unitaire, id_commande)
                 VALUES (:type, :categorie, :quantite, :prix, :commande) RETURNING id'
            );
            $stmt->bindValue(':type', $ligne->typeBillet);
            $stmt->bindValue(':categorie', $ligne->categorie);
            $stmt->bindValue(':quantite', $ligne->quantite, PDO::PARAM_INT);
            $stmt->bindValue(':prix', number_format($ligne->prixUnitaire, 2, '.', ''));
            $stmt->bindValue(':commande', $idCommande, PDO::PARAM_INT);
            $stmt->execute();
            $idLigne = (int)$stmt->fetchColumn();

            $stmtSoiree = $this->pdo->prepare(
                'INSERT INTO ligne_commande_soiree (id_ligne_commande, id_soiree) VALUES (:ligne, :soiree)'
            );
            foreach ($ligne->idSoirees as $idSoiree) {
                $stmtSoiree->bindValue(':ligne', $idLigne, PDO::PARAM_INT);
                $stmtSoiree->bindValue(':soiree', $idSoiree, PDO::PARAM_INT);
                $stmtSoiree->execute();
            }

            $ligne->id = $idLigne;
            return $idLigne;
        });
    }

    public function modifierQuantite(int $idLigne, int $quantite): void
    {
        $stmt = $this->pdo->prepare('UPDATE ligne_commande SET quantite = :quantite WHERE id = :id');
        $stmt->bindValue(':quantite', $quantite, PDO::PARAM_INT);
        $stmt->bindValue(':id', $idLigne, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            throw new EntityNotFoundException("Ligne $idLigne introuvable");
        }
    }

    public function supprimerLigne(int $idLigne): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM ligne_commande WHERE id = :id');
        $stmt->bindValue(':id', $idLigne, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            throw new EntityNotFoundException("Ligne $idLigne introuvable");
        }
    }

    public function changerStatut(int $idCommande, string $statut): void
    {
        $stmt = $this->pdo->prepare('UPDATE commande SET statut = :statut WHERE id = :id');
        $stmt->bindValue(':statut', $statut);
        $stmt->bindValue(':id', $idCommande, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            throw new EntityNotFoundException("Commande $idCommande introuvable");
        }
    }

    public function enregistrerPaiement(Commande $commande, array $billets): void
    {
        $this->dansUneTransaction(function () use ($commande, $billets) {
            $places = $commande->placesParSoiree();
            $this->verrouillerEtVerifierPlaces($places);

            $stmt = $this->pdo->prepare(
                'UPDATE soiree SET nb_places_vendues = nb_places_vendues + :nb WHERE id = :id'
            );
            foreach ($places as $idSoiree => $nb) {
                $stmt->bindValue(':nb', $nb, PDO::PARAM_INT);
                $stmt->bindValue(':id', $idSoiree, PDO::PARAM_INT);
                $stmt->execute();
            }

            $this->insererBillets($billets);
            $this->changerStatut($commande->id, Commande::PAYEE);
        });
    }

    private function verrouillerEtVerifierPlaces(array $places): void
    {
        if ($places === []) {
            return;
        }

        $ids = array_keys($places);
        $stmt = $this->pdo->prepare(
            'SELECT id, nom, nb_places_total, nb_places_vendues FROM soiree
             WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')
             ORDER BY id FOR UPDATE'
        );
        foreach ($ids as $i => $id) {
            $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();

        $soirees = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $soiree) {
            $soirees[(int)$soiree['id']] = $soiree;
        }

        foreach ($places as $idSoiree => $nb) {
            if (!isset($soirees[$idSoiree])) {
                throw new EntityNotFoundException("Soirée $idSoiree introuvable");
            }
            $restantes = (int)$soirees[$idSoiree]['nb_places_total'] - (int)$soirees[$idSoiree]['nb_places_vendues'];
            if ($nb > $restantes) {
                throw new PlacesInsuffisantesException(
                    "Plus assez de places pour « {$soirees[$idSoiree]['nom']} » : $restantes restante(s), $nb demandée(s)"
                );
            }
        }
    }

    private function insererBillets(array $billets): void
    {
        $stmtId = $this->pdo->prepare("SELECT nextval(pg_get_serial_sequence('billet', 'id'))");
        $stmtBillet = $this->pdo->prepare(
            'INSERT INTO billet (id, reference, token, type_billet, categorie, prix, id_commande)
             VALUES (:id, :reference, :token, :type, :categorie, :prix, :commande)'
        );
        $stmtSoiree = $this->pdo->prepare(
            'INSERT INTO billet_soiree (id_billet, id_soiree) VALUES (:billet, :soiree)'
        );

        foreach ($billets as $billet) {
            $stmtId->execute();
            $id = (int)$stmtId->fetchColumn();
            $reference = $billet->reference ?? sprintf('NRV-2027-%05d', $id);

            $stmtBillet->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtBillet->bindValue(':reference', $reference);
            $stmtBillet->bindValue(':token', $billet->token);
            $stmtBillet->bindValue(':type', $billet->typeBillet);
            $stmtBillet->bindValue(':categorie', $billet->categorie);
            $stmtBillet->bindValue(':prix', number_format($billet->prix, 2, '.', ''));
            $stmtBillet->bindValue(':commande', $billet->idCommande, PDO::PARAM_INT);
            $stmtBillet->execute();

            foreach ($billet->idSoirees as $idSoiree) {
                $stmtSoiree->bindValue(':billet', $id, PDO::PARAM_INT);
                $stmtSoiree->bindValue(':soiree', $idSoiree, PDO::PARAM_INT);
                $stmtSoiree->execute();
            }

            $billet->id = $id;
            $billet->reference = $reference;
        }
    }

    private function dansUneTransaction(callable $operation): mixed
    {
        if ($this->pdo->inTransaction()) {
            return $operation();
        }

        $this->pdo->beginTransaction();
        try {
            $resultat = $operation();
            $this->pdo->commit();
            return $resultat;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function hydrater(array $ligne): Commande
    {
        return new Commande(
            (int)$ligne['id'],
            (int)$ligne['id_utilisateur'],
            $ligne['statut'],
            $this->chargerLignes((int)$ligne['id']),
            new \DateTimeImmutable($ligne['date_creation'])
        );
    }

    private function chargerLignes(int $idCommande): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT lc.id, lc.type_billet, lc.categorie, lc.quantite, lc.prix_unitaire,
                    STRING_AGG(lcs.id_soiree::text, ',' ORDER BY lcs.id_soiree) AS soirees
             FROM ligne_commande lc
             JOIN ligne_commande_soiree lcs ON lcs.id_ligne_commande = lc.id
             WHERE lc.id_commande = :commande
             GROUP BY lc.id
             ORDER BY lc.id"
        );
        $stmt->bindValue(':commande', $idCommande, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(fn(array $l) => new LigneCommande(
            (int)$l['id'],
            $l['type_billet'],
            $l['categorie'],
            (int)$l['quantite'],
            (float)$l['prix_unitaire'],
            array_map('intval', explode(',', $l['soirees']))
        ), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
