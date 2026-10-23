<?php

namespace nrv\core\application\usecases;

use nrv\core\application\ports\JaugeRepositoryInterface;

class ServiceBackoffice
{
    private JaugeRepositoryInterface $jaugeRepository;

    public function __construct(JaugeRepositoryInterface $jaugeRepository)
    {
        $this->jaugeRepository = $jaugeRepository;
    }

    public function getJaugesSoirees(): array
    {
        return $this->jaugeRepository->getJaugesSoirees();
    }
}