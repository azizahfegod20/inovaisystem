<?php

namespace App\Services\Nfse;

class AdnErrorTranslator
{
    private static array $errors = [
        'E1235' => 'Falha no esquema XML da DPS.',
        'E1236' => 'Assinatura digital inválida.',
        'E1237' => 'Certificado digital não autorizado.',
        'E1238' => 'CNPJ do emitente não confere com o certificado.',
        'E1239' => 'Identificador DPS duplicado.',
        'E1240' => 'Município não aderente ao Padrão Nacional.',
        'E1241' => 'Código de serviço (LC 116) inválido.',
        'E1242' => 'Alíquota ISS fora da faixa permitida pelo município.',
        'E1243' => 'Tomador com CPF/CNPJ inválido.',
        'E1244' => 'Data de competência inválida.',
        'E1245' => 'Valor do serviço deve ser maior que zero.',
        '403' => 'Certificado não autorizado para este ambiente.',
        '409' => 'Operação duplicada (idempotência).',
        '429' => 'Limite de requisições excedido. Aguarde.',
        '500' => 'Erro interno no servidor do ADN.',
        '502' => 'ADN indisponível. Tente novamente.',
        '503' => 'ADN em manutenção. Tente novamente mais tarde.',
    ];

    public static function translate(string $errorCode): string
    {
        return self::$errors[$errorCode] ?? "Erro ADN: {$errorCode}";
    }

    public static function translateResponse(array $response): string
    {
        $code = $response['error_code'] ?? $response['code'] ?? '';
        $message = $response['message'] ?? '';

        $translated = self::translate((string) $code);

        if ($translated !== "Erro ADN: {$code}") {
            return $translated;
        }

        return $message ?: "Erro desconhecido no ADN.";
    }
}
