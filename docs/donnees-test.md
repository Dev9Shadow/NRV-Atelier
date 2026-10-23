# Données de test

Le jeu de données est généré par `sql/seed.php` (faker, graine fixe) et chargé au premier démarrage du conteneur `db`.

- 15 soirées du 1er au 14 juillet 2027 dans 5 lieux de Nancy, 35 spectacles, 8 styles
- 60 utilisateurs, environ 1 200 commandes payées, environ 3 900 billets
- Pour chaque soirée, `nb_places_vendues` est égal au nombre de billets en base : la jauge et la recette sont cohérentes

Pour repartir d'une base propre :

```bash
docker compose down -v && docker compose up -d
```

## Comptes

| Rôle | Email | Mot de passe | Pour la démo |
|---|---|---|---|
| Administrateur | `admin@nrv.fr` | `Admin2027!` | Jauge du back-office |
| Utilisatrice | `alice@nrv.fr` | `Alice2027!` | Panier vide : parcours complet réservation → panier → paiement |
| Utilisateur | `bob@nrv.fr` | `Bob2027!` | Commande n°3 validée, prête à être payée |
| Utilisatrice | `chloe@nrv.fr` | `Chloe2027!` | Commandes n°1 et n°2 payées, avec leurs billets |
| Utilisatrice | `elisabeth.blin5@example.com` | `Festival2027!` | Commande n°4 refusée faute de places |

Les autres acheteurs (`…@example.com`) ont tous le mot de passe `Festival2027!`.

## Commandes à montrer

| Commande | Compte | Statut | Contenu | Résultat attendu |
|---|---|---|---|---|
| n°3 | bob@nrv.fr | VALIDEE | 2 billets « Crossroads Night » tarif normal + 1 pass 3 jours réduit (« Pop Rock Sunset », « Hard Rock Fever », « Rock'n'Stan »), 142,75 € | `paiement.html?commande=3` : paiement accepté, 3 billets créés |
| n°4 | elisabeth.blin5@example.com | VALIDEE | 2 billets « Jam de clôture », 70,00 € | Paiement refusé (409) : il ne reste qu'1 place |
| n°1 | chloe@nrv.fr | PAYEE | 2 billets « Nuit Métal » + 1 pass 3 jours réduit | Billets consultables et imprimables |

La soirée n°15 « Jam de clôture » (Le Hublot, 14 juillet, 22h30) est à 39 places vendues sur 40.

## Billets

| Référence | Type | Acheteur | Page du billet |
|---|---|---|---|
| NRV-2027-00001 | Billet 1 soirée, tarif normal | Chloé Lambert | `billet.html?token=27f27ec27cfa6a3a564da81136d2ff65b632e218b89b3746856b6b79db62309e` |
| NRV-2027-00003 | Pass 3 jours, tarif réduit | Chloé Lambert | `billet.html?token=42736781d18678b596e23473a5f5aa94c3b058bac47da04b31374c2b80b82bb3` |
| NRV-2027-00004 | Billet 1 soirée, tarif réduit | Chloé Lambert | `billet.html?token=158a0b356847d2c5498a639e13e476c6ca24cb23def6b37ff558c033b41d0d8b` |

Les 3 billets de la commande n°1 sur une seule page, un par page à l'impression :

```
billet.html?token=27f27ec27cfa6a3a564da81136d2ff65b632e218b89b3746856b6b79db62309e&token=fec096dafb741675b69006302ca2258e0ad514bb2ed4bb1d80b7218489642e81&token=42736781d18678b596e23473a5f5aa94c3b058bac47da04b31374c2b80b82bb3
```

## Carte bancaire

Le paiement est simulé : n'importe quel numéro de 16 chiffres, une date `MM/AA` non expirée et un code de 3 chiffres.

| Numéro | Expiration | Code | Résultat |
|---|---|---|---|
| `4970 1012 3456 7890` | `12/28` | `123` | Accepté |
| `1234` | `12/28` | `123` | Refusé (400) : 16 chiffres attendus |
| `4970 1012 3456 7890` | `09/26` | `123` | Refusé (400) : carte expirée |

## Base de données

| | Valeur par défaut |
|---|---|
| Adminer | http://localhost:55002, système PostgreSQL |
| Serveur | `db` |
| Base | `nrv_db` |
| Utilisateur | `nrv_user` |
| Mot de passe | `nrv_password` |

Ces valeurs viennent du fichier `.env` lu par `docker-compose.yml`.
