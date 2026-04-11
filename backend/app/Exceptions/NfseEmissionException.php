<?php

namespace App\Exceptions;

use RuntimeException;

class NfseEmissionException extends RuntimeException
{
    protected string $stage;

    protected bool $retryable;

    public function __construct(string $message, string $stage = 'unknown', bool $retryable = false, ?\Throwable $previous = null)
    {
        $this->stage = $stage;
        $this->retryable = $retryable;
        parent::__construct($message, 0, $previous);
    }

    public function getStage(): string
    {
        return $this->stage;
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    public static function adnTimeout(string $detail, ?\Throwable $previous = null): self
    {
        return new self(
            "Timeout na comunicação com o ADN: {$detail}",
            stage: 'adn_communication',
            retryable: true,
            previous: $previous
        );
    }

    public static function adnRejected(string $errorCode, string $message): self
    {
        return new self(
            "ADN rejeitou a DPS [{$errorCode}]: {$message}",
            stage: 'adn_rejection',
            retryable: false
        );
    }

    public static function xmlValidation(array $errors): self
    {
        return new self(
            'XML inválido: '.implode('; ', $errors),
            stage: 'xml_validation',
            retryable: false
        );
    }

    public static function municipalNotAdherent(string $ibgeCode): self
    {
        return new self(
            "O município (IBGE: {$ibgeCode}) não aderiu ao Padrão Nacional de NFS-e.",
            stage: 'municipal_validation',
            retryable: false
        );
    }

    public static function idempotency(int $invoiceId): self
    {
        return new self(
            "DPS já emitida (idempotência) - Invoice #{$invoiceId}",
            stage: 'idempotency_check',
            retryable: false
        );
    }
}
