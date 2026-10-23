<?php

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
    return $valeur === null ? 'NULL' : "'" . str_replace("'", "''", $valeur) . "'";
}

function inserer(array &$sql, string $table, array $colonnes, array $lignes): void
{
    foreach (array_chunk($lignes, 500) as $paquet) {
        $sql[] = "INSERT INTO $table (" . implode(', ', $colonnes) . ") VALUES\n  "
            . implode(",\n  ", array_map(fn(array $l) => '(' . implode(', ', $l) . ')', $paquet)) . ';';
    }
}

$prix = [
    'SIMPLE' => ['NORMAL' => 35.0, 'REDUIT' => 25.0],
    'PASS_3J' => ['NORMAL' => round(3 * 35.0 * 0.97, 2), 'REDUIT' => round(3 * 25.0 * 0.97, 2)],
];

$lieux = [
    1 => ["L'Autre Canal", "45 boulevard d'Austrasie, 54000 Nancy", 1200],
    2 => ['Salle Poirel', '3 rue Victor Poirel, 54000 Nancy', 700],
    3 => ['La Manufacture', '10 rue Baron Louis, 54000 Nancy', 400],
    4 => ['Parc de la Pépinière', 'Place de la Carrière, 54000 Nancy', 3000],
    5 => ['Le Hublot', 'Rue de la Ravinelle, 54000 Nancy', 250],
];

$styles = [1 => 'Blues rock', 'Métal', 'Rock', 'Hard rock', 'Punk', 'Pop rock', 'Rock psyché', 'Grunge'];

$groupes = [
    'Iron Wolves', 'Black Mirage', 'The Rusty Pipes', 'Lune Noire', 'Cobalt Riders', 'Electric Moose',
    'Velvet Thunder', 'Les Corbeaux de Lorraine', 'Mudslide Brothers', 'Neon Cathedral', 'Satellite Dogs',
    'Red Canyon', 'Molotov Garden', 'Les Ombres Électriques', 'Steel Orchid', 'Dust Prophets', 'Gravity Sons',
    'Pale Horizon', 'Kinky Lanterns', 'Les Fils du Granit', 'Wild Saturn', 'Hollow Kings', 'Feedback Sisters',
    'Burning Vinyl', 'Magma Club', 'Crimson Tape', 'Sonic Bisons', 'Les Vagabonds du Plateau',
    'Rattlesnake Choir', 'Ghost Highway', 'Ultra Violette', 'Brick Lane Riot', 'Les Mirabelles Hurlantes',
    'Thunder Moth', 'Ash & Ivory', 'Deep Blue Lizard', 'Static Monks', 'Polaroid Youth', 'Bad Weather Club',
    'Copper Saints',
];

$villes = ['Nancy', 'Metz', 'Strasbourg', 'Lyon', 'Lille', 'Bruxelles', 'Montréal', 'Manchester',
    'Seattle', 'Berlin', 'Austin', 'Glasgow', 'Bordeaux', 'Liège'];

$adjectifs = [
    'Blues rock' => ['poisseux et habité', 'chaud comme un bar du Mississippi', 'aux guitares slide brûlantes'],
    'Métal' => ['lourd et sans concession', 'aux riffs massifs', 'taillé pour faire trembler les murs'],
    'Rock' => ['direct et efficace', 'aux refrains imparables', 'nerveux et généreux'],
    'Hard rock' => ['survolté', 'aux solos interminables', 'hérité des grandes heures des années 80'],
    'Punk' => ['rapide et rageur', 'brut de décoffrage', 'aux textes acérés'],
    'Pop rock' => ['lumineux et entraînant', 'aux mélodies accrocheuses', 'aussi dansant que mélancolique'],
    'Rock psyché' => ['hypnotique', 'aux longues envolées planantes', 'nourri de synthés vintage'],
    'Grunge' => ['sale et mélancolique', 'aux guitares saturées', 'chargé de colère contenue'],
];

