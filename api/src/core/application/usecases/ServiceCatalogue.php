<?php
namespace nrv\core\application\usecases;

class ServiceCatalogue {
    // Plus tard, on injectera les interfaces de Erdal ici via le constructeur

    public function listerSpectacles(array $filtres): array {
        // Mock de données pour débloquer le front
        return [
            [
                "id" => 1,
                "titre" => "Iron Wolves",
                "date" => "2027-07-03",
                "horaire" => "21:00",
                "image" => "/img/ironwolves.jpg",
                "style" => "Métal",
                "id_soiree" => 4
            ]
        ];
    }

    public function getSpectacle(int $id): array {
        return [
            "id" => $id, "titre" => "Iron Wolves", "date" => "2027-07-03",
            "horaire" => "21:00", "image" => "/img/ironwolves.jpg", "style" => "Métal", "id_soiree" => 4
        ];
    }

    public function getSoiree(int $id): array {
        return [
            "id" => $id, "nom" => "Nuit Métal", "thematique" => "Métal",
            "date" => "2027-07-03", "horaire" => "20:00",
            "lieu" => ["id" => 2, "nom" => "L'Autre Canal", "adresse" => "45 bd d'Austrasie, Nancy"],
            "tarifs" => ["normal" => 35.0, "reduit" => 25.0],
            "placesRestantes" => 120,
            "spectacles" => [
                [
                    "id" => 1, "titre" => "Iron Wolves", "artistes" => ["Iron Wolves"],
                    "description" => "…", "style" => "Métal", "video" => "https://…",
                    "horaire" => "21:00", "images" => ["/img/ironwolves.jpg"]
                ]
            ]
        ];
    }

    public function listerStyles(): array {
        return [
            ["id" => 1, "libelle" => "Blues rock"],
            ["id" => 2, "libelle" => "Métal"]
        ];
    }
}