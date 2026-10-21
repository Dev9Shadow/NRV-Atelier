<?php
namespace nrv\core\domain\exceptions;

class InvalidFilterException extends \InvalidArgumentException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 400);
    }
}