$nomsSoirees = [
    'Blues rock' => ['Blues de minuit', 'Crossroads Night', 'La Nuit du Bayou', 'Blue Moon Session'],
    'Métal' => ['Nuit Métal', 'Heavy Stanislas', 'Les Forges de Lorraine', 'Décibels & Damnation'],
    'Rock' => ["Rock'n'Stan", 'Rock à la Pépinière', 'Grand Soir Rock', 'Rock au Canal'],
    'Hard rock' => ['Hard Rock Fever', 'Électrochoc', 'Highway Night', 'Amplis à 11'],
    'Punk' => ['Pogo Party', 'No Future Night', 'Punk à Charles III', 'Trois Accords'],
    'Pop rock' => ['Pop Rock Sunset', 'Les Mélodies du Canal', 'Summer Jam', 'Golden Hour'],
    'Rock psyché' => ['Voyage Psyché', 'Kaléidoscope', 'Nuit Cosmique', 'Lumières Liquides'],
    'Grunge' => ['Grunge Revival', 'Flannel Night', 'Distorsion', 'Garage Hurlant'],
];

$debutFestival = new DateTimeImmutable('2027-07-01');
$joursFestival = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 13];
$nbSpectaclesParSoiree = [3, 2, 2, 3, 2, 2, 3, 2, 2, 3, 2, 2, 3, 2, 2];
$heures = ['19:00', '19:30', '20:00', '20:30'];

$soirees = [];
foreach ($joursFestival as $i => $decalage) {
    $id = $i + 1;
    $idLieu = ($i % count($lieux)) + 1;
    $idStyle = $faker->numberBetween(1, count($styles));
    $date = $debutFestival->modify("+$decalage day")->format('Y-m-d');

    if ($id === 15) {
        $soirees[$id] = ['nom' => 'Jam de clôture', 'thematique' => 'Rock', 'style' => 3, 'lieu' => 5,
            'date' => $date, 'heure' => '22:30', 'total' => 40];
        continue;
    }

    $style = $styles[$idStyle];
    $soirees[$id] = [
        'nom' => array_shift($nomsSoirees[$style]), 'thematique' => $style, 'style' => $idStyle, 'lieu' => $idLieu,
        'date' => $date, 'heure' => $faker->randomElement($heures), 'total' => $lieux[$idLieu][2],
    ];
}

$spectacles = [];
$artistes = [];
$spectacleArtistes = [];
$images = [];
$groupesMelanges = $faker->shuffle($groupes);
$idSpectacle = 0;

foreach ($soirees as $idSoiree => $soiree) {
    $horaire = DateTimeImmutable::createFromFormat('H:i', $soiree['heure']);
    for ($n = 0; $n < $nbSpectaclesParSoiree[$idSoiree - 1]; $n++) {
        $idSpectacle++;
        $groupe = array_shift($groupesMelanges);
        $idStyle = $faker->boolean(75) ? $soiree['style'] : $faker->numberBetween(1, count($styles));
        $style = $styles[$idStyle];

        $description = sprintf(
            '%s, %s propose un %s %s. %s',
            $faker->randomElement([
                'Formé à ' . $faker->randomElement($villes) . ' en ' . $faker->numberBetween(2008, 2023),
                'Tout droit venu de ' . $faker->randomElement($villes),
                'Après ' . $faker->numberBetween(2, 6) . ' albums et des centaines de concerts',
                'Révélation de la scène ' . mb_strtolower($style) . ' de ces dernières années',
            ]),
            $groupe,
            mb_strtolower($style),
            $faker->randomElement($adjectifs[$style]),
            $faker->randomElement([
                'Un live à ne pas manquer.',
                'Leur nouvel album sort au printemps 2027 : la tournée passe par Nancy.',
                'Attendez-vous à un show explosif.',
                'Sur scène, le groupe ne laisse personne immobile.',
                'Premier passage au festival, et sûrement pas le dernier.',
            ])
        );

        $spectacles[] = [
            $idSpectacle, q($groupe), q($description),
            q('https://www.youtube.com/watch?v=' . $faker->regexify('[A-Za-z0-9_-]{11}')),
            q($horaire->modify('+' . (30 + 75 * $n) . ' minutes')->format('H:i')),
            $idSoiree, $idStyle,
        ];

        for ($a = 0; $a < $faker->numberBetween(2, 4); $a++) {
            $artistes[] = [count($artistes) + 1, q($faker->firstName() . ' ' . $faker->lastName())];
            $spectacleArtistes[] = [$idSpectacle, count($artistes)];
        }

        for ($img = 1; $img <= $faker->numberBetween(1, 2); $img++) {
            $images[] = [count($images) + 1, q("https://picsum.photos/seed/nrv-$idSpectacle-$img/800/500"), $idSpectacle];
        }
    }
}

