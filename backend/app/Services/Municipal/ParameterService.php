<?php

namespace App\Services\Municipal;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ParameterService
{
    public function getByIbge(string $codigoIbge): array
    {
        $cacheKey = "municipal_params:{$codigoIbge}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($codigoIbge) {
            return $this->fetchFromAdn($codigoIbge);
        });
    }

    public function invalidateCache(string $codigoIbge): void
    {
        Cache::forget("municipal_params:{$codigoIbge}");
    }

    private function fetchFromAdn(string $codigoIbge): array
    {
        $baseUrl = config('nfse.adn_url');

        $response = Http::timeout(10)
            ->retry(2, 500)
            ->get("{$baseUrl}/parametros_municipais/{$codigoIbge}");

        if ($response->failed()) {
            return $this->defaultParams($codigoIbge);
        }

        $data = $response->json();

        return [
            'codigo_ibge' => $codigoIbge,
            'municipio' => $data['municipio'] ?? '',
            'uf' => $data['uf'] ?? '',
            'aderente_padrao_nacional' => $data['aderente'] ?? false,
            'aliquota_iss_minima' => $data['aliquota_minima'] ?? 0.02,
            'aliquota_iss_maxima' => $data['aliquota_maxima'] ?? 0.05,
            'cached_at' => now()->toISOString(),
            'expires_at' => now()->addHours(24)->toISOString(),
        ];
    }

    private function defaultParams(string $codigoIbge): array
    {
        return [
            'codigo_ibge' => $codigoIbge,
            'municipio' => '',
            'uf' => '',
            'aderente_padrao_nacional' => false,
            'aliquota_iss_minima' => 0.02,
            'aliquota_iss_maxima' => 0.05,
            'cached_at' => now()->toISOString(),
            'expires_at' => now()->addHours(24)->toISOString(),
        ];
    }
}
