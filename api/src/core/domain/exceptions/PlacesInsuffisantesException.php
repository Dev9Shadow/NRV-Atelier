<?php
namespace nrv\core\domain\exceptions;

// Plus assez de places dans une soirée, à l'ajout au panier ou au paiement
class PlacesInsuffisantesException extends \DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 409);
    }
}
