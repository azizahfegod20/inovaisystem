<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $companyId = $request->get('current_company_id');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = Invoice::where('company_id', $companyId);

        if ($dateFrom) {
            $query->whereDate('data_emissao', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('data_emissao', '<=', $dateTo);
        }

        $stats = $query->selectRaw("
            COUNT(*) as total_notas,
            COALESCE(SUM(CASE WHEN status = 'authorized' THEN valor_servico ELSE 0 END), 0) as total_receita,
            COALESCE(SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END), 0) as total_canceladas,
            COALESCE(SUM(CASE WHEN status = 'authorized' THEN valor_iss ELSE 0 END), 0) as total_iss,
            COALESCE(SUM(CASE WHEN status = 'authorized' THEN valor_ir + valor_csll + valor_cofins + valor_pis + valor_inss ELSE 0 END), 0) as total_retencoes
        ")->first();

        return response()->json([
            'total_notas' => (int) $stats->total_notas,
            'total_receita' => (float) $stats->total_receita,
            'total_canceladas' => (int) $stats->total_canceladas,
            'total_iss' => (float) $stats->total_iss,
            'total_retencoes' => (float) $stats->total_retencoes,
        ]);
    }

    public function chart(Request $request): JsonResponse
    {
        $companyId = $request->get('current_company_id');
        $dateFrom = $request->query('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());
        $period = $request->query('period', 'daily');

        $dateFormat = match ($period) {
            'weekly' => "TO_CHAR(data_emissao, 'IYYY-IW')",
            'monthly' => "TO_CHAR(data_emissao, 'YYYY-MM')",
            default => "TO_CHAR(data_emissao, 'YYYY-MM-DD')",
        };

        $results = Invoice::where('company_id', $companyId)
            ->where('status', 'authorized')
            ->whereDate('data_emissao', '>=', $dateFrom)
            ->whereDate('data_emissao', '<=', $dateTo)
            ->selectRaw("{$dateFormat} as label, SUM(valor_servico) as receita, COUNT(*) as notas")
            ->groupByRaw($dateFormat)
            ->orderByRaw($dateFormat)
            ->get();

        return response()->json([
            'labels' => $results->pluck('label'),
            'datasets' => [
                'receita' => $results->pluck('receita')->map(fn ($v) => (float) $v),
                'notas' => $results->pluck('notas')->map(fn ($v) => (int) $v),
            ],
        ]);
    }
}
