<?php
namespace nrv\tests;

use nrv\core\domain\entities\LigneCommande;
use nrv\core\domain\entities\Tarif;
use nrv\core\domain\exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

class LigneCommandeTest extends TestCase
{
    private Tarif $normal;
    private Tarif $reduit;

    protected function setUp(): void
    {
        $this->normal = new Tarif(Tarif::NORMAL, 35.0);
        $this->reduit = new Tarif(Tarif::REDUIT, 25.0);
    }

    public function testBilletSimpleAuPrixDuTarifNormal(): void
    {
        $ligne = LigneCommande::creer(LigneCommande::SIMPLE, $this->normal, [4], 2);

        $this->assertNull($ligne->id);
        $this->assertSame(LigneCommande::SIMPLE, $ligne->typeBillet);
        $this->assertSame(Tarif::NORMAL, $ligne->categorie);
        $this->assertSame(35.0, $ligne->prixUnitaire);
        $this->assertSame(70.0, $ligne->sousTotal());
        $this->assertSame([4], $ligne->idSoirees);
    }

    public function testBilletSimpleAuPrixDuTarifReduit(): void
    {
        $ligne = LigneCommande::creer(LigneCommande::SIMPLE, $this->reduit, [4], 3);

        $this->assertSame(25.0, $ligne->prixUnitaire);
        $this->assertSame(75.0, $ligne->sousTotal());
    }

    public function testPassNormalAvecRemiseDe3Pourcent(): void
    {
        $ligne = LigneCommande::creer(LigneCommande::PASS_3J, $this->normal, [1, 5, 9], 1);

        $this->assertSame(101.85, $ligne->prixUnitaire);
        $this->assertTrue($ligne->estUnPass());
    }

    public function testPassReduitAvecRemiseDe3Pourcent(): void
    {
        $ligne = LigneCommande::creer(LigneCommande::PASS_3J, $this->reduit, [1, 5, 9], 2);

        $this->assertSame(72.75, $ligne->prixUnitaire);
        $this->assertSame(145.5, $ligne->sousTotal());
    }

    public function testLesSoireesSontReindexees(): void
    {
        $ligne = LigneCommande::creer(LigneCommande::PASS_3J, $this->normal, [3 => 2, 7 => 6, 9 => 8], 1);

        $this->assertSame([2, 6, 8], $ligne->idSoirees);
    }

    public function testPassAvecDeuxSoireesRefuse(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(400);
        LigneCommande::creer(LigneCommande::PASS_3J, $this->normal, [1, 2], 1);
    }

    public function testPassAvecQuatreSoireesRefuse(): void
    {
        $this->expectException(ValidationException::class);
        LigneCommande::creer(LigneCommande::PASS_3J, $this->normal, [1, 2, 3, 4], 1);
    }

    public function testPassAvecDeuxSoireesIdentiquesRefuse(): void
    {
        $this->expectException(ValidationException::class);
        LigneCommande::creer(LigneCommande::PASS_3J, $this->normal, [1, 1, 2], 1);
    }

    public function testBilletSimpleSurDeuxSoireesRefuse(): void
    {
        $this->expectException(ValidationException::class);
        LigneCommande::creer(LigneCommande::SIMPLE, $this->normal, [1, 2], 1);
    }

    public function testBilletSimpleSansSoireeRefuse(): void
    {
        $this->expectException(ValidationException::class);
        LigneCommande::creer(LigneCommande::SIMPLE, $this->normal, [], 1);
    }

    public function testIdentifiantDeSoireeInvalideRefuse(): void
    {
        $this->expectException(ValidationException::class);
        LigneCommande::creer(LigneCommande::SIMPLE, $this->normal, [0], 1);
    }

    public function testQuantiteNulleRefusee(): void
    {
        $this->expectException(ValidationException::class);
        LigneCommande::creer(LigneCommande::SIMPLE, $this->normal, [1], 0);
    }

    public function testQuantiteNegativeRefusee(): void
    {
        $this->expectException(ValidationException::class);
        LigneCommande::creer(LigneCommande::SIMPLE, $this->normal, [1], -3);
    }

    public function testValiderQuantite(): void
    {
        LigneCommande::validerQuantite(1);
        $this->addToAssertionCount(1);

        $this->expectException(ValidationException::class);
        LigneCommande::validerQuantite(0);
    }

    public function testTypeDeBilletInconnuRefuse(): void
    {
        $this->expectException(ValidationException::class);
        new LigneCommande(null, 'VIP', Tarif::NORMAL, 1, 35.0, [1]);
    }

    public function testCategorieInconnueRefusee(): void
    {
        $this->expectException(ValidationException::class);
        new LigneCommande(null, LigneCommande::SIMPLE, 'ETUDIANT', 1, 20.0, [1]);
    }
}