$utilisateurs = [
    1 => ['admin@nrv.fr', 'Admin2027!', 'NRV', 'Admin', 'ADMIN'],
    2 => ['alice@nrv.fr', 'Alice2027!', 'Martin', 'Alice', 'USER'],
    3 => ['bob@nrv.fr', 'Bob2027!', 'Durand', 'Bob', 'USER'],
    4 => ['chloe@nrv.fr', 'Chloe2027!', 'Lambert', 'Chloé', 'USER'],
];
for ($id = 5; $id <= 60; $id++) {
    $prenom = $faker->firstName();
    $nom = $faker->lastName();
    $email = preg_replace('/[^a-z0-9.]/', '', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', "$prenom.$nom"))) . "$id@example.com";
    $utilisateurs[$id] = [$email, 'Festival2027!', $nom, $prenom, 'USER'];
}
$hashes = [];
foreach ($utilisateurs as [, $motDePasse]) {
    $hashes[$motDePasse] ??= password_hash($motDePasse, PASSWORD_DEFAULT);
}

$commandes = [];
$lignes = [];
$ligneSoirees = [];
$billets = [];
$billetSoirees = [];
$vendues = array_fill_keys(array_keys($soirees), 0);

$ajouterCommande = function (int $idUtilisateur, string $statut, array $lignesCommande) use (
    &$commandes, &$lignes, &$ligneSoirees, &$billets, &$billetSoirees, &$vendues, $prix, $faker
): int {
    $idCommande = count($commandes) + 1;
    $date = $faker->dateTimeBetween('2026-09-01', '2026-10-20')->format('Y-m-d H:i:s');
    $commandes[] = [$idCommande, q($statut), q($date), $idUtilisateur];

    foreach ($lignesCommande as [$type, $categorie, $idSoirees, $quantite]) {
        $prixUnitaire = number_format($prix[$type][$categorie], 2, '.', '');
        $idLigne = count($lignes) + 1;
        $lignes[] = [$idLigne, q($type), q($categorie), $quantite, $prixUnitaire, $idCommande];
        foreach ($idSoirees as $idSoiree) {
            $ligneSoirees[] = [$idLigne, $idSoiree];
        }

        if ($statut !== 'PAYEE') {
            continue;
        }
        for ($i = 0; $i < $quantite; $i++) {
            $idBillet = count($billets) + 1;
            $billets[] = [$idBillet, q(sprintf('NRV-2027-%05d', $idBillet)), q($faker->unique()->sha256()),
                q($type), q($categorie), $prixUnitaire, $idCommande];
            foreach ($idSoirees as $idSoiree) {
                $billetSoirees[] = [$idBillet, $idSoiree];
                $vendues[$idSoiree]++;
            }
        }
    }
    return $idCommande;
};

$ajouterCommande(4, 'PAYEE', [['SIMPLE', 'NORMAL', [1], 2], ['PASS_3J', 'REDUIT', [3, 7, 12], 1]]);
$ajouterCommande(4, 'PAYEE', [['SIMPLE', 'REDUIT', [5], 1]]);
$commandeBob = $ajouterCommande(3, 'VALIDEE', [['SIMPLE', 'NORMAL', [4], 2], ['PASS_3J', 'REDUIT', [2, 6, 11], 1]]);
$commandeRefusee = $ajouterCommande(5, 'VALIDEE', [['SIMPLE', 'NORMAL', [15], 2]]);

$objectif = [];
foreach ($soirees as $id => $s) {
    $objectif[$id] = $id === 15 ? 39 : (int)round($s['total'] * $faker->numberBetween(10, 65) / 100);
}
$restant = function (int $id) use (&$objectif, &$vendues): int {
    return $objectif[$id] - $vendues[$id];
};

while ($ouvertes = array_keys(array_filter($objectif, fn($o, $id) => $vendues[$id] < $o, ARRAY_FILTER_USE_BOTH))) {
    $acheteur = $faker->numberBetween(5, 60);
    $categorie = $faker->boolean(65) ? 'NORMAL' : 'REDUIT';

    if (count($ouvertes) >= 3 && $faker->boolean(15)) {
        $troisSoirees = $faker->randomElements($ouvertes, 3);
        sort($troisSoirees);
        $quantite = min($faker->numberBetween(1, 2), ...array_map($restant, $troisSoirees));
        $ajouterCommande($acheteur, 'PAYEE', [['PASS_3J', $categorie, $troisSoirees, $quantite]]);
    } else {
        $idSoiree = $faker->randomElement($ouvertes);
        $quantite = min($faker->numberBetween(1, 6), $restant($idSoiree));
        $ajouterCommande($acheteur, 'PAYEE', [['SIMPLE', $categorie, [$idSoiree], $quantite]]);
    }
}

foreach ($soirees as $id => $s) {
    if ($vendues[$id] > $s['total']) {
        fwrite(STDERR, "Soirée $id : {$vendues[$id]} places vendues pour {$s['total']}\n");
        exit(1);
    }
}

$sql = ['-- Fichier généré par sql/seed.php, ne pas modifier à la main.', 'BEGIN;'];

inserer($sql, 'tarif', ['categorie', 'prix'], [[q('NORMAL'), '35.00'], [q('REDUIT'), '25.00']]);
inserer($sql, 'lieu', ['id', 'nom', 'adresse', 'capacite'],
    array_map(fn($id, $l) => [$id, q($l[0]), q($l[1]), $l[2]], array_keys($lieux), $lieux));
inserer($sql, 'style', ['id', 'libelle'],
    array_map(fn($id, $s) => [$id, q($s)], array_keys($styles), $styles));
inserer($sql, 'soiree',
    ['id', 'nom', 'thematique', 'date', 'heure_debut', 'nb_places_total', 'nb_places_vendues', 'id_lieu'],
    array_map(fn($id, $s) => [$id, q($s['nom']), q($s['thematique']), q($s['date']), q($s['heure']),
        $s['total'], $vendues[$id], $s['lieu']], array_keys($soirees), $soirees));
inserer($sql, 'spectacle',
    ['id', 'titre', 'description', 'url_video', 'horaire_previsionnel', 'id_soiree', 'id_style'], $spectacles);
inserer($sql, 'artiste', ['id', 'nom'], $artistes);
inserer($sql, 'spectacle_artiste', ['id_spectacle', 'id_artiste'], $spectacleArtistes);
inserer($sql, 'image', ['id', 'url', 'id_spectacle'], $images);
inserer($sql, 'utilisateur', ['id', 'email', 'password_hash', 'nom', 'prenom', 'role'],
    array_map(fn($id, $u) => [$id, q($u[0]), q($hashes[$u[1]]), q($u[2]), q($u[3]), q($u[4])],
        array_keys($utilisateurs), $utilisateurs));
inserer($sql, 'commande', ['id', 'statut', 'date_creation', 'id_utilisateur'], $commandes);
inserer($sql, 'ligne_commande', ['id', 'type_billet', 'categorie', 'quantite', 'prix_unitaire', 'id_commande'], $lignes);
inserer($sql, 'ligne_commande_soiree', ['id_ligne_commande', 'id_soiree'], $ligneSoirees);
inserer($sql, 'billet', ['id', 'reference', 'token', 'type_billet', 'categorie', 'prix', 'id_commande'], $billets);
inserer($sql, 'billet_soiree', ['id_billet', 'id_soiree'], $billetSoirees);

foreach (['lieu', 'style', 'soiree', 'spectacle', 'artiste', 'image', 'utilisateur', 'commande', 'ligne_commande', 'billet'] as $table) {
    $sql[] = "SELECT setval(pg_get_serial_sequence('$table', 'id'), (SELECT MAX(id) FROM $table));";
}
$sql[] = 'COMMIT;';

$fichier = __DIR__ . '/nrv.data.sql';
file_put_contents($fichier, implode("\n", $sql) . "\n");

printf("Jeu de données écrit dans %s\n", $fichier);
printf("%d soirées, %d spectacles, %d utilisateurs, %d commandes, %d billets\n",
    count($soirees), count($spectacles), count($utilisateurs), count($commandes), count($billets));
printf("Commande VALIDEE de bob : n°%d ; commande refusée faute de places : n°%d\n", $commandeBob, $commandeRefusee);
