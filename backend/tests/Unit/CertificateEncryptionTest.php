<?php

namespace Tests\Unit;

use App\Exceptions\CertificateStorageException;
use App\Models\Certificate;
use App\Models\Company;
use App\Services\Certificate\CertificateStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class CertificateEncryptionTest extends TestCase
{
    use RefreshDatabase;

    protected CertificateStorage $storage;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = app(CertificateStorage::class);
        $this->company = Company::factory()->create();
    }

    public function test_store_encrypts_password(): void
    {
        $password = 'minha-senha-secreta';

        $this->storage->store($this->company, 'pfx-content', $password, [
            'cnpj' => '12345678000190',
            'common_name' => 'Test Cert',
            'valid_from' => '2024-01-01',
            'valid_to' => '2025-01-01',
        ]);

        $cert = Certificate::first();
        $this->assertNotNull($cert->pfx_password);
        $this->assertNotSame($password, $cert->pfx_password);

        // Verify it can be decrypted
        $this->assertSame($password, Crypt::decryptString($cert->pfx_password));
    }

    public function test_extract_pem_files_decrypts_password(): void
    {
        $this->createTestCertificate(encrypted: true);

        $cert = Certificate::first();
        $result = $this->storage->extractPemFiles($cert);

        $this->assertArrayHasKey('cert_pem', $result);
        $this->assertArrayHasKey('key_pem', $result);
        $this->assertNotEmpty($result['cert_pem']);
        $this->assertNotEmpty($result['key_pem']);
    }

    public function test_extract_with_corrupted_password_throws_exception(): void
    {
        $cert = Certificate::create([
            'company_id' => $this->company->id,
            'pfx_content' => base64_encode('invalid-pfx'),
            'pfx_password' => 'not-a-valid-laravel-encrypted-string',
            'cnpj' => '12345678000190',
            'common_name' => 'Test',
            'valid_from' => '2024-01-01',
            'valid_to' => '2025-01-01',
            'is_active' => true,
        ]);

        $this->expectException(CertificateStorageException::class);
        $this->storage->extractPemFiles($cert);
    }

    public function test_store_deactivates_previous_certificate(): void
    {
        $this->createTestCertificate(encrypted: true);

        $firstCert = Certificate::first();
        $this->assertTrue($firstCert->is_active);

        $this->storage->store($this->company, 'pfx-content-2', 'password2', [
            'cnpj' => '12345678000190',
            'common_name' => 'Test Cert 2',
            'valid_from' => '2024-01-01',
            'valid_to' => '2025-01-01',
        ]);

        $firstCert->refresh();
        $this->assertFalse($firstCert->is_active);

        $secondCert = Certificate::latest('id')->first();
        $this->assertTrue($secondCert->is_active);
    }

    protected function createTestCertificate(bool $encrypted = true): void
    {
        $config = [
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $privateKey = openssl_pkey_new($config);
        $csr = openssl_csr_new([
            'commonName' => 'TEST:12345678000190',
        ], $privateKey);
        $cert = openssl_csr_sign($csr, null, $privateKey, 365);

        openssl_pkcs12_export($cert, $pfxContent, $privateKey, 'test123');

        $password = $encrypted ? Crypt::encryptString('test123') : 'test123';

        Certificate::create([
            'company_id' => $this->company->id,
            'pfx_content' => base64_encode($pfxContent),
            'pfx_password' => $password,
            'cnpj' => '12345678000190',
            'common_name' => 'TEST:12345678000190',
            'valid_from' => now()->subMonth()->toDateString(),
            'valid_to' => now()->addYear()->toDateString(),
            'is_active' => true,
        ]);
    }
}
