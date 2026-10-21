<?php
namespace nrv\core\dto;

use nrv\core\domain\entities\Soiree;
use nrv\core\domain\entities\Spectacle;
use nrv\core\domain\entities\Tarif;

/** Détail d'une soirée avec ses spectacles (contrat GET /soirees/{id}) */
class SoireeDTO
{
    /** @param array<string, Tarif> $tarifs */
    public function __construct(private Soiree $soiree, private array $tarifs) {}

    public function toArray(): array
    {
        $s = $this->soiree;
        return [
            'id' => $s->id,
            'nom' => $s->nom,
            'thematique' => $s->thematique,
            'date' => $s->date,
            'horaire' => $s->heureDebut,
            'lieu' => ['id' => $s->lieu->id, 'nom' => $s->lieu->nom, 'adresse' => $s->lieu->adresse],
            'tarifs' => [
                'normal' => isset($this->tarifs[Tarif::NORMAL]) ? $this->tarifs[Tarif::NORMAL]->prix : null,
                'reduit' => isset($this->tarifs[Tarif::REDUIT]) ? $this->tarifs[Tarif::REDUIT]->prix : null,
            ],
            'placesRestantes' => $s->placesRestantes(),
            'spectacles' => array_map(fn(Spectacle $sp) => [
                'id' => $sp->id,
                'titre' => $sp->titre,
                'artistes' => $sp->artistes,
                'description' => $sp->description,
                'style' => $sp->style->libelle,
                'video' => $sp->urlVideo,
                'horaire' => $sp->horaire,
                'images' => $sp->images,
            ], $s->spectacles),
        ];
    }
}
