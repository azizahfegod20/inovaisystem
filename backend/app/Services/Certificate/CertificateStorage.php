<?php

namespace App\Services\Certificate;

use App\Models\Certificate;
use App\Models\Company;

class CertificateStorage
{
    public function store(Company $company, string $pfxContent, string $password, array $parsedData): Certificate
    {
        $company->certificates()
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return Certificate::create([
            'company_id' => $company->id,
            'pfx_content' => base64_encode($pfxContent),
            'pfx_password' => $password,
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
        $password = $certificate->pfx_password;

        $certs = [];
        if (! openssl_pkcs12_read($pfxContent, $certs, $password)) {
            throw new \RuntimeException('Falha ao extrair PEM do certificado armazenado.');
        }

        return [
            'cert_pem' => $certs['cert'],
            'key_pem' => $certs['pkey'],
        ];
    }
}
