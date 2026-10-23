# NRV.net

Billetterie du festival Nancy Rock Vibration : programme, panier, paiement simulé, billets imprimables et jauge d'administration.

- **API** : PHP 8.2, Slim 4, PHP-DI, architecture hexagonale, PostgreSQL, JWT
- **Front** : HTML, SCSS, JavaScript (`fetch`, Handlebars)

## Prérequis

- Docker et Docker Compose
- Node.js (uniquement pour recompiler le SCSS)

## Installation

```bash
git clone https://github.com/Dev9Shadow/NRV-Atelier.git
cd NRV-Atelier
cp .env.example .env
docker compose up -d
```

Au premier démarrage :

- le conteneur `api` installe les dépendances PHP (`composer install`) ;
- le conteneur `db` crée les tables (`sql/nrv.schema.sql`) et charge le jeu de données (`sql/nrv.data.sql`).

## Accès

| Service | URL |
|---|---|
| Application | http://localhost:55001 |
| API | http://localhost:55000 |
| Adminer (base de données) | http://localhost:55002 (serveur `db`, identifiants du fichier `.env`) |

## Comptes de test

| Rôle | Email | Mot de passe |
|---|---|---|
| Administrateur | `admin@nrv.fr` | `Admin2027!` |
| Utilisatrice (panier vide) | `alice@nrv.fr` | `Alice2027!` |
| Utilisateur (commande validée à payer) | `bob@nrv.fr` | `Bob2027!` |
| Utilisatrice (billets déjà achetés) | `chloe@nrv.fr` | `Chloe2027!` |

Pour le paiement simulé, utiliser par exemple la carte `4970101234567890`, expiration `12/28`, CVC `123`.

Détail du jeu de données et des scénarios de démo : [`docs/donnees-test.md`](docs/donnees-test.md).

## Base de données

Repartir d'une base propre :

```bash
docker compose down -v
docker compose up -d
```

Régénérer le jeu de données avec faker (réécrit `sql/nrv.data.sql`, puis recharger la base) :

```bash
docker compose exec api php /var/www/sql/seed.php
docker compose down -v
docker compose up -d
```

## Tests

```bash
docker compose exec api composer test
```

## SCSS

Le CSS compilé (`front/css/main.css`) est versionné. Après une modification dans `front/scss/` :

```bash
npm install
npm run sass
```

## Documentation

- Modèle du domaine : [`docs/modele-domaine.png`](docs/modele-domaine.png)
- Tableau de bord du projet : [`docs/tableau-de-bord.md`](docs/tableau-de-bord.md)
