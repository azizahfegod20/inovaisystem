<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanySelected
{
    public function handle(Request $request, Closure $next): Response
    {
        $companyId = $request->session()->get('company_id');

        if (! $companyId) {
            return response()->json([
                'message' => 'Nenhuma empresa selecionada. Use POST /api/companies/{id}/select primeiro.',
            ], 403);
        }

        $user = $request->user();

        if (! $user || ! $user->companies()->where('companies.id', $companyId)->exists()) {
            $request->session()->forget('company_id');

            return response()->json([
                'message' => 'Você não tem acesso a esta empresa.',
            ], 403);
        }

        $request->merge(['current_company_id' => (int) $companyId]);

        return $next($request);
    }
}
