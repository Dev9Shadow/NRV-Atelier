<?php
/**
 * Génère le jeu de données du festival dans sql/nrv.data.sql (chargé au démarrage du conteneur db).
 *
 * Usage (depuis la racine du repo) :
 *   docker compose exec api php /var/www/sql/seed.php
 * ou en local :
 *   php sql/seed.php
 *
 * Le générateur est initialisé avec une graine fixe : le jeu de données est le même à chaque exécution.
 */

// En local : api/vendor ; dans le conteneur api : /var/www/html/vendor
foreach ([__DIR__ . '/../api/vendor/autoload.php', '/var/www/html/vendor/autoload.php'] as $autoload) {
    if (file_exists($autoload)) {
        require $autoload;
        break;
    }
}
if (!class_exists(Faker\Factory::class)) {
    fwrite(STDERR, "Faker introuvable : lancer d'abord composer install dans api/\n");
    exit(1);
}

$faker = Faker\Factory::create('fr_FR');
$faker->seed(2027);

function q(?string $valeur): string
{
    if ($valeur === null) {
        return 'NULL';
    }
    return "'" . str_replace("'", "''", $valeur) . "'";
}

$sql = [];
$sql[] = "-- Fichier généré par sql/seed.php, ne pas modifier à la main.";
$sql[] = "BEGIN;";

// ---- Tarifs (globaux) ----
$sql[] = "INSERT INTO tarif (categorie, prix) VALUES ('NORMAL', 35.00), ('REDUIT', 25.00);";

// ---- Lieux ----
$lieux = [
    ["L'Autre Canal", "45 boulevard d'Austrasie, 54000 Nancy", 1200],
    ["Salle Poirel", "3 rue Victor Poirel, 54000 Nancy", 700],
    ["La Manufacture", "10 rue Baron Louis, 54000 Nancy", 400],
    ["Parc de la Pépinière", "Place de la Carrière, 54000 Nancy", 3000],
    ["Le Hublot", "Rue de la Ravinelle, 54000 Nancy", 250],
];
foreach ($lieux as $i => [$nom, $adresse, $capacite]) {
    $sql[] = sprintf("INSERT INTO lieu (id, nom, adresse, capacite) VALUES (%d, %s, %s, %d);",
        $i + 1, q($nom), q($adresse), $capacite);
}

// ---- Styles ----
$styles = ['Blues rock', 'Métal', 'Rock', 'Hard rock', 'Punk', 'Pop rock', 'Rock psyché', 'Grunge'];
foreach ($styles as $i => $libelle) {
    $sql[] = sprintf("INSERT INTO style (id, libelle) VALUES (%d, %s);", $i + 1, q($libelle));
}

// ---- Soirées : 15 soirées sur 2 semaines ----
// 5 soirées à 3 spectacles + 10 soirées à 2 spectacles = 35 spectacles
$debutFestival = new DateTimeImmutable('2027-07-01');
$joursFestival = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 13]; // 2 soirées le dernier jour
$heures = ['19:00', '19:30', '20:00', '20:30'];
$nbSpectaclesParSoiree = [3, 2, 2, 3, 2, 2, 3, 2, 2, 3, 2, 2, 3, 2, 2];

$soirees = [];
foreach ($joursFestival as $i => $decalage) {
    $id = $i + 1;
    $idStyle = $faker->numberBetween(1, count($styles));
    $idLieu = ($i % count($lieux)) + 1;
    $date = $debutFestival->modify("+$decalage day")->format('Y-m-d');
    $heure = $i === 14 ? '22:30' : $faker->randomElement($heures);
    $capacite = $lieux[$idLieu - 1][2];
    $vendues = $faker->numberBetween(0, (int)($capacite * 0.8));

    $soirees[$id] = ['style' => $idStyle, 'heure' => $heure];
    $sql[] = sprintf(
        "INSERT INTO soiree (id, nom, thematique, date, heure_debut, nb_places_total, nb_places_vendues, id_lieu) "
        . "VALUES (%d, %s, %s, %s, %s, %d, %d, %d);",
        $id,
        q('Nuit ' . $styles[$idStyle - 1] . ' #' . $id),
        q($styles[$idStyle - 1]),
        q($date),
        q($heure),
        $capacite,
        $vendues,
        $idLieu
    );
}

