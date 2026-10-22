<?php

namespace nrv\core\domain\exceptions;

use Exception;

class NonAuthentifieException extends Exception
{
    protected $code = 401;
}