<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelInvoiceRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Service;
use App\Services\Nfse\AdnRejectedException;
use App\Services\Nfse\IdempotencyException;
use App\Services\Nfse\InvoiceCanceller;
use App\Services\Nfse\InvoiceEmitter;
use App\Services\Nfse\InvoiceReplacer;
use App\Services\Storage\MinioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceEmitter $emitter,
        protected MinioService $minioService,
    ) {}

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $companyId = $request->get('current_company_id');
        $company = Company::findOrFail($companyId);
        $customer = Customer::findOrFail($request->input('customer_id'));
        $service = Service::findOrFail($request->input('service_id'));

        if ((int) $customer->company_id !== (int) $companyId || (int) $service->company_id !== (int) $companyId) {
            return response()->json(['message' => 'Tomador ou serviço não pertence a esta empresa.'], 403);
        }

        try {
            $invoice = $this->emitter->emit(
                $company,
                $customer,
                $service,
                $request->user()->id,
                $request->validated(),
            );

            return response()->json([
                'id' => $invoice->id,
                'id_dps' => $invoice->id_dps,
                'dps_number' => $invoice->dps_number,
                'dps_serie' => $invoice->dps_serie,
                'chave_acesso' => $invoice->chave_acesso,
                'numero_nfse' => $invoice->numero_nfse,
                'status' => $invoice->status->value,
                'valor_servico' => $invoice->valor_servico,
                'valor_iss' => $invoice->valor_iss,
                'valor_liquido' => $invoice->valor_liquido,
                'data_emissao' => $invoice->data_emissao->toIso8601String(),
                'pdf_url' => "/api/invoices/{$invoice->id}/pdf",
            ], 201);
        } catch (IdempotencyException $e) {
            return response()->json([
                'message' => 'DPS já emitida (idempotência)',
                'invoice_id' => $e->existingInvoice->id,
            ], 409);
        } catch (AdnRejectedException $e) {
            return response()->json([
                'message' => "Rejeição ADN: {$e->errorCode} — {$e->getMessage()}",
                'error_code' => $e->errorCode,
            ], 422);
        } catch (RuntimeException $e) {
            if (str_contains($e->getMessage(), 'Timeout') || str_contains($e->getMessage(), 'Circuit breaker')) {
                return response()->json([
                    'message' => 'Erro de comunicação com o ADN',
                    'error_code' => 'ADN_TIMEOUT',
                ], 502);
            }

            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->get('current_company_id');

        $query = Invoice::where('company_id', $companyId)
            ->with(['customer:id,razao_social,documento', 'service:id,descricao,codigo_lc116']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($customerId = $request->query('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('data_emissao', '>=', $dateFrom);
        }

        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('data_emissao', '<=', $dateTo);
        }

        if ($search = $request->query('search')) {
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('razao_social', 'ilike', "%{$search}%");
            });
        }

        $perPage = (int) $request->query('per_page', 10);

        return response()->json(
            $query->orderByDesc('data_emissao')->paginate($perPage)
        );
    }

    public function show(Invoice $invoice, Request $request): JsonResponse
    {
        $companyId = $request->get('current_company_id');

        if ((int) $invoice->company_id !== (int) $companyId) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $invoice->load(['customer', 'service', 'user:id,name,email', 'replacedInvoice:id,id_dps,chave_acesso']);

        return response()->json($invoice);
    }

    public function pdf(Invoice $invoice, Request $request): Response|JsonResponse
    {
        $companyId = $request->get('current_company_id');

        if ((int) $invoice->company_id !== (int) $companyId) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        if ($invoice->pdf_path && $this->minioService->exists($invoice->pdf_path)) {
            $content = $this->minioService->download($invoice->pdf_path);

            return response($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"DANFSe-{$invoice->chave_acesso}.pdf\"",
            ]);
        }

        if (! $invoice->chave_acesso) {
            return response()->json(['message' => 'PDF não disponível. Nota sem chave de acesso.'], 404);
        }

        return response()->json(['message' => 'PDF ainda não disponível.'], 404);
    }

    public function xml(Invoice $invoice, Request $request): Response|JsonResponse
    {
        $companyId = $request->get('current_company_id');

        if ((int) $invoice->company_id !== (int) $companyId) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        if (! $invoice->xml_sent_path) {
            return response()->json(['message' => 'XML não disponível.'], 404);
        }

        $content = $this->minioService->download($invoice->xml_sent_path);

        if (! $content) {
            return response()->json(['message' => 'XML não encontrado no storage.'], 404);
        }

        return response($content, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => "attachment; filename=\"DPS-{$invoice->id_dps}.xml\"",
        ]);
    }

    public function cancel(CancelInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        $companyId = $request->get('current_company_id');

        if ((int) $invoice->company_id !== (int) $companyId) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $canceller = app(InvoiceCanceller::class);

        try {
            $invoice = $canceller->cancel($invoice, $request->input('motivo'), $request->user()->id);

            return response()->json([
                'id' => $invoice->id,
                'status' => $invoice->status->value,
                'data_cancelamento' => $invoice->data_cancelamento->toIso8601String(),
                'motivo_cancelamento' => $invoice->motivo_cancelamento,
            ]);
        } catch (RuntimeException $e) {
            $statusCode = str_contains($e->getMessage(), 'já cancelada') ? 409 : 422;

            return response()->json(['message' => $e->getMessage()], $statusCode);
        }
    }

    public function replace(StoreInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        $companyId = $request->get('current_company_id');

        if ((int) $invoice->company_id !== (int) $companyId) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $replacer = app(InvoiceReplacer::class);

        try {
            $validated = $request->validated();
            $validated['motivo'] = $request->input('motivo', 'Substituição de NFS-e');

            $newInvoice = $replacer->replace($invoice, $validated, $request->user()->id);

            return response()->json([
                'id' => $newInvoice->id,
                'id_dps' => $newInvoice->id_dps,
                'chave_acesso' => $newInvoice->chave_acesso,
                'status' => $newInvoice->status->value,
                'replaced_invoice_id' => $invoice->id,
            ], 201);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
