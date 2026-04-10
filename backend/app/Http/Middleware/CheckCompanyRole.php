<?php

namespace App\Http\Middleware;

use App\Enums\CompanyRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCompanyRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $companyId = $request->get('current_company_id')
            ?? $request->session()->get('company_id');

        if (! $companyId) {
            return response()->json([
                'message' => 'Nenhuma empresa selecionada.',
            ], 403);
        }

        $user = $request->user();
        $pivot = $user->companies()->where('companies.id', $companyId)->first()?->pivot;

        if (! $pivot) {
            return response()->json([
                'message' => 'Você não tem acesso a esta empresa.',
            ], 403);
        }

        $userRole = $pivot->role instanceof CompanyRole
            ? $pivot->role->value
            : $pivot->role;

        $allowedRoles = array_map(fn ($r) => trim($r), $roles);

        if (! in_array($userRole, $allowedRoles)) {
            return response()->json([
                'message' => 'Permissão insuficiente. Requer: ' . implode(', ', $allowedRoles),
            ], 403);
        }

        return $next($request);
    }
}
