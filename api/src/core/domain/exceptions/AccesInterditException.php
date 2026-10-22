<?php

namespace nrv\core\domain\exceptions;

use Exception;

class AccesInterditException extends Exception
{
    protected $code = 403;
}