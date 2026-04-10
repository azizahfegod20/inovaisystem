<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Municipal\ParameterService;
use Illuminate\Http\JsonResponse;

class MunicipalParamsController extends Controller
{
    public function __invoke(string $codigoIbge, ParameterService $service): JsonResponse
    {
        if (! preg_match('/^\d{7}$/', $codigoIbge)) {
            return response()->json(['message' => 'Código IBGE inválido. Deve ter 7 dígitos.'], 422);
        }

        $params = $service->getByIbge($codigoIbge);

        return response()->json($params);
    }
}
