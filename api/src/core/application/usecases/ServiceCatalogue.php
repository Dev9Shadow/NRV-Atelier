<?php
namespace nrv\core\application\usecases;

use nrv\core\application\ports\SoireeRepositoryInterface;
use nrv\core\application\ports\SpectacleRepositoryInterface;
use nrv\core\application\ports\StyleRepositoryInterface;
use nrv\core\application\ports\TarifRepositoryInterface;
use nrv\core\domain\exceptions\EntityNotFoundException;
use nrv\core\domain\exceptions\InvalidFilterException;
use nrv\core\dto\SoireeDTO;
use nrv\core\dto\SpectacleDTO;

class ServiceCatalogue
{
    public function __construct(
        private SpectacleRepositoryInterface $spectacleRepository,
        private SoireeRepositoryInterface $soireeRepository,
        private StyleRepositoryInterface $styleRepository,
        private TarifRepositoryInterface $tarifRepository
    ) {}

    /**
     * @param array $filtres paramètres de requête bruts (date, style, soiree), tous optionnels
     * @throws InvalidFilterException si un filtre est mal formé
     */
    public function listerSpectacles(array $filtres): array
    {
        $spectacles = $this->spectacleRepository->findAll($this->validerFiltres($filtres));
        return array_map(fn($s) => SpectacleDTO::fromEntity($s)->toArray(), $spectacles);
    }

    public function getSpectacle(int $id): array
    {
        $spectacle = $this->spectacleRepository->findById($id);
        if ($spectacle === null) {
            throw new EntityNotFoundException("Spectacle $id introuvable");
        }
        return SpectacleDTO::fromEntity($spectacle)->toArray();
    }

    public function getSoiree(int $id): array
    {
        $soiree = $this->soireeRepository->findById($id);
        if ($soiree === null) {
            throw new EntityNotFoundException("Soirée $id introuvable");
        }
        return (new SoireeDTO($soiree, $this->tarifRepository->findAll()))->toArray();
    }

    public function listerSoirees(): array
    {
        return array_map(fn($s) => [
            'id' => $s->id,
            'nom' => $s->nom,
            'date' => $s->date,
            'horaire' => $s->heureDebut,
            'placesRestantes' => $s->placesRestantes(),
        ], $this->soireeRepository->findAll());
    }

    public function listerStyles(): array
    {
        return array_map(
            fn($s) => ['id' => $s->id, 'libelle' => $s->libelle],
            $this->styleRepository->findAll()
        );
    }

    private function validerFiltres(array $filtres): array
    {
        $valides = [];

        if (isset($filtres['date']) && $filtres['date'] !== '') {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', (string)$filtres['date']);
            if ($date === false || $date->format('Y-m-d') !== $filtres['date']) {
                throw new InvalidFilterException("Date invalide : attendu AAAA-MM-JJ");
            }
            $valides['date'] = $filtres['date'];
        }

        foreach (['style', 'soiree'] as $cle) {
            if (isset($filtres[$cle]) && $filtres[$cle] !== '') {
                if (!ctype_digit((string)$filtres[$cle])) {
                    throw new InvalidFilterException("Filtre $cle invalide : entier attendu");
                }
                $valides[$cle] = (int)$filtres[$cle];
            }
        }

        return $valides;
    }
}
