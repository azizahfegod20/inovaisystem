<?php

namespace Tests\Feature;

use App\Enums\CompanyRole;
use App\Enums\InvoiceStatus;
use App\Models\Certificate;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Services\Municipal\ParameterService;
use App\Services\Nfse\AdnClient;
use App\Services\Storage\MinioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class InvoiceEmissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected Customer $customer;

    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create([
            'cnpj' => '12345678000190',
            'dps_serie' => '00001',
            'dps_next_number' => 1,
            'ambiente' => 2,
            'codigo_ibge' => '3550308',
        ]);

        $this->user->companies()->attach($this->company->id, [
            'role' => CompanyRole::ADMIN->value,
        ]);

        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'tipo_documento' => '2',
            'documento' => '98765432000190',
        ]);

        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'codigo_lc116' => '01.01',
            'aliquota_iss' => 0.0500,
        ]);

        $this->createTestCertificate();
    }

    protected function createTestCertificate(): void
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

        Certificate::create([
            'company_id' => $this->company->id,
            'pfx_content' => base64_encode($pfxContent),
            'pfx_password' => Crypt::encryptString('test123'),
            'cnpj' => '12345678000190',
            'common_name' => 'TEST:12345678000190',
            'valid_from' => now()->subMonth(),
            'valid_to' => now()->addYear(),
            'is_active' => true,
        ]);
    }

    public function test_emission_creates_invoice_with_mock_adn(): void
    {
        $this->mock(AdnClient::class, function ($mock) {
            $mock->shouldReceive('sendDps')->once()->andReturn([
                'success' => true,
                'status' => 200,
                'data' => [
                    'chaveAcesso' => 'NFSe35503082123456780001900000100000000000000001',
                    'numeroNfse' => 12345,
                    'xml' => '<nfseResponse>OK</nfseResponse>',
                ],
            ]);
        });

        $this->mock(ParameterService::class, function ($mock) {
            $mock->shouldReceive('getByIbge')->andReturn([
                'aderente_padrao_nacional' => true,
                'codigo_ibge' => '3550308',
            ]);
        });

        $this->mock(MinioService::class, function ($mock) {
            $mock->shouldReceive('upload')->andReturn(true);
        });

        $response = $this->actingAs($this->user)
            ->withSession(['company_id' => $this->company->id])
            ->postJson('/api/invoices', [
                'customer_id' => $this->customer->id,
                'service_id' => $this->service->id,
                'valor_servico' => 5000.00,
                'descricao_servico' => 'Consultoria em desenvolvimento de sistemas',
                'aliquota_iss' => 0.0500,
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id', 'id_dps', 'dps_number', 'dps_serie', 'chave_acesso',
            'status', 'valor_servico', 'valor_iss', 'data_emissao',
        ]);

        $this->assertDatabaseHas('invoices', [
            'company_id' => $this->company->id,
            'status' => InvoiceStatus::AUTHORIZED->value,
        ]);
    }

    public function test_emission_requires_authentication(): void
    {
        $response = $this->postJson('/api/invoices', [
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'valor_servico' => 5000.00,
            'descricao_servico' => 'Test',
        ]);

        $response->assertStatus(401);
    }

    public function test_emission_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['company_id' => $this->company->id])
            ->postJson('/api/invoices', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer_id', 'service_id', 'valor_servico', 'descricao_servico']);
    }
}
