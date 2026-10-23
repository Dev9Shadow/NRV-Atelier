<?php
namespace nrv\tests;

use nrv\core\application\ports\SoireeRepositoryInterface;
use nrv\core\application\ports\SpectacleRepositoryInterface;
use nrv\core\application\ports\StyleRepositoryInterface;
use nrv\core\application\ports\TarifRepositoryInterface;
use nrv\core\application\usecases\ServiceCatalogue;
use nrv\core\domain\entities\Lieu;
use nrv\core\domain\entities\Soiree;
use nrv\core\domain\entities\Spectacle;
use nrv\core\domain\entities\Style;
use nrv\core\domain\entities\Tarif;
use nrv\core\domain\exceptions\EntityNotFoundException;
use nrv\core\domain\exceptions\InvalidFilterException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ServiceCatalogueTest extends TestCase
{
    private SpectacleRepositoryInterface&MockObject $spectacles;
    private SoireeRepositoryInterface&MockObject $soirees;
    private StyleRepositoryInterface&MockObject $styles;
    private TarifRepositoryInterface&MockObject $tarifs;
    private ServiceCatalogue $service;

    protected function setUp(): void
    {
        $this->spectacles = $this->createMock(SpectacleRepositoryInterface::class);
        $this->soirees = $this->createMock(SoireeRepositoryInterface::class);
        $this->styles = $this->createMock(StyleRepositoryInterface::class);
        $this->tarifs = $this->createMock(TarifRepositoryInterface::class);

        $this->service = new ServiceCatalogue($this->spectacles, $this->soirees, $this->styles, $this->tarifs);
    }

    private function spectacle(int $id = 1, int $idSoiree = 4): Spectacle
    {
        return new Spectacle(
            $id, 'Iron Wolves', 'Description', 'https://video', '21:00', $idSoiree, '2027-07-03',
            new Style(2, 'Métal'), ['Iron Wolves'], ['/img/ironwolves.jpg', '/img/ironwolves-2.jpg']
        );
    }

    public function testListerSpectaclesSansFiltreRenvoieLeFormatDuContrat(): void
    {
        $this->spectacles->expects($this->once())->method('findAll')->with([])
            ->willReturn([$this->spectacle()]);

        $resultat = $this->service->listerSpectacles([]);

        $this->assertSame([[
            'id' => 1, 'titre' => 'Iron Wolves', 'date' => '2027-07-03', 'horaire' => '21:00',
            'image' => '/img/ironwolves.jpg', 'style' => 'Métal', 'id_soiree' => 4,
        ]], $resultat);
    }

    public function testListerSpectaclesTransmetLesFiltresConvertis(): void
    {
        $this->spectacles->expects($this->once())->method('findAll')
            ->with(['date' => '2027-07-03', 'style' => 2, 'soiree' => 4])
            ->willReturn([]);

        $this->assertSame([], $this->service->listerSpectacles(['date' => '2027-07-03', 'style' => '2', 'soiree' => '4']));
    }

    public function testFiltresVidesIgnores(): void
    {
        $this->spectacles->expects($this->once())->method('findAll')->with([])->willReturn([]);
        $this->service->listerSpectacles(['date' => '', 'style' => '']);
    }

    public function testDateMalFormeeRenvoie400(): void
    {
        $this->spectacles->expects($this->never())->method('findAll');

        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionCode(400);
        $this->service->listerSpectacles(['date' => '03/07/2027']);
    }

    public function testDateInexistanteRefusee(): void
    {
        $this->expectException(InvalidFilterException::class);
        $this->service->listerSpectacles(['date' => '2027-02-30']);
    }

    public function testStyleNonNumeriqueRefuse(): void
    {
        $this->expectException(InvalidFilterException::class);
        $this->service->listerSpectacles(['style' => '2 OR 1=1']);
    }

    public function testGetSpectacleInconnuRenvoie404(): void
    {
        $this->spectacles->method('findById')->willReturn(null);

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionCode(404);
        $this->service->getSpectacle(99);
    }

    public function testGetSoireeRenvoieLeDetailAvecTarifsEtPlacesRestantes(): void
    {
        $soiree = new Soiree(
            4, 'Nuit Métal', 'Métal', '2027-07-03', '20:00', 1200, 1080,
            new Lieu(2, "L'Autre Canal", "45 bd d'Austrasie, Nancy", 1200),
            [$this->spectacle()]
        );
        $this->soirees->expects($this->once())->method('findById')->with(4)->willReturn($soiree);
        $this->tarifs->method('findAll')->willReturn([
            Tarif::NORMAL => new Tarif(Tarif::NORMAL, 35.0),
            Tarif::REDUIT => new Tarif(Tarif::REDUIT, 25.0),
        ]);

        $resultat = $this->service->getSoiree(4);

        $this->assertSame('Nuit Métal', $resultat['nom']);
        $this->assertSame(['id' => 2, 'nom' => "L'Autre Canal", 'adresse' => "45 bd d'Austrasie, Nancy"], $resultat['lieu']);
        $this->assertSame(['normal' => 35.0, 'reduit' => 25.0], $resultat['tarifs']);
        $this->assertSame(120, $resultat['placesRestantes']);
        $this->assertCount(1, $resultat['spectacles']);
        $this->assertSame(['Iron Wolves'], $resultat['spectacles'][0]['artistes']);
        $this->assertSame('https://video', $resultat['spectacles'][0]['video']);
    }

    public function testGetSoireeInconnueRenvoie404(): void
    {
        $this->soirees->method('findById')->willReturn(null);

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Soirée 99 introuvable');
        $this->service->getSoiree(99);
    }

    public function testListerSoireesAvecPlacesRestantes(): void
    {
        $lieu = new Lieu(1, 'Le Hublot', 'Nancy', 250);
        $this->soirees->method('findAll')->willReturn([
            new Soiree(1, 'Nuit Métal', 'Métal', '2027-07-01', '20:30', 250, 100, $lieu),
            new Soiree(2, 'Blues de minuit', 'Blues', '2027-07-02', '19:00', 250, 250, $lieu),
        ]);

        $this->assertSame([
            ['id' => 1, 'nom' => 'Nuit Métal', 'date' => '2027-07-01', 'horaire' => '20:30', 'placesRestantes' => 150],
            ['id' => 2, 'nom' => 'Blues de minuit', 'date' => '2027-07-02', 'horaire' => '19:00', 'placesRestantes' => 0],
        ], $this->service->listerSoirees());
    }

    public function testListerStyles(): void
    {
        $this->styles->method('findAll')->willReturn([new Style(1, 'Blues rock'), new Style(2, 'Métal')]);

        $this->assertSame(
            [['id' => 1, 'libelle' => 'Blues rock'], ['id' => 2, 'libelle' => 'Métal']],
            $this->service->listerStyles()
        );
    }

    public function testPlacesRestantesJamaisNegatives(): void
    {
        $soiree = new Soiree(1, 'n', 't', '2027-07-01', '20:00', 100, 100, new Lieu(1, 'l', 'a', 100));
        $this->assertSame(0, $soiree->placesRestantes());
        $this->assertTrue($soiree->estComplete());
    }
}
