<?php
namespace nrv\tests;

use nrv\core\domain\entities\Commande;
use nrv\core\domain\entities\LigneCommande;
use nrv\core\domain\entities\Tarif;
use nrv\core\domain\exceptions\EntityNotFoundException;
use nrv\core\domain\exceptions\TransitionStatutException;
use PHPUnit\Framework\TestCase;

class CommandeTest extends TestCase
{
    private function ligne(int $id, string $type, string $categorie, array $soirees, int $quantite): LigneCommande
    {
        $tarif = $categorie === Tarif::NORMAL ? new Tarif(Tarif::NORMAL, 35.0) : new Tarif(Tarif::REDUIT, 25.0);
        $ligne = LigneCommande::creer($type, $tarif, $soirees, $quantite);
        $ligne->id = $id;
        return $ligne;
    }

    private function panier(array $lignes = []): Commande
    {
        return new Commande(12, 7, Commande::PANIER, $lignes);
    }

    private function panierMixte(): Commande
    {
        return $this->panier([
            $this->ligne(1, LigneCommande::SIMPLE, Tarif::NORMAL, [4], 2),
            $this->ligne(2, LigneCommande::PASS_3J, Tarif::REDUIT, [1, 4, 9], 1),
            $this->ligne(3, LigneCommande::SIMPLE, Tarif::REDUIT, [9], 3),
            $this->ligne(4, LigneCommande::PASS_3J, Tarif::NORMAL, [2, 4, 6], 1),
        ]);
    }

    public function testMontantTotalExempleDuPlan(): void
    {
        $commande = $this->panier([
            $this->ligne(1, LigneCommande::SIMPLE, Tarif::NORMAL, [4], 2),
            $this->ligne(2, LigneCommande::PASS_3J, Tarif::REDUIT, [1, 5, 9], 1),
        ]);

        $this->assertSame(142.75, $commande->montantTotal());
    }

    public function testMontantTotalAvecSimplePassNormalEtReduit(): void
    {
        $this->assertSame(319.6, $this->panierMixte()->montantTotal());
    }

    public function testMontantTotalDUnPanierVide(): void
    {
        $this->assertSame(0.0, $this->panier()->montantTotal());
    }

    public function testPanierModifiable(): void
    {
        $this->assertTrue($this->panier()->estModifiable());
        $this->assertTrue($this->panier()->estVide());
    }

    public function testCommandeValideeOuPayeeNonModifiable(): void
    {
        $this->assertFalse((new Commande(1, 7, Commande::VALIDEE))->estModifiable());
        $this->assertFalse((new Commande(1, 7, Commande::PAYEE))->estModifiable());
    }

    public function testAppartientA(): void
    {
        $commande = $this->panier();

        $this->assertTrue($commande->appartientA(7));
        $this->assertFalse($commande->appartientA(8));
    }

    public function testTrouverUneLigne(): void
    {
        $this->assertSame(3, $this->panierMixte()->ligne(3)->quantite);
    }

    public function testLigneInconnue(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->panierMixte()->ligne(99);
    }

    public function testCycleCompletPanierValideePayee(): void
    {
        $commande = $this->panierMixte();

        $commande->valider();
        $this->assertSame(Commande::VALIDEE, $commande->statut);
        $this->assertFalse($commande->estModifiable());

        $commande->payer();
        $this->assertSame(Commande::PAYEE, $commande->statut);
    }

    public function testValiderUnPanierVideInterdit(): void
    {
        $this->expectException(TransitionStatutException::class);
        $this->expectExceptionCode(409);
        $this->panier()->valider();
    }

    public function testValiderDeuxFoisInterdit(): void
    {
        $commande = $this->panierMixte();
        $commande->valider();

        $this->expectException(TransitionStatutException::class);
        $commande->valider();
    }

    public function testPayerUnPanierNonValideInterdit(): void
    {
        $this->expectException(TransitionStatutException::class);
        $this->expectExceptionCode(409);
        $this->panierMixte()->payer();
    }

    public function testPayerDeuxFoisInterdit(): void
    {
        $commande = $this->panierMixte();
        $commande->valider();
        $commande->payer();

        $this->expectException(TransitionStatutException::class);
        $commande->payer();
    }

    public function testValiderUneCommandePayeeInterdit(): void
    {
        $this->expectException(TransitionStatutException::class);
        (new Commande(1, 7, Commande::PAYEE, [$this->ligne(1, LigneCommande::SIMPLE, Tarif::NORMAL, [1], 1)]))->valider();
    }

    public function testUnPassCompteUnePlaceDansChacuneDeSesSoirees(): void
    {
        $commande = $this->panier([$this->ligne(1, LigneCommande::PASS_3J, Tarif::NORMAL, [2, 6, 7], 1)]);

        $this->assertSame([2 => 1, 6 => 1, 7 => 1], $commande->placesParSoiree());
    }

    public function testPlacesMultiplieesParLaQuantite(): void
    {
        $commande = $this->panier([$this->ligne(1, LigneCommande::PASS_3J, Tarif::NORMAL, [2, 6, 7], 3)]);

        $this->assertSame([2 => 3, 6 => 3, 7 => 3], $commande->placesParSoiree());
    }

    public function testPlacesCumuleesSurToutesLesLignes(): void
    {
        $this->assertSame([1 => 1, 2 => 1, 4 => 4, 6 => 1, 9 => 4], $this->panierMixte()->placesParSoiree());
    }

    public function testAucunePlacePourUnPanierVide(): void
    {
        $this->assertSame([], $this->panier()->placesParSoiree());
    }
}
