<?php

namespace App\Services\Certificate;

use App\Exceptions\CertificateStorageException;
use App\Models\Certificate;
use App\Models\Company;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class CertificateStorage
{
    public function store(Company $company, string $pfxContent, string $password, array $parsedData): Certificate
    {
        $company->certificates()
            ->where('is_active', true)
            ->update(['is_active' => false]);

        try {
            $encryptedPassword = Crypt::encryptString($password);
        } catch (\Throwable $e) {
            throw CertificateStorageException::encryptionFailed($e->getMessage());
        }

        return Certificate::create([
            'company_id' => $company->id,
            'pfx_content' => base64_encode($pfxContent),
            'pfx_password' => $encryptedPassword,
            'cnpj' => $parsedData['cnpj'],
            'common_name' => $parsedData['common_name'],
            'valid_from' => $parsedData['valid_from'],
            'valid_to' => $parsedData['valid_to'],
            'is_active' => true,
        ]);
    }

    public function getActiveCertificate(Company $company): ?Certificate
    {
        return $company->certificates()
            ->where('is_active', true)
            ->whereDate('valid_to', '>=', now())
            ->first();
    }

    public function extractPemFiles(Certificate $certificate): array
    {
        $pfxContent = base64_decode($certificate->pfx_content);

        try {
            $password = Crypt::decryptString($certificate->pfx_password);
        } catch (DecryptException $e) {
            Log::warning('Falha ao descriptografar senha do certificado', [
                'certificate_id' => $certificate->id,
                'company_id' => $certificate->company_id,
                'error' => $e->getMessage(),
            ]);
            throw CertificateStorageException::decryptionFailed($certificate->id);
        }

        $certs = [];
        if (! openssl_pkcs12_read($pfxContent, $certs, $password)) {
            throw new CertificateStorageException(
                'Falha ao extrair PEM do certificado armazenado.',
                $certificate->id
            );
        }

        return [
            'cert_pem' => $certs['cert'],
            'key_pem' => $certs['pkey'],
        ];
    }
}
