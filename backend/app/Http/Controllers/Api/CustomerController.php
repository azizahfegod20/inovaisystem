<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->get('current_company_id');

        $query = Customer::where('company_id', $companyId);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('razao_social', 'ilike', "%{$search}%")
                  ->orWhere('documento', 'like', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        $perPage = (int) $request->query('per_page', 10);

        return response()->json(
            $query->orderBy('razao_social')->paginate($perPage)
        );
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $companyId = $request->get('current_company_id');

        $customer = Customer::create(
            array_merge($request->validated(), ['company_id' => $companyId])
        );

        return response()->json($customer, 201);
    }

    public function show(Customer $customer, Request $request): JsonResponse
    {
        $companyId = $request->get('current_company_id');

        if ((int) $customer->company_id !== (int) $companyId) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        return response()->json($customer);
    }

    public function update(StoreCustomerRequest $request, Customer $customer): JsonResponse
    {
        $companyId = $request->get('current_company_id');

        if ((int) $customer->company_id !== (int) $companyId) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $customer->update($request->validated());

        return response()->json($customer);
    }

    public function destroy(Customer $customer, Request $request): JsonResponse
    {
        $companyId = $request->get('current_company_id');

        if ((int) $customer->company_id !== (int) $companyId) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        if ($customer->invoices()->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir tomador com notas fiscais vinculadas.',
            ], 422);
        }

        $customer->delete();

        return response()->json(null, 204);
    }
}
