<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use nrv\core\application\usecases\ServiceBackoffice;
use nrv\core\application\ports\JaugeRepositoryInterface;
use nrv\core\dto\JaugeSoireeDTO;

class ServiceBackofficeTest extends TestCase
{
    public function testGetJaugesSoirees()
    {
        $mockRepo = $this->createMock(JaugeRepositoryInterface::class);
        $mockRepo->method('getJaugesSoirees')->willReturn([
            new JaugeSoireeDTO([
                'id' => 1,
                'titre' => 'Soirée Test',
                'date' => '2027-07-01',
                'horaire' => '21:00',
                'capacite' => 100,
                'places_vendues' => 50,
                'recette' => 750.0
            ])
        ]);

        $service = new ServiceBackoffice($mockRepo);
        $jauges = $service->getJaugesSoirees();

        $this->assertCount(1, $jauges);
        $this->assertEquals(50.0, $jauges[0]->taux_remplissage);
        $this->assertEquals(750.0, $jauges[0]->recette);
    }
}