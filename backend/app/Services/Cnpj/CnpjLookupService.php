<?php

namespace App\Services\Cnpj;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CnpjLookupService
{
    protected string $baseUrl = 'https://publica.cnpj.ws/cnpj';

    public function lookup(string $cnpj): array
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        $cacheKey = "cnpj_lookup:{$cnpj}";
        $ttl = config('nfse.cnpj_cache_ttl', 604800);

        return Cache::remember($cacheKey, $ttl, function () use ($cnpj) {
            return $this->fetchFromApi($cnpj);
        });
    }

    protected function fetchFromApi(string $cnpj): array
    {
        $response = Http::timeout(10)
            ->accept('application/json')
            ->get("{$this->baseUrl}/{$cnpj}");

        if ($response->status() === 404) {
            throw new \App\Exceptions\CnpjNotFoundException($cnpj);
        }

        if ($response->status() === 429) {
            throw new \App\Exceptions\CnpjRateLimitException();
        }

        $response->throw();

        $data = $response->json();
        $estabelecimento = $data['estabelecimento'] ?? [];

        $telefone = null;
        if (! empty($estabelecimento['ddd1']) && ! empty($estabelecimento['telefone1'])) {
            $telefone = $estabelecimento['ddd1'] . $estabelecimento['telefone1'];
        }

        $logradouro = trim(
            ($estabelecimento['tipo_logradouro'] ?? '') . ' ' . ($estabelecimento['logradouro'] ?? '')
        );

        return [
            'cnpj' => $cnpj,
            'razao_social' => $data['razao_social'] ?? null,
            'nome_fantasia' => $estabelecimento['nome_fantasia'] ?? null,
            'logradouro' => $logradouro ?: null,
            'numero' => $estabelecimento['numero'] ?? null,
            'complemento' => $estabelecimento['complemento'] ?? null,
            'bairro' => $estabelecimento['bairro'] ?? null,
            'cep' => $estabelecimento['cep'] ?? null,
            'codigo_ibge' => $estabelecimento['cidade']['ibge_id'] ?? null,
            'uf' => $estabelecimento['estado']['sigla'] ?? null,
            'email' => $estabelecimento['email'] ?? null,
            'telefone' => $telefone,
            'situacao_cadastral' => $estabelecimento['situacao_cadastral'] ?? null,
            'natureza_juridica' => $data['natureza_juridica']['descricao'] ?? null,
            'porte' => $data['porte']['descricao'] ?? null,
            'simples_nacional' => ! empty($data['simples']),
            'mei' => ! empty($data['simei']),
            'source' => 'cnpj.ws',
            'cached_at' => now()->toIso8601String(),
        ];
    }
}
