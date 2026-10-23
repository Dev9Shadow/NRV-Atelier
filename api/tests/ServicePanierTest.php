<?php
namespace nrv\tests;

use nrv\core\application\ports\CommandeRepositoryInterface;
use nrv\core\application\ports\SoireeRepositoryInterface;
use nrv\core\application\ports\TarifRepositoryInterface;
use nrv\core\application\usecases\ServicePanier;
use nrv\core\domain\entities\Commande;
use nrv\core\domain\entities\Lieu;
use nrv\core\domain\entities\LigneCommande;
use nrv\core\domain\entities\Soiree;
use nrv\core\domain\entities\Tarif;
use nrv\core\domain\exceptions\EntityNotFoundException;
use nrv\core\domain\exceptions\PlacesInsuffisantesException;
use nrv\core\domain\exceptions\ValidationException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ServicePanierTest extends TestCase
{
    private CommandeRepositoryInterface&MockObject $commandes;
    private ServicePanier $service;
    private array $placesRestantes = [1 => 100, 2 => 100, 3 => 100, 4 => 3];

    protected function setUp(): void
    {
        $this->commandes = $this->createMock(CommandeRepositoryInterface::class);

        $soirees = $this->createMock(SoireeRepositoryInterface::class);
        $soirees->method('findById')->willReturnCallback(fn(int $id) => isset($this->placesRestantes[$id])
            ? new Soiree($id, "Soirée $id", 'Rock', sprintf('2027-07-%02d', $id), '20:00', 100, 100 - $this->placesRestantes[$id], new Lieu(1, 'Le Hublot', 'Nancy', 250))
            : null);

        $tarifs = $this->createMock(TarifRepositoryInterface::class);
        $tarifs->method('findAll')->willReturn([
            Tarif::NORMAL => new Tarif(Tarif::NORMAL, 35.0),
            Tarif::REDUIT => new Tarif(Tarif::REDUIT, 25.0),
        ]);

        $this->service = new ServicePanier($this->commandes, $soirees, $tarifs);
    }

    private function panierAvec(array $lignes = []): Commande
    {
        return new Commande(12, 3, Commande::PANIER, $lignes);
    }

    public function testGetPanierCreeUnPanierVideSiAucun(): void
    {
        $this->commandes->method('findPanierByUtilisateur')->with(3)->willReturn(null);
        $this->commandes->expects($this->once())->method('creerPanier')->with(3)->willReturn($this->panierAvec());

        $panier = $this->service->getPanier(3);

        $this->assertSame(12, $panier['id']);
        $this->assertSame('PANIER', $panier['statut']);
        $this->assertSame([], $panier['lignes']);
        $this->assertEquals(0, $panier['montantTotal']);
    }

    public function testAjouterUnPassReduitAppliqueLaRemise(): void
    {
        $this->commandes->method('findPanierByUtilisateur')->willReturn($this->panierAvec());
        $this->commandes->expects($this->once())->method('ajouterLigne')->with(12)->willReturn(7);

        $panier = $this->service->ajouterLigne(3, LigneCommande::PASS_3J, Tarif::REDUIT, [1, 2, 3], 2);

        $this->assertCount(1, $panier['lignes']);
        $this->assertSame(7, $panier['lignes'][0]['id']);
        $this->assertSame(72.75, $panier['lignes'][0]['prixUnitaire']);
        $this->assertSame(145.5, $panier['montantTotal']);
        $this->assertSame(['Soirée 1', 'Soirée 2', 'Soirée 3'], array_column($panier['lignes'][0]['soirees'], 'nom'));
    }

    public function testAjoutRefuseSiPlusAssezDePlaces(): void
    {
        $this->commandes->method('findPanierByUtilisateur')->willReturn($this->panierAvec());
        $this->commandes->expects($this->never())->method('ajouterLigne');

        $this->expectException(PlacesInsuffisantesException::class);
        $this->service->ajouterLigne(3, LigneCommande::SIMPLE, Tarif::NORMAL, [4], 4);
    }

    public function testAjoutTientCompteDesBilletsDejaDansLePanier(): void
    {
        $existante = LigneCommande::creer(LigneCommande::SIMPLE, new Tarif(Tarif::NORMAL, 35.0), [4], 2);
        $existante->id = 1;
        $this->commandes->method('findPanierByUtilisateur')->willReturn($this->panierAvec([$existante]));
        $this->commandes->expects($this->never())->method('ajouterLigne');

        $this->expectException(PlacesInsuffisantesException::class);
        $this->service->ajouterLigne(3, LigneCommande::PASS_3J, Tarif::NORMAL, [1, 2, 4], 2);
    }

    public function testCategorieDeTarifInconnueRefusee(): void
    {
        $this->commandes->expects($this->never())->method('ajouterLigne');

        $this->expectException(ValidationException::class);
        $this->service->ajouterLigne(3, LigneCommande::SIMPLE, 'ETUDIANT', [1], 1);
    }

    public function testSoireeInexistanteRefusee(): void
    {
        $this->commandes->method('findPanierByUtilisateur')->willReturn($this->panierAvec());
        $this->commandes->expects($this->never())->method('ajouterLigne');

        $this->expectException(EntityNotFoundException::class);
        $this->service->ajouterLigne(3, LigneCommande::SIMPLE, Tarif::NORMAL, [99], 1);
    }
}
