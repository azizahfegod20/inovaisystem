<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->get('current_company_id');

        $query = Service::where('company_id', $companyId);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('descricao', 'ilike', "%{$search}%")
                  ->orWhere('codigo_lc116', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('favorites_only')) {
            $query->where('is_favorite', true);
        }

        return response()->json(
            $query->orderBy('descricao')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'codigo_lc116' => ['required', 'string', 'max:10'],
            'codigo_nbs' => ['nullable', 'string', 'max:10'],
            'descricao' => ['required', 'string', 'max:500'],
            'aliquota_iss' => ['required', 'numeric', 'min:0', 'max:1'],
            'is_favorite' => ['sometimes', 'boolean'],
        ]);

        $companyId = $request->get('current_company_id');

        $service = Service::create(
            array_merge($validated, ['company_id' => $companyId])
        );

        return response()->json($service, 201);
    }

    public function show(Service $service, Request $request): JsonResponse
    {
        $companyId = $request->get('current_company_id');

        if ((int) $service->company_id !== (int) $companyId) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        return response()->json($service);
    }

    public function update(Service $service, Request $request): JsonResponse
    {
        $companyId = $request->get('current_company_id');

        if ((int) $service->company_id !== (int) $companyId) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $validated = $request->validate([
            'codigo_lc116' => ['sometimes', 'string', 'max:10'],
            'codigo_nbs' => ['nullable', 'string', 'max:10'],
            'descricao' => ['sometimes', 'string', 'max:500'],
            'aliquota_iss' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'is_favorite' => ['sometimes', 'boolean'],
        ]);

        $service->update($validated);

        return response()->json($service);
    }

    public function destroy(Service $service, Request $request): JsonResponse
    {
        $companyId = $request->get('current_company_id');

        if ((int) $service->company_id !== (int) $companyId) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $service->delete();

        return response()->json(null, 204);
    }
}
