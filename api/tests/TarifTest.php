<?php
namespace nrv\tests;

use nrv\core\domain\entities\Tarif;
use PHPUnit\Framework\TestCase;

class TarifTest extends TestCase
{
    public function testPrixBilletSimpleEgalAuTarif(): void
    {
        $this->assertSame(35.0, (new Tarif(Tarif::NORMAL, 35.0))->prixBilletSimple());
    }

    public function testPass3JoursTarifNormalAvecReductionDe3Pourcent(): void
    {
        // 3 x 35 = 105 ; 105 - 3 % = 101.85
        $this->assertSame(101.85, (new Tarif(Tarif::NORMAL, 35.0))->prixPass3Jours());
    }

    public function testPass3JoursTarifReduit(): void
    {
        // 3 x 25 = 75 ; 75 - 3 % = 72.75
        $this->assertSame(72.75, (new Tarif(Tarif::REDUIT, 25.0))->prixPass3Jours());
    }

    public function testPass3JoursArrondiAuCentime(): void
    {
        // 3 x 33.33 = 99.99 ; x 0.97 = 96.9903 -> 96.99
        $this->assertSame(96.99, (new Tarif(Tarif::NORMAL, 33.33))->prixPass3Jours());
    }

    public function testPass3JoursMoinsCherQue3BilletsSimples(): void
    {
        $tarif = new Tarif(Tarif::NORMAL, 35.0);
        $this->assertLessThan(3 * $tarif->prixBilletSimple(), $tarif->prixPass3Jours());
    }

    public function testCategorieInconnueRefusee(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Tarif('ETUDIANT', 20.0);
    }

    public function testPrixNegatifRefuse(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Tarif(Tarif::NORMAL, -1.0);
    }
}
