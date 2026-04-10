<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ambiente NFS-e (ADN - Padrão Nacional)
    |--------------------------------------------------------------------------
    | 1 = Produção, 2 = Homologação (Produção Restrita)
    */

    'ambiente' => (int) env('NFSE_AMBIENTE', 2),

    /*
    |--------------------------------------------------------------------------
    | URLs do ADN (Ambiente de Dados Nacional)
    |--------------------------------------------------------------------------
    */

    'adn_url' => [
        1 => 'https://adn.nfse.gov.br',
        2 => 'https://adn.producaorestrita.nfse.gov.br',
    ],

    /*
    |--------------------------------------------------------------------------
    | XSD para validação do XML da DPS
    |--------------------------------------------------------------------------
    */

    'xsd_path' => env('NFSE_XSD_PATH', 'resources/xsd/nfse_v1.00.02.xsd'),

    /*
    |--------------------------------------------------------------------------
    | Série da DPS
    |--------------------------------------------------------------------------
    | Faixa 00001-49999 para aplicativo próprio (Padrão Nacional)
    */

    'dps_serie_min' => 1,
    'dps_serie_max' => 49999,
    'dps_serie_default' => '00001',

    /*
    |--------------------------------------------------------------------------
    | Timeouts para comunicação com ADN
    |--------------------------------------------------------------------------
    */

    'adn_timeout' => (int) env('NFSE_ADN_TIMEOUT', 30),
    'adn_retry_times' => (int) env('NFSE_ADN_RETRY_TIMES', 3),
    'adn_retry_sleep_ms' => (int) env('NFSE_ADN_RETRY_SLEEP_MS', 1000),

    /*
    |--------------------------------------------------------------------------
    | Cache de parâmetros municipais
    |--------------------------------------------------------------------------
    */

    'municipal_cache_ttl' => (int) env('NFSE_MUNICIPAL_CACHE_TTL', 86400), // 24h

    /*
    |--------------------------------------------------------------------------
    | Cache de consulta CNPJ (publica.cnpj.ws)
    |--------------------------------------------------------------------------
    */

    'cnpj_cache_ttl' => (int) env('NFSE_CNPJ_CACHE_TTL', 604800), // 7 dias

];
