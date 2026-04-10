<?php

namespace App\Exceptions;

use Exception;

class CnpjRateLimitException extends Exception
{
    public function __construct()
    {
        parent::__construct('Rate limit excedido. Aguarde 1 minuto.');
    }
}
