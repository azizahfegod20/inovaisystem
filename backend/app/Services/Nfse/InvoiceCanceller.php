<?php

namespace App\Services\Nfse;

use App\Enums\InvoiceStatus;
use App\Exceptions\CertificateStorageException;
use App\Exceptions\NfseEmissionException;
use App\Models\Invoice;
use App\Services\Certificate\CertificateStorage;
use App\Services\Storage\MinioService;

class InvoiceCanceller
{
    public function __construct(
        protected XmlSigner $xmlSigner,
        protected AdnClient $adnClient,
        protected CertificateStorage $certStorage,
        protected MinioService $minioService,
    ) {}

    public function cancel(Invoice $invoice, string $motivo, int $userId): Invoice
    {
        if ($invoice->status !== InvoiceStatus::AUTHORIZED) {
            if ($invoice->status === InvoiceStatus::CANCELLED) {
                throw new NfseEmissionException(
                    'Nota já cancelada',
                    stage: 'cancel_validation',
                    retryable: false
                );
            }
            throw new NfseEmissionException(
                "Apenas notas autorizadas podem ser canceladas. Status atual: {$invoice->status->label()}",
                stage: 'cancel_validation',
                retryable: false
            );
        }

        $company = $invoice->company;
        $certificate = $this->certStorage->getActiveCertificate($company);

        if (! $certificate) {
            throw CertificateStorageException::notFound($company->id);
        }

        $pemFiles = $this->certStorage->extractPemFiles($certificate);

        $cancelXml = $this->buildCancelXml($invoice, $motivo);
        $signedXml = $this->xmlSigner->sign($cancelXml, $pemFiles['cert_pem'], $pemFiles['key_pem']);

        $gzipped = gzencode($signedXml);
        $base64 = base64_encode($gzipped);

        $certTmpPath = tempnam(sys_get_temp_dir(), 'cert_');
        $keyTmpPath = tempnam(sys_get_temp_dir(), 'key_');
        file_put_contents($certTmpPath, $pemFiles['cert_pem']);
        file_put_contents($keyTmpPath, $pemFiles['key_pem']);

        try {
            $response = $this->adnClient->sendCancelamento($base64, $certTmpPath, $keyTmpPath);
        } finally {
            @unlink($certTmpPath);
            @unlink($keyTmpPath);
        }

        if (! empty($response['data']['xml'])) {
            $xmlPath = "companies/{$company->id}/invoices/{$invoice->id_dps}/cancel_response.xml";
            $this->minioService->upload($xmlPath, $response['data']['xml']);
        }

        $invoice->update([
            'status' => InvoiceStatus::CANCELLED->value,
            'data_cancelamento' => now(),
            'motivo_cancelamento' => $motivo,
        ]);

        return $invoice->fresh();
    }

    protected function buildCancelXml(Invoice $invoice, string $motivo): string
    {
        $ns = 'http://www.sped.fazenda.gov.br/nfse';

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = false;

        $pedido = $dom->createElementNS($ns, 'pedRegEvento');
        $pedido->setAttribute('versao', '1.00.02');
        $dom->appendChild($pedido);

        $infPedReg = $dom->createElementNS($ns, 'infPedReg');
        $infPedReg->setAttribute('Id', 'CANC_'.$invoice->id_dps);
        $pedido->appendChild($infPedReg);

        $tpEvento = $dom->createElementNS($ns, 'tpEvento', 'e101101');
        $infPedReg->appendChild($tpEvento);

        $chNFSe = $dom->createElementNS($ns, 'chNFSe', $invoice->chave_acesso);
        $infPedReg->appendChild($chNFSe);

        $nPedRegEvento = $dom->createElementNS($ns, 'nPedRegEvento', '1');
        $infPedReg->appendChild($nPedRegEvento);

        $detEvento = $dom->createElementNS($ns, 'detEvento');
        $infPedReg->appendChild($detEvento);

        $descEvento = $dom->createElementNS($ns, 'descEvento', 'Cancelamento de NFS-e');
        $detEvento->appendChild($descEvento);

        $motCanc = $dom->createElementNS($ns, 'motCanc', $motivo);
        $detEvento->appendChild($motCanc);

        return $dom->saveXML();
    }
}
