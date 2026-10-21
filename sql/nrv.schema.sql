-- NRV.net : schéma de la base (PostgreSQL)
-- V1 : seules lieu, style, soiree, spectacle, artiste, spectacle_artiste, image et tarif sont remplies.

DROP TABLE IF EXISTS billet_soiree, billet, ligne_commande_soiree, ligne_commande, commande, utilisateur,
    tarif, image, spectacle_artiste, artiste, spectacle, soiree, style, lieu CASCADE;

CREATE TABLE lieu (
    id        SERIAL PRIMARY KEY,
    nom       VARCHAR(100) NOT NULL,
    adresse   VARCHAR(255) NOT NULL,
    capacite  INTEGER      NOT NULL CHECK (capacite > 0)
);

CREATE TABLE style (
    id       SERIAL PRIMARY KEY,
    libelle  VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE soiree (
    id                SERIAL PRIMARY KEY,
    nom               VARCHAR(100) NOT NULL,
    thematique        VARCHAR(100) NOT NULL,
    date              DATE         NOT NULL,
    heure_debut       TIME         NOT NULL,
    nb_places_total   INTEGER      NOT NULL CHECK (nb_places_total > 0),
    nb_places_vendues INTEGER      NOT NULL DEFAULT 0 CHECK (nb_places_vendues >= 0),
    id_lieu           INTEGER      NOT NULL REFERENCES lieu(id),
    CHECK (nb_places_vendues <= nb_places_total)
);

CREATE TABLE spectacle (
    id                    SERIAL PRIMARY KEY,
    titre                 VARCHAR(150) NOT NULL,
    description           TEXT         NOT NULL,
    url_video             VARCHAR(255),
    horaire_previsionnel  TIME         NOT NULL,
    id_soiree             INTEGER      NOT NULL REFERENCES soiree(id) ON DELETE CASCADE,
    id_style              INTEGER      NOT NULL REFERENCES style(id)
);

CREATE TABLE artiste (
    id   SERIAL PRIMARY KEY,
    nom  VARCHAR(100) NOT NULL
);

CREATE TABLE spectacle_artiste (
    id_spectacle  INTEGER NOT NULL REFERENCES spectacle(id) ON DELETE CASCADE,
    id_artiste    INTEGER NOT NULL REFERENCES artiste(id) ON DELETE CASCADE,
    PRIMARY KEY (id_spectacle, id_artiste)
);

CREATE TABLE image (
    id            SERIAL PRIMARY KEY,
    url           VARCHAR(255) NOT NULL,
    id_spectacle  INTEGER      NOT NULL REFERENCES spectacle(id) ON DELETE CASCADE
);

-- Tarif global, identique pour toutes les soirées
CREATE TABLE tarif (
    categorie  VARCHAR(10)   PRIMARY KEY CHECK (categorie IN ('NORMAL', 'REDUIT')),
    prix       NUMERIC(6, 2) NOT NULL CHECK (prix >= 0)
);

-- ===== Hors V1 (panier, commande, billets) =====

CREATE TABLE utilisateur (
    id             SERIAL PRIMARY KEY,
    email          VARCHAR(255) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    nom            VARCHAR(100) NOT NULL,
    prenom         VARCHAR(100) NOT NULL,
    role           VARCHAR(10)  NOT NULL DEFAULT 'USER' CHECK (role IN ('USER', 'ADMIN'))
);

-- Panier = commande au statut PANIER
CREATE TABLE commande (
    id              SERIAL PRIMARY KEY,
    statut          VARCHAR(10) NOT NULL DEFAULT 'PANIER' CHECK (statut IN ('PANIER', 'VALIDEE', 'PAYEE')),
    date_creation   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_utilisateur  INTEGER     NOT NULL REFERENCES utilisateur(id)
);

CREATE TABLE ligne_commande (
    id             SERIAL PRIMARY KEY,
    type_billet    VARCHAR(10)   NOT NULL CHECK (type_billet IN ('SIMPLE', 'PASS_3J')),
    categorie      VARCHAR(10)   NOT NULL REFERENCES tarif(categorie),
    quantite       INTEGER       NOT NULL CHECK (quantite > 0),
    prix_unitaire  NUMERIC(6, 2) NOT NULL CHECK (prix_unitaire >= 0),
    id_commande    INTEGER       NOT NULL REFERENCES commande(id) ON DELETE CASCADE
);

-- 1 soirée pour un billet simple, 3 pour un pass
CREATE TABLE ligne_commande_soiree (
    id_ligne_commande  INTEGER NOT NULL REFERENCES ligne_commande(id) ON DELETE CASCADE,
    id_soiree          INTEGER NOT NULL REFERENCES soiree(id),
    PRIMARY KEY (id_ligne_commande, id_soiree)
);

-- Billets créés au paiement
CREATE TABLE billet (
    id           SERIAL PRIMARY KEY,
    reference    VARCHAR(20)   NOT NULL UNIQUE,
    token        VARCHAR(64)   NOT NULL UNIQUE,
    type_billet  VARCHAR(10)   NOT NULL CHECK (type_billet IN ('SIMPLE', 'PASS_3J')),
    categorie    VARCHAR(10)   NOT NULL REFERENCES tarif(categorie),
    prix         NUMERIC(6, 2) NOT NULL CHECK (prix >= 0),
    id_commande  INTEGER       NOT NULL REFERENCES commande(id)
);

CREATE TABLE billet_soiree (
    id_billet  INTEGER NOT NULL REFERENCES billet(id) ON DELETE CASCADE,
    id_soiree  INTEGER NOT NULL REFERENCES soiree(id),
    PRIMARY KEY (id_billet, id_soiree)
);

CREATE INDEX idx_soiree_date ON soiree(date);
CREATE INDEX idx_spectacle_soiree ON spectacle(id_soiree);
CREATE INDEX idx_spectacle_style ON spectacle(id_style);
