<?php

namespace App\Services\Certificate;

use App\Exceptions\CertificateException;
use Carbon\Carbon;

class CertificateParser
{
    public function parse(string $pfxContent, string $password): array
    {
        $certs = [];

        if (! openssl_pkcs12_read($pfxContent, $certs, $password)) {
            throw new CertificateException('Não foi possível ler o certificado. Verifique a senha.');
        }

        $certResource = openssl_x509_read($certs['cert']);

        if (! $certResource) {
            throw new CertificateException('Certificado inválido.');
        }

        $certInfo = openssl_x509_parse($certResource);

        if (! $certInfo) {
            throw new CertificateException('Não foi possível extrair informações do certificado.');
        }

        $commonName = $certInfo['subject']['CN'] ?? '';
        $cnpj = $this->extractCnpj($commonName, $certInfo);

        $validFrom = Carbon::createFromTimestamp($certInfo['validFrom_time_t']);
        $validTo = Carbon::createFromTimestamp($certInfo['validTo_time_t']);

        return [
            'cnpj' => $cnpj,
            'common_name' => $commonName,
            'valid_from' => $validFrom->toDateString(),
            'valid_to' => $validTo->toDateString(),
            'cert_pem' => $certs['cert'],
            'key_pem' => $certs['pkey'],
            'is_expired' => $validTo->isPast(),
        ];
    }

    protected function extractCnpj(string $commonName, array $certInfo): string
    {
        if (preg_match('/(\d{14})/', $commonName, $matches)) {
            return $matches[1];
        }

        $subjectAltName = $certInfo['extensions']['subjectAltName'] ?? '';
        if (preg_match('/(\d{14})/', $subjectAltName, $matches)) {
            return $matches[1];
        }

        $serialNumber = $certInfo['subject']['serialNumber'] ?? '';
        if (preg_match('/(\d{14})/', $serialNumber, $matches)) {
            return $matches[1];
        }

        throw new CertificateException('Não foi possível extrair o CNPJ do certificado.');
    }
}
