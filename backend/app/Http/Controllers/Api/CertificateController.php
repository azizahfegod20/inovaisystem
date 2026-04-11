<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CertificateException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCertificateRequest;
use App\Models\Certificate;
use App\Models\Company;
use App\Services\Certificate\CertificateParser;
use App\Services\Certificate\CertificateStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function __construct(
        protected CertificateParser $parser,
        protected CertificateStorage $storage,
    ) {}

    public function store(StoreCertificateRequest $request): JsonResponse
    {
        $companyId = $request->get('current_company_id');
        $company = Company::findOrFail($companyId);

        $pfxContent = file_get_contents($request->file('pfx_file')->getRealPath());
        $password = $request->input('password');

        try {
            $parsed = $this->parser->parse($pfxContent, $password);
        } catch (CertificateException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if ($parsed['cnpj'] !== $company->cnpj) {
            return response()->json([
                'message' => 'CNPJ do certificado não confere com a empresa',
            ], 422);
        }

        if ($parsed['is_expired']) {
            return response()->json(['message' => 'Certificado expirado'], 422);
        }

        $certificate = $this->storage->store($company, $pfxContent, $password, $parsed);

        return response()->json([
            'id' => $certificate->id,
            'cnpj' => $certificate->cnpj,
            'common_name' => $certificate->common_name,
            'valid_from' => $certificate->valid_from->toDateString(),
            'valid_to' => $certificate->valid_to->toDateString(),
            'is_active' => $certificate->is_active,
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->get('current_company_id');

        $certificates = Certificate::where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Certificate $cert) => [
                'id' => $cert->id,
                'cnpj' => $cert->cnpj,
                'common_name' => $cert->common_name,
                'valid_from' => $cert->valid_from->toDateString(),
                'valid_to' => $cert->valid_to->toDateString(),
                'is_active' => $cert->is_active,
                'is_expired' => $cert->isExpired(),
                'is_expiring_soon' => $cert->isExpiringSoon(),
            ]);

        return response()->json($certificates);
    }

    public function destroy(Certificate $certificate, Request $request): JsonResponse
    {
        $companyId = $request->get('current_company_id');

        if ((int) $certificate->company_id !== (int) $companyId) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $certificate->delete();

        return response()->json(null, 204);
    }
}