// ---- Spectacles, artistes, images ----
$idSpectacle = 0;
$idArtiste = 0;
$idImage = 0;
foreach ($soirees as $idSoiree => $soiree) {
    $horaire = DateTimeImmutable::createFromFormat('H:i', $soiree['heure']);
    for ($n = 0; $n < $nbSpectaclesParSoiree[$idSoiree - 1]; $n++) {
        $idSpectacle++;
        // La plupart des spectacles suivent la thématique de la soirée, quelques-uns non
        $idStyle = $faker->boolean(75) ? $soiree['style'] : $faker->numberBetween(1, count($styles));
        $titre = ucwords($faker->unique()->words($faker->numberBetween(1, 3), true));
        $horaireSpectacle = $horaire->modify('+' . (30 + 75 * $n) . ' minutes')->format('H:i');

        $sql[] = sprintf(
            "INSERT INTO spectacle (id, titre, description, url_video, horaire_previsionnel, id_soiree, id_style) "
            . "VALUES (%d, %s, %s, %s, %s, %d, %d);",
            $idSpectacle,
            q($titre),
            q($faker->paragraph(3)),
            q('https://www.youtube.com/watch?v=' . $faker->regexify('[A-Za-z0-9_-]{11}')),
            q($horaireSpectacle),
            $idSoiree,
            $idStyle
        );

        // 1 à 3 artistes par spectacle (le premier porte le nom du groupe)
        $artistes = [$titre];
        for ($a = 1; $a < $faker->numberBetween(1, 3); $a++) {
            $artistes[] = $faker->name();
        }
        foreach ($artistes as $nomArtiste) {
            $idArtiste++;
            $sql[] = sprintf("INSERT INTO artiste (id, nom) VALUES (%d, %s);", $idArtiste, q($nomArtiste));
            $sql[] = sprintf("INSERT INTO spectacle_artiste (id_spectacle, id_artiste) VALUES (%d, %d);",
                $idSpectacle, $idArtiste);
        }

        // 1 ou 2 images par spectacle
        for ($img = 1; $img <= $faker->numberBetween(1, 2); $img++) {
            $idImage++;
            $sql[] = sprintf("INSERT INTO image (id, url, id_spectacle) VALUES (%d, %s, %d);",
                $idImage, q("https://picsum.photos/seed/nrv-$idSpectacle-$img/800/500"), $idSpectacle);
        }
    }
}

$utilisateurs = [
    [1, 'admin@nrv.fr', 'Admin2027!', 'NRV', 'Admin', 'ADMIN'],
    [2, 'alice@nrv.fr', 'Alice2027!', 'Martin', 'Alice', 'USER'],
    [3, 'bob@nrv.fr', 'Bob2027!', 'Durand', 'Bob', 'USER'],
    [4, 'chloe@nrv.fr', 'Chloe2027!', 'Lambert', 'Chloé', 'USER'],
];
foreach ($utilisateurs as [$id, $email, $motDePasse, $nom, $prenom, $role]) {
    $sql[] = sprintf(
        "INSERT INTO utilisateur (id, email, password_hash, nom, prenom, role) VALUES (%d, %s, %s, %s, %s, %s);",
        $id, q($email), q(password_hash($motDePasse, PASSWORD_DEFAULT)), q($nom), q($prenom), q($role)
    );
}

// Les SERIAL doivent repartir après les id insérés à la main
foreach (['lieu', 'style', 'soiree', 'spectacle', 'artiste', 'image', 'utilisateur'] as $table) {
    $sql[] = "SELECT setval(pg_get_serial_sequence('$table', 'id'), (SELECT MAX(id) FROM $table));";
}

$sql[] = "COMMIT;";

$fichier = __DIR__ . '/nrv.data.sql';
file_put_contents($fichier, implode("\n", $sql) . "\n");
echo "Jeu de données écrit dans $fichier ($idSpectacle spectacles, " . count($soirees) . " soirées)\n";
