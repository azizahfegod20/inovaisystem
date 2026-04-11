<?php

namespace App\Services\Nfse;

use App\Enums\InvoiceStatus;
use App\Exceptions\NfseEmissionException;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Service;

class InvoiceReplacer
{
    public function __construct(
        protected InvoiceEmitter $emitter,
        protected InvoiceCanceller $canceller,
    ) {}

    public function replace(Invoice $original, array $data, int $userId): Invoice
    {
        if ($original->status !== InvoiceStatus::AUTHORIZED) {
            throw new NfseEmissionException(
                "Apenas notas autorizadas podem ser substituídas. Status atual: {$original->status->label()}",
                stage: 'invoice_validation',
                retryable: false
            );
        }

        $company = $original->company;
        $customer = Customer::findOrFail($data['customer_id']);
        $service = Service::findOrFail($data['service_id']);

        $data['invoice_replaced_id'] = $original->id;

        $newInvoice = $this->emitter->emit(
            $company,
            $customer,
            $service,
            $userId,
            $data,
            $original->chave_acesso,
        );

        $motivo = $data['motivo'] ?? 'Substituição de NFS-e';
        $this->canceller->cancel($original, $motivo, $userId);

        $original->update(['status' => InvoiceStatus::REPLACED->value]);

        return $newInvoice;
    }
}
