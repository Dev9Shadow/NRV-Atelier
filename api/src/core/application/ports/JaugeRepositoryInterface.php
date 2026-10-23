<?php

namespace nrv\core\application\ports;

interface JaugeRepositoryInterface
{
    public function getJaugesSoirees(): array;
}