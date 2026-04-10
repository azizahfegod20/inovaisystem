<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CnpjNotFoundException;
use App\Exceptions\CnpjRateLimitException;
use App\Http\Controllers\Controller;
use App\Services\Cnpj\CnpjLookupService;
use Illuminate\Http\JsonResponse;

class CnpjLookupController extends Controller
{
    public function __construct(
        protected CnpjLookupService $cnpjLookupService,
    ) {}

    public function __invoke(string $cnpj): JsonResponse
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        if (! preg_match('/^[0-9]{14}$/', $cnpj)) {
            return response()->json(['message' => 'CNPJ inválido'], 422);
        }

        try {
            $data = $this->cnpjLookupService->lookup($cnpj);

            return response()->json($data);
        } catch (CnpjNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (CnpjRateLimitException $e) {
            return response()->json(['message' => $e->getMessage()], 429);
        }
    }
}
