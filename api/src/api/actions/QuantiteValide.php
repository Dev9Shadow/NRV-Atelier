<?php
namespace nrv\api\actions;

use nrv\core\domain\exceptions\ValidationException;

final class QuantiteValide
{
    public const MAX = 10;

    public static function depuis(mixed $valeur): int
    {
        $quantite = filter_var($valeur, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => self::MAX]]);
        if ($quantite === false) {
            throw new ValidationException('La quantité doit être un entier entre 1 et ' . self::MAX);
        }
        return $quantite;
    }
}
