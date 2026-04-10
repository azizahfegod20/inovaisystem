<?php

namespace App\Observers;

use App\Enums\AuditOperation;
use App\Models\AuditLog;
use App\Models\Invoice;

class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        $this->logOperation($invoice, AuditOperation::INVOICE_CREATED, 'success');
    }

    public function updated(Invoice $invoice): void
    {
        if ($invoice->wasChanged('status')) {
            $operation = match ($invoice->status->value) {
                'authorized' => AuditOperation::INVOICE_AUTHORIZED,
                'cancelled' => AuditOperation::INVOICE_CANCELLED,
                'replaced' => AuditOperation::INVOICE_REPLACED,
                'rejected' => AuditOperation::INVOICE_REJECTED,
                default => null,
            };

            if ($operation) {
                $this->logOperation($invoice, $operation, 'success');
            }
        }
    }

    private function logOperation(Invoice $invoice, AuditOperation $operation, string $result): void
    {
        AuditLog::create([
            'company_id' => $invoice->company_id,
            'user_id' => $invoice->user_id,
            'invoice_id' => $invoice->id,
            'operation' => $operation->value,
            'payload_summary' => json_encode([
                'id_dps' => $invoice->id_dps,
                'status' => $invoice->status->value,
                'valor_servico' => $invoice->valor_servico,
                'chave_acesso' => $invoice->chave_acesso,
            ]),
            'result' => $result,
            'error_code' => null,
            'ip_address' => request()?->ip() ?? '0.0.0.0',
            'created_at' => now(),
        ]);
    }
}
