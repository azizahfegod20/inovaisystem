<?php

namespace App\Exceptions;

use RuntimeException;

class DpsGenerationException extends RuntimeException
{
    protected int $companyId;

    protected bool $retryable;

    public function __construct(string $message, int $companyId = 0, bool $retryable = true, ?\Throwable $previous = null)
    {
        $this->companyId = $companyId;
        $this->retryable = $retryable;
        parent::__construct($message, 0, $previous);
    }

    public function getCompanyId(): int
    {
        return $this->companyId;
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    public static function lockTimeout(int $companyId): self
    {
        return new self(
            "Timeout ao adquirir lock para geração de DPS da empresa #{$companyId}. Tente novamente.",
            $companyId,
            retryable: true
        );
    }
}
