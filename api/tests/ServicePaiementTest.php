<?php
namespace nrv\tests;

use nrv\core\application\ports\BilletRepositoryInterface;
use nrv\core\application\ports\CommandeRepositoryInterface;
use nrv\core\application\ports\SoireeRepositoryInterface;
use nrv\core\application\usecases\ServicePaiement;
use nrv\core\domain\entities\Billet;
use nrv\core\domain\entities\CarteBancaire;
use nrv\core\domain\entities\Commande;
use nrv\core\domain\entities\Lieu;
use nrv\core\domain\entities\LigneCommande;
use nrv\core\domain\entities\Soiree;
use nrv\core\domain\entities\Tarif;
use nrv\core\domain\exceptions\AccesInterditException;
use nrv\core\domain\exceptions\EntityNotFoundException;
use nrv\core\domain\exceptions\PlacesInsuffisantesException;
use nrv\core\domain\exceptions\TransitionStatutException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ServicePaiementTest extends TestCase
{
    private CommandeRepositoryInterface&MockObject $commandes;
    private BilletRepositoryInterface&MockObject $billets;
    private SoireeRepositoryInterface&MockObject $soirees;
    private ServicePaiement $service;
    private CarteBancaire $carte;

    protected function setUp(): void
    {
        $this->commandes = $this->createMock(CommandeRepositoryInterface::class);
        $this->billets = $this->createMock(BilletRepositoryInterface::class);
        $this->soirees = $this->createMock(SoireeRepositoryInterface::class);
        $this->soirees->method('findById')->willReturnCallback(fn(int $id) =>
            new Soiree($id, "Soirée $id", 'Rock', sprintf('2027-07-%02d', $id), '20:00', 100, 10, new Lieu(1, 'Le Hublot', 'Nancy', 250)));

        $this->service = new ServicePaiement($this->commandes, $this->billets, $this->soirees);
        $this->carte = CarteBancaire::depuis('4970101234567890', '12/28', '123', new \DateTimeImmutable('2026-10-23'));
    }

    private function commandeDeBob(string $statut = Commande::VALIDEE): Commande
    {
        $simple = LigneCommande::creer(LigneCommande::SIMPLE, new Tarif(Tarif::NORMAL, 35.0), [4], 2);
        $simple->id = 1;
        $pass = LigneCommande::creer(LigneCommande::PASS_3J, new Tarif(Tarif::REDUIT, 25.0), [2, 6, 11], 1);
        $pass->id = 2;
        return new Commande(3, 3, $statut, [$simple, $pass]);
    }

    public function testPaiementCreeUnBilletParUniteDeQuantite(): void
    {
        $commande = $this->commandeDeBob();
        $this->commandes->method('findById')->with(3)->willReturn($commande);

        $emis = [];
        $this->commandes->expects($this->once())->method('enregistrerPaiement')
            ->willReturnCallback(function (Commande $c, array $billets) use (&$emis) {
                foreach ($billets as $i => $b) {
                    $b->id = $i + 1;
                    $b->reference = sprintf('NRV-2027-%05d', $i + 1);
                }
                $emis = $billets;
            });

        $resultat = $this->service->payer(3, 3, $this->carte);

        $this->assertCount(3, $emis);
        $this->assertSame([[4], [4], [2, 6, 11]], array_map(fn(Billet $b) => $b->idSoirees, $emis));
        $this->assertSame([35.0, 35.0, 72.75], array_map(fn(Billet $b) => $b->prix, $emis));
        $this->assertSame(Commande::PAYEE, $commande->statut);
        $this->assertSame('PAYEE', $resultat['statut']);
        $this->assertSame(142.75, $resultat['montantTotal']);
        $this->assertSame(['NRV-2027-00001', 'NRV-2027-00002', 'NRV-2027-00003'], array_column($resultat['billets'], 'reference'));
    }

    public function testTokensAleatoiresDe64CaracteresTousDifferents(): void
    {
        $this->commandes->method('findById')->willReturn($this->commandeDeBob());
        $tokens = [];
        $this->commandes->method('enregistrerPaiement')
            ->willReturnCallback(function (Commande $c, array $billets) use (&$tokens) {
                $tokens = array_map(fn(Billet $b) => $b->token, $billets);
            });

        $this->service->payer(3, 3, $this->carte);

        $this->assertCount(3, array_unique($tokens));
        foreach ($tokens as $token) {
            $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
        }
    }

    public function testCommandeIntrouvable(): void
    {
        $this->commandes->method('findById')->willReturn(null);
        $this->commandes->expects($this->never())->method('enregistrerPaiement');

        $this->expectException(EntityNotFoundException::class);
        $this->service->payer(99, 3, $this->carte);
    }

    public function testCommandeDUnAutreUtilisateur(): void
    {
        $this->commandes->method('findById')->willReturn($this->commandeDeBob());
        $this->commandes->expects($this->never())->method('enregistrerPaiement');

        $this->expectException(AccesInterditException::class);
        $this->expectExceptionCode(403);
        $this->service->payer(3, 2, $this->carte);
    }

    public function testPanierNonValideRefuse(): void
    {
        $this->commandes->method('findById')->willReturn($this->commandeDeBob(Commande::PANIER));
        $this->commandes->expects($this->never())->method('enregistrerPaiement');

        $this->expectException(TransitionStatutException::class);
        $this->expectExceptionCode(409);
        $this->service->payer(3, 3, $this->carte);
    }

    public function testCommandeDejaPayeeRefusee(): void
    {
        $this->commandes->method('findById')->willReturn($this->commandeDeBob(Commande::PAYEE));
        $this->commandes->expects($this->never())->method('enregistrerPaiement');

        $this->expectException(TransitionStatutException::class);
        $this->service->payer(3, 3, $this->carte);
    }

    public function testPlacesInsuffisantesRemonteEn409(): void
    {
        $this->commandes->method('findById')->willReturn($this->commandeDeBob());
        $this->commandes->method('enregistrerPaiement')
            ->willThrowException(new PlacesInsuffisantesException('Plus assez de places'));

        $this->expectException(PlacesInsuffisantesException::class);
        $this->expectExceptionCode(409);
        $this->service->payer(3, 3, $this->carte);
    }

    public function testGetCommandeValideeSansBillets(): void
    {
        $this->commandes->method('findById')->willReturn($this->commandeDeBob());
        $this->billets->expects($this->never())->method('findByCommande');

        $resultat = $this->service->getCommande(3, 3);

        $this->assertSame('VALIDEE', $resultat['statut']);
        $this->assertSame([], $resultat['billets']);
        $this->assertSame('Soirée 4', $resultat['lignes'][0]['soirees'][0]['nom']);
    }

    public function testGetCommandePayeeAvecSesBillets(): void
    {
        $this->commandes->method('findById')->willReturn($this->commandeDeBob(Commande::PAYEE));
        $this->billets->method('findByCommande')->with(3)->willReturn([
            new Billet(10, 'NRV-2027-00010', str_repeat('a', 64), 'SIMPLE', 'NORMAL', 35.0, 3, [4], 'Bob Durand'),
        ]);

        $resultat = $this->service->getCommande(3, 3);

        $this->assertSame([['reference' => 'NRV-2027-00010', 'typeBillet' => 'SIMPLE', 'categorie' => 'NORMAL', 'prix' => 35.0, 'token' => str_repeat('a', 64)]], $resultat['billets']);
    }

    public function testGetCommandeDUnAutreUtilisateur(): void
    {
        $this->commandes->method('findById')->willReturn($this->commandeDeBob());

        $this->expectException(AccesInterditException::class);
        $this->service->getCommande(3, 4);
    }

    public function testGetCommandeIntrouvable(): void
    {
        $this->commandes->method('findById')->willReturn(null);

        $this->expectException(EntityNotFoundException::class);
        $this->service->getCommande(99, 3);
    }
}
