<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\User;
use App\Services\Nfse\InvoiceCanceller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class InvoiceCancellationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;
    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
        $this->user->companies()->attach($this->company->id, ['role' => 'admin']);

        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $service = Service::factory()->create(['company_id' => $this->company->id]);

        $this->invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'user_id' => $this->user->id,
            'status' => InvoiceStatus::AUTHORIZED->value,
            'id_dps' => str_pad('1', 42, '0', STR_PAD_LEFT),
            'dps_number' => 1,
            'dps_serie' => '00001',
            'chave_acesso' => 'NFSe' . str_pad('1', 46, '0', STR_PAD_LEFT),
            'numero_nfse' => 12345,
            'valor_servico' => 5000.00,
            'valor_deducoes' => 0,
            'valor_desconto' => 0,
            'valor_liquido' => 5000.00,
            'aliquota_iss' => 0.0500,
            'valor_iss' => 250.00,
            'iss_retido' => false,
            'valor_ir' => 0,
            'valor_csll' => 0,
            'valor_cofins' => 0,
            'valor_pis' => 0,
            'valor_inss' => 0,
            'descricao_servico' => 'Consultoria em TI',
            'data_emissao' => now(),
        ]);
    }

    public function test_cancel_authorized_invoice_with_mock_adn(): void
    {
        $cancellerMock = Mockery::mock(InvoiceCanceller::class);
        $cancellerMock->shouldReceive('cancel')
            ->once()
            ->andReturnUsing(function (Invoice $invoice) {
                $invoice->update([
                    'status' => InvoiceStatus::CANCELLED->value,
                    'data_cancelamento' => now(),
                    'motivo_cancelamento' => 'Serviço não foi prestado conforme contratado originalmente',
                ]);

                return $invoice->fresh();
            });

        $this->app->instance(InvoiceCanceller::class, $cancellerMock);

        $response = $this->actingAs($this->user)
            ->withSession(['company_id' => $this->company->id])
            ->postJson("/api/invoices/{$this->invoice->id}/cancel", [
                'motivo' => 'Serviço não foi prestado conforme contratado originalmente',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'cancelled');

        $this->assertDatabaseHas('invoices', [
            'id' => $this->invoice->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_cancel_requires_minimum_motivo_length(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['company_id' => $this->company->id])
            ->postJson("/api/invoices/{$this->invoice->id}/cancel", [
                'motivo' => 'curto',
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_cancel_already_cancelled_invoice(): void
    {
        $this->invoice->update(['status' => InvoiceStatus::CANCELLED->value]);

        $response = $this->actingAs($this->user)
            ->withSession(['company_id' => $this->company->id])
            ->postJson("/api/invoices/{$this->invoice->id}/cancel", [
                'motivo' => 'Motivo suficientemente longo para validar',
            ]);

        $response->assertStatus(409);
    }

    public function test_cancel_requires_authentication(): void
    {
        $response = $this->postJson("/api/invoices/{$this->invoice->id}/cancel", [
            'motivo' => 'Motivo suficientemente longo para validar',
        ]);

        $response->assertStatus(401);
    }
}
