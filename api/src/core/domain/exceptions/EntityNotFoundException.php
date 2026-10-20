<?php
namespace nrv\core\domain\exceptions;

class EntityNotFoundException extends \RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 404);
    }
}
