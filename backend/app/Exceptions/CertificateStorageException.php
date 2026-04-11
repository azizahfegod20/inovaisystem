<?php

namespace App\Exceptions;

class CertificateStorageException extends CertificateException
{
    public static function decryptionFailed(int $certificateId): self
    {
        return new self(
            "Falha ao descriptografar senha do certificado #{$certificateId}. Verifique se a APP_KEY está correta.",
            $certificateId
        );
    }

    public static function encryptionFailed(string $reason): self
    {
        return new self("Falha ao criptografar senha do certificado: {$reason}");
    }

    public static function notFound(int $companyId): self
    {
        return new self("Nenhum certificado digital ativo encontrado para a empresa #{$companyId}.");
    }
}
