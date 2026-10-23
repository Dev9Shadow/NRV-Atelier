<?php
namespace nrv\tests;

use nrv\core\domain\entities\CarteBancaire;
use nrv\core\domain\exceptions\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CarteBancaireTest extends TestCase
{
    private \DateTimeImmutable $maintenant;

    protected function setUp(): void
    {
        $this->maintenant = new \DateTimeImmutable('2026-10-23 14:00:00');
    }

    public function testCarteValide(): void
    {
        $carte = CarteBancaire::depuis('4970101234567890', '12/28', '123', $this->maintenant);

        $this->assertSame('4970101234567890', $carte->numero);
        $this->assertSame(12, $carte->moisExpiration);
        $this->assertSame(2028, $carte->anneeExpiration);
    }

    public function testEspacesEtTiretsAcceptesDansLeNumero(): void
    {
        $this->assertSame('4970101234567890', CarteBancaire::depuis('4970 1012-3456 7890', '12/28', '123', $this->maintenant)->numero);
    }

    public function testNumeroMasque(): void
    {
        $this->assertSame('**** **** **** 7890', CarteBancaire::depuis('4970101234567890', '12/28', '123', $this->maintenant)->numeroMasque());
    }

    public function testValableJusquALaFinDuMoisDExpiration(): void
    {
        $this->assertSame(10, CarteBancaire::depuis('4970101234567890', '10/26', '123', new \DateTimeImmutable('2026-10-31 23:59:59'))->moisExpiration);
    }

    public function testExpireeLePremierDuMoisSuivant(): void
    {
        $this->expectException(ValidationException::class);
        CarteBancaire::depuis('4970101234567890', '10/26', '123', new \DateTimeImmutable('2026-11-01 00:00:00'));
    }

    public static function cartesInvalides(): array
    {
        return [
            'numéro de 4 chiffres' => ['1234', '12/28', '123'],
            'numéro de 15 chiffres' => ['497010123456789', '12/28', '123'],
            'numéro de 17 chiffres' => ['49701012345678901', '12/28', '123'],
            'lettre dans le numéro' => ['4970A01234567890', '12/28', '123'],
            'numéro absent' => [null, '12/28', '123'],
            'carte expirée' => ['4970101234567890', '09/26', '123'],
            'expiration MM/AAAA' => ['4970101234567890', '12/2028', '123'],
            'mois 00' => ['4970101234567890', '00/28', '123'],
            'mois 13' => ['4970101234567890', '13/28', '123'],
            'expiration absente' => ['4970101234567890', null, '123'],
            'CVC de 2 chiffres' => ['4970101234567890', '12/28', '12'],
            'CVC de 4 chiffres' => ['4970101234567890', '12/28', '1234'],
            'CVC en lettres' => ['4970101234567890', '12/28', 'abc'],
            'CVC absent' => ['4970101234567890', '12/28', null],
        ];
    }

    #[DataProvider('cartesInvalides')]
    public function testCarteInvalideRefusee(mixed $numero, mixed $expiration, mixed $cvc): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(400);
        CarteBancaire::depuis($numero, $expiration, $cvc, $this->maintenant);
    }
}
