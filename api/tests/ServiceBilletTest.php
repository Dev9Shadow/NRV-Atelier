<?php
namespace nrv\tests;

use nrv\core\application\ports\BilletRepositoryInterface;
use nrv\core\application\ports\SoireeRepositoryInterface;
use nrv\core\application\usecases\ServiceBillet;
use nrv\core\domain\entities\Billet;
use nrv\core\domain\entities\Lieu;
use nrv\core\domain\entities\Soiree;
use nrv\core\domain\exceptions\EntityNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ServiceBilletTest extends TestCase
{
    private BilletRepositoryInterface&MockObject $billets;
    private ServiceBillet $service;

    protected function setUp(): void
    {
        $this->billets = $this->createMock(BilletRepositoryInterface::class);
        $soirees = $this->createMock(SoireeRepositoryInterface::class);
        $soirees->method('findById')->willReturnCallback(fn(int $id) =>
            new Soiree($id, "Soirée $id", 'Rock', sprintf('2027-07-%02d', $id), '20:30', 700, 100, new Lieu(2, 'Salle Poirel', '3 rue Victor Poirel', 700)));

        $this->service = new ServiceBillet($this->billets, $soirees);
    }

    public function testBilletSimpleComplet(): void
    {
        $token = str_repeat('a', 64);
        $this->billets->method('findByToken')->with($token)->willReturn(
            new Billet(42, 'NRV-2027-00042', $token, 'SIMPLE', 'REDUIT', 25.0, 3, [4], 'Bob Durand')
        );

        $this->assertSame([
            'reference' => 'NRV-2027-00042',
            'acheteur' => 'Bob Durand',
            'typeBillet' => 'SIMPLE',
            'categorie' => 'REDUIT',
            'prix' => 25.0,
            'soirees' => [['nom' => 'Soirée 4', 'date' => '2027-07-04', 'horaire' => '20:30', 'lieu' => 'Salle Poirel']],
        ], $this->service->getBillet($token));
    }

    public function testPassAvecSesTroisSoirees(): void
    {
        $token = str_repeat('b', 64);
        $this->billets->method('findByToken')->willReturn(
            new Billet(43, 'NRV-2027-00043', $token, 'PASS_3J', 'NORMAL', 101.85, 3, [2, 6, 11], 'Bob Durand')
        );

        $billet = $this->service->getBillet($token);

        $this->assertSame(['Soirée 2', 'Soirée 6', 'Soirée 11'], array_column($billet['soirees'], 'nom'));
        $this->assertSame(101.85, $billet['prix']);
    }

    public function testTokenInconnu(): void
    {
        $this->billets->method('findByToken')->willReturn(null);

        $this->expectException(EntityNotFoundException::class);
        $this->service->getBillet(str_repeat('0', 64));
    }
}
