<?php
namespace nrv\core\application\usecases;

use nrv\core\application\ports\BilletRepositoryInterface;
use nrv\core\application\ports\SoireeRepositoryInterface;
use nrv\core\domain\exceptions\EntityNotFoundException;

class ServiceBillet
{
    public function __construct(
        private BilletRepositoryInterface $billetRepository,
        private SoireeRepositoryInterface $soireeRepository
    ) {}

    public function getBillet(string $token): array
    {
        $billet = $this->billetRepository->findByToken($token);
        if ($billet === null) {
            throw new EntityNotFoundException('Billet introuvable');
        }

        $soirees = [];
        foreach ($billet->idSoirees as $idSoiree) {
            $soiree = $this->soireeRepository->findById($idSoiree);
            if ($soiree !== null) {
                $soirees[] = [
                    'nom' => $soiree->nom,
                    'date' => $soiree->date,
                    'horaire' => $soiree->heureDebut,
                    'lieu' => $soiree->lieu->nom,
                ];
            }
        }

        return [
            'reference' => $billet->reference,
            'acheteur' => $billet->nomAcheteur,
            'typeBillet' => $billet->typeBillet,
            'categorie' => $billet->categorie,
            'prix' => $billet->prix,
            'soirees' => $soirees,
        ];
    }
}
