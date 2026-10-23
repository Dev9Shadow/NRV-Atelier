<?php
namespace nrv\core\domain\entities;

use nrv\core\domain\exceptions\ValidationException;

final class CarteBancaire
{
    private function __construct(
        public readonly string $numero,
        public readonly int $moisExpiration,
        public readonly int $anneeExpiration
    ) {}

    public static function depuis(mixed $numero, mixed $expiration, mixed $cvc, ?\DateTimeImmutable $maintenant = null): self
    {
        $numero = is_string($numero) ? preg_replace('/[\s-]/', '', $numero) : '';
        if (!preg_match('/^\d{16}$/', $numero)) {
            throw new ValidationException('Numéro de carte invalide : 16 chiffres attendus');
        }

        if (!is_string($expiration) || !preg_match('#^(0[1-9]|1[0-2])/(\d{2})$#', $expiration, $m)) {
            throw new ValidationException("Date d'expiration invalide : format MM/AA attendu");
        }
        $mois = (int)$m[1];
        $annee = 2000 + (int)$m[2];
        $finDeValidite = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $annee, $mois));
        if (($maintenant ?? new \DateTimeImmutable()) >= $finDeValidite->modify('+1 month')) {
            throw new ValidationException('Carte expirée');
        }

        if (!is_string($cvc) || !preg_match('/^\d{3}$/', $cvc)) {
            throw new ValidationException('Code de sécurité invalide : 3 chiffres attendus');
        }

        return new self($numero, $mois, $annee);
    }

    public function numeroMasque(): string
    {
        return '**** **** **** ' . substr($this->numero, -4);
    }
}
