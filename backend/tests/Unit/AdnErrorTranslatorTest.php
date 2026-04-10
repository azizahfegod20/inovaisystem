<?php

namespace Tests\Unit;

use App\Services\Nfse\AdnErrorTranslator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AdnErrorTranslatorTest extends TestCase
{
    #[DataProvider('knownErrorCodes')]
    public function test_translates_known_error_codes(string $code, string $expectedFragment): void
    {
        $translated = AdnErrorTranslator::translate($code);

        $this->assertStringContainsString($expectedFragment, $translated);
        $this->assertNotEquals("Erro ADN: {$code}", $translated);
    }

    public static function knownErrorCodes(): array
    {
        return [
            'E1235 esquema XML' => ['E1235', 'esquema XML'],
            'E1236 assinatura' => ['E1236', 'Assinatura'],
            'E1237 certificado' => ['E1237', 'Certificado'],
            'E1238 CNPJ emitente' => ['E1238', 'CNPJ'],
            'E1239 DPS duplicado' => ['E1239', 'duplicado'],
            'E1240 município' => ['E1240', 'Município'],
            'E1241 serviço' => ['E1241', 'serviço'],
            'E1242 alíquota' => ['E1242', 'Alíquota'],
            'E1243 tomador' => ['E1243', 'Tomador'],
            'E1244 competência' => ['E1244', 'competência'],
            'E1245 valor' => ['E1245', 'Valor'],
            '403 não autorizado' => ['403', 'autorizado'],
            '409 duplicada' => ['409', 'duplicada'],
            '429 rate limit' => ['429', 'Limite'],
            '500 erro interno' => ['500', 'interno'],
            '502 indisponível' => ['502', 'indisponível'],
            '503 manutenção' => ['503', 'manutenção'],
        ];
    }

    public function test_unknown_code_returns_generic_message(): void
    {
        $translated = AdnErrorTranslator::translate('E9999');

        $this->assertEquals('Erro ADN: E9999', $translated);
    }

    public function test_translate_response_uses_error_code(): void
    {
        $result = AdnErrorTranslator::translateResponse([
            'error_code' => 'E1235',
            'message' => 'Schema validation failed',
        ]);

        $this->assertStringContainsString('esquema XML', $result);
    }

    public function test_translate_response_falls_back_to_message(): void
    {
        $result = AdnErrorTranslator::translateResponse([
            'error_code' => 'E9999',
            'message' => 'Algo inesperado aconteceu',
        ]);

        $this->assertEquals('Algo inesperado aconteceu', $result);
    }

    public function test_translate_response_with_empty_payload(): void
    {
        $result = AdnErrorTranslator::translateResponse([]);

        $this->assertEquals('Erro desconhecido no ADN.', $result);
    }
}
