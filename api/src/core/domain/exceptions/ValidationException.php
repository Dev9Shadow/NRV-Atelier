<?php

namespace nrv\core\domain\exceptions;

use Exception;

class ValidationException extends Exception
{
    protected $code = 400;
}