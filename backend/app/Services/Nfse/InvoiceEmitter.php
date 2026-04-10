<?php

namespace App\Services\Nfse;

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Service;
use App\Services\Certificate\CertificateStorage;
use App\Services\Municipal\ParameterService;
use App\Services\Storage\MinioService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InvoiceEmitter
{
    public function __construct(
        protected DpsBuilder $dpsBuilder,
        protected XsdValidator $xsdValidator,
        protected XmlSigner $xmlSigner,
        protected AdnClient $adnClient,
        protected CertificateStorage $certStorage,
        protected MinioService $minioService,
        protected ParameterService $parameterService,
    ) {}

    public function emit(
        Company $company,
        Customer $customer,
        Service $service,
        int $userId,
        array $invoiceData,
        ?string $chaveSubstituida = null,
    ): Invoice {
        $municipalParams = $this->parameterService->getByIbge($company->codigo_ibge);
        if (! ($municipalParams['aderente_padrao_nacional'] ?? false)) {
            throw new RuntimeException(
                'O município (IBGE: ' . $company->codigo_ibge . ') não aderiu ao Padrão Nacional de NFS-e. '
                . 'A emissão não é possível para este município.'
            );
        }

        $certificate = $this->certStorage->getActiveCertificate($company);

        if (! $certificate) {
            throw new RuntimeException('Nenhum certificado digital ativo encontrado para esta empresa.');
        }

        $pemFiles = $this->certStorage->extractPemFiles($certificate);

        return DB::transaction(function () use ($company, $customer, $service, $userId, $invoiceData, $chaveSubstituida, $pemFiles) {
            $dpsNumber = $this->getNextDpsNumber($company);
            $idDps = $this->generateIdDps($company, $dpsNumber);

            $existing = Invoice::where('id_dps', $idDps)->first();
            if ($existing) {
                throw new IdempotencyException($existing);
            }

            $valorServico = (float) $invoiceData['valor_servico'];
            $valorDeducoes = (float) ($invoiceData['valor_deducoes'] ?? 0);
            $valorDesconto = (float) ($invoiceData['valor_desconto'] ?? 0);
            $aliquotaIss = (float) ($invoiceData['aliquota_iss'] ?? $service->aliquota_iss);

            $baseCalculo = $valorServico - $valorDeducoes - $valorDesconto;
            $valorIss = round($baseCalculo * $aliquotaIss, 2);
            $valorLiquido = $valorServico - $valorDesconto;

            $dataEmissao = now()->toIso8601String();

            $dpsData = [
                'id_dps' => $idDps,
                'ambiente' => $company->ambiente,
                'data_emissao' => $dataEmissao,
                'serie' => $company->dps_serie,
                'numero_dps' => (string) $dpsNumber,
                'emitente' => [
                    'cnpj' => $company->cnpj,
                    'razao_social' => $company->razao_social,
                    'nome_fantasia' => $company->nome_fantasia,
                    'inscricao_municipal' => $company->inscricao_municipal,
                    'regime_tributario' => $company->regime_tributario,
                    'reg_esp_trib' => $company->reg_esp_trib,
                    'endereco' => [
                        'codigo_ibge' => $company->codigo_ibge,
                        'cep' => $company->cep,
                        'logradouro' => $company->logradouro,
                        'numero' => $company->numero,
                        'complemento' => $company->complemento,
                        'bairro' => $company->bairro,
                    ],
                    'telefone' => $company->telefone,
                    'email' => $company->email,
                ],
                'tomador' => [
                    'tipo_documento' => $customer->tipo_documento,
                    'documento' => $customer->documento,
                    'razao_social' => $customer->razao_social,
                    'inscricao_municipal' => $customer->inscricao_municipal,
                    'endereco' => [
                        'codigo_ibge' => $customer->codigo_ibge,
                        'cep' => $customer->cep,
                        'logradouro' => $customer->logradouro,
                        'numero' => $customer->numero,
                        'complemento' => $customer->complemento,
                        'bairro' => $customer->bairro,
                    ],
                    'telefone' => $customer->telefone,
                    'email' => $customer->email,
                ],
                'servico' => [
                    'codigo_lc116' => $service->codigo_lc116,
                    'codigo_nbs' => $service->codigo_nbs,
                    'descricao' => $invoiceData['descricao_servico'],
                ],
                'valores' => [
                    'valor_servico' => $valorServico,
                    'valor_deducoes' => $valorDeducoes,
                    'valor_desconto' => $valorDesconto,
                    'aliquota_iss' => $aliquotaIss,
                    'valor_iss' => $valorIss,
                    'iss_retido' => $invoiceData['iss_retido'] ?? false,
                    'valor_ir' => $invoiceData['valor_ir'] ?? 0,
                    'valor_csll' => $invoiceData['valor_csll'] ?? 0,
                    'valor_cofins' => $invoiceData['valor_cofins'] ?? 0,
                    'valor_pis' => $invoiceData['valor_pis'] ?? 0,
                    'valor_inss' => $invoiceData['valor_inss'] ?? 0,
                ],
                'chave_substituida' => $chaveSubstituida,
            ];

            $xml = $this->dpsBuilder->build($dpsData);

            $validationResult = $this->xsdValidator->validate($xml);
            if (! $validationResult->isValid()) {
                throw new RuntimeException(
                    'XML inválido: ' . implode('; ', $validationResult->getErrors())
                );
            }

            $signedXml = $this->xmlSigner->sign($xml, $pemFiles['cert_pem'], $pemFiles['key_pem']);

            $gzipped = gzencode($signedXml);
            $base64 = base64_encode($gzipped);

            $certTmpPath = tempnam(sys_get_temp_dir(), 'cert_');
            $keyTmpPath = tempnam(sys_get_temp_dir(), 'key_');
            file_put_contents($certTmpPath, $pemFiles['cert_pem']);
            file_put_contents($keyTmpPath, $pemFiles['key_pem']);

            try {
                $adnResponse = $this->adnClient->sendDps($base64, $certTmpPath, $keyTmpPath);
            } finally {
                @unlink($certTmpPath);
                @unlink($keyTmpPath);
            }

            $xmlSentPath = "companies/{$company->id}/invoices/{$idDps}/dps_sent.xml";
            $this->minioService->upload($xmlSentPath, $signedXml);

            $xmlResponsePath = null;
            $pdfPath = null;
            $status = InvoiceStatus::PENDING;
            $chaveAcesso = null;
            $numeroNfse = null;

            if ($adnResponse['success']) {
                $status = InvoiceStatus::AUTHORIZED;
                $chaveAcesso = $adnResponse['data']['chaveAcesso'] ?? null;
                $numeroNfse = $adnResponse['data']['numeroNfse'] ?? null;

                if (! empty($adnResponse['data']['xml'])) {
                    $xmlResponsePath = "companies/{$company->id}/invoices/{$idDps}/nfse_response.xml";
                    $this->minioService->upload($xmlResponsePath, $adnResponse['data']['xml']);
                }
            } else {
                $errorCode = $adnResponse['error_code'] ?? 'ADN_ERROR';
                $errorMsg = $adnResponse['message'] ?? 'Erro desconhecido';

                throw new AdnRejectedException($errorCode, $errorMsg);
            }

            $company->update(['dps_next_number' => $dpsNumber + 1]);

            return Invoice::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'user_id' => $userId,
                'status' => $status->value,
                'id_dps' => $idDps,
                'dps_number' => $dpsNumber,
                'dps_serie' => $company->dps_serie,
                'chave_acesso' => $chaveAcesso,
                'numero_nfse' => $numeroNfse,
                'valor_servico' => $valorServico,
                'valor_deducoes' => $valorDeducoes,
                'valor_desconto' => $valorDesconto,
                'valor_liquido' => $valorLiquido,
                'aliquota_iss' => $aliquotaIss,
                'valor_iss' => $valorIss,
                'iss_retido' => $invoiceData['iss_retido'] ?? false,
                'valor_ir' => $invoiceData['valor_ir'] ?? 0,
                'valor_csll' => $invoiceData['valor_csll'] ?? 0,
                'valor_cofins' => $invoiceData['valor_cofins'] ?? 0,
                'valor_pis' => $invoiceData['valor_pis'] ?? 0,
                'valor_inss' => $invoiceData['valor_inss'] ?? 0,
                'descricao_servico' => $invoiceData['descricao_servico'],
                'xml_sent_path' => $xmlSentPath,
                'xml_response_path' => $xmlResponsePath,
                'pdf_path' => $pdfPath,
                'data_emissao' => now(),
                'invoice_replaced_id' => $invoiceData['invoice_replaced_id'] ?? null,
            ]);
        });
    }

    protected function getNextDpsNumber(Company $company): int
    {
        $row = DB::table('companies')
            ->where('id', $company->id)
            ->lockForUpdate()
            ->first(['dps_next_number']);

        return (int) $row->dps_next_number;
    }

    protected function generateIdDps(Company $company, int $dpsNumber): string
    {
        $codMunicipio = str_pad($company->codigo_ibge, 7, '0', STR_PAD_LEFT);
        $tipoInscricao = '2';
        $inscricaoFederal = str_pad($company->cnpj, 14, '0', STR_PAD_LEFT);
        $serie = str_pad($company->dps_serie, 5, '0', STR_PAD_LEFT);
        $numDps = str_pad((string) $dpsNumber, 15, '0', STR_PAD_LEFT);

        return $codMunicipio . $tipoInscricao . $inscricaoFederal . $serie . $numDps;
    }
}

class IdempotencyException extends RuntimeException
{
    public Invoice $existingInvoice;

    public function __construct(Invoice $invoice)
    {
        $this->existingInvoice = $invoice;
        parent::__construct('DPS já emitida (idempotência)');
    }
}

class AdnRejectedException extends RuntimeException
{
    public string $errorCode;

    public function __construct(string $errorCode, string $message)
    {
        $this->errorCode = $errorCode;
        parent::__construct($message);
    }
}
