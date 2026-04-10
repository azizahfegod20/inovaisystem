<?php

namespace App\Exceptions;

use Exception;

class CnpjNotFoundException extends Exception
{
    public function __construct(string $cnpj)
    {
        parent::__construct("CNPJ não encontrado na base da Receita Federal: {$cnpj}");
    }
}
