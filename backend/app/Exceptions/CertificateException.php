<?php

namespace App\Exceptions;

use RuntimeException;

class CertificateException extends RuntimeException
{
    protected int $certificateId;

    public function __construct(string $message, int $certificateId = 0, ?\Throwable $previous = null)
    {
        $this->certificateId = $certificateId;
        parent::__construct($message, 0, $previous);
    }

    public function getCertificateId(): int
    {
        return $this->certificateId;
    }
}
