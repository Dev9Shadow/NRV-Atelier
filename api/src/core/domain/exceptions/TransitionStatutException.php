<?php
namespace nrv\core\domain\exceptions;

// Changement de statut interdit : valider un panier vide, payer deux fois, modifier un panier validé
class TransitionStatutException extends \DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 409);
    }
}
