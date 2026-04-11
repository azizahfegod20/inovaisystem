<?php

namespace App\Services\Nfse;

use App\Exceptions\NfseEmissionException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class AdnClient
{
    protected int $timeout;

    protected int $retryTimes;

    protected int $retrySleepMs;

    protected int $circuitBreakerThreshold = 5;

    protected static int $consecutiveFailures = 0;

    public function __construct()
    {
        $this->timeout = config('nfse.adn_timeout', 30);
        $this->retryTimes = config('nfse.adn_retry_times', 3);
        $this->retrySleepMs = config('nfse.adn_retry_sleep_ms', 1000);
    }

    public function sendDps(string $xmlBase64Gzipped, string $certPemPath, string $keyPemPath): array
    {
        $this->checkCircuitBreaker();

        $baseUrl = $this->getBaseUrl();

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryTimes, $this->retrySleepMs, function ($exception) {
                    return $exception instanceof ConnectionException;
                })
                ->withOptions([
                    'cert' => $certPemPath,
                    'ssl_key' => $keyPemPath,
                    'verify' => true,
                ])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$baseUrl}/DPS", [
                    'dpsXmlGZipB64' => $xmlBase64Gzipped,
                ]);

            self::$consecutiveFailures = 0;

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => $response->status(),
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'status' => $response->status(),
                'data' => $response->json(),
                'error_code' => $response->json('codigoErro') ?? $response->json('error_code') ?? 'ADN_ERROR',
                'message' => $response->json('mensagem') ?? $response->json('message') ?? 'Erro na comunicação com ADN',
            ];
        } catch (ConnectionException $e) {
            self::$consecutiveFailures++;

            throw NfseEmissionException::adnTimeout($e->getMessage(), $e);
        } catch (\Exception $e) {
            self::$consecutiveFailures++;

            throw NfseEmissionException::adnTimeout($e->getMessage(), $e);
        }
    }

    public function getDanfse(string $chaveAcesso, string $certPemPath, string $keyPemPath): ?string
    {
        $baseUrl = $this->getBaseUrl();

        $response = Http::timeout($this->timeout)
            ->withOptions([
                'cert' => $certPemPath,
                'ssl_key' => $keyPemPath,
            ])
            ->get("{$baseUrl}/danfse/{$chaveAcesso}");

        if ($response->successful()) {
            return $response->body();
        }

        return null;
    }

    public function sendCancelamento(string $xmlBase64Gzipped, string $certPemPath, string $keyPemPath): array
    {
        $this->checkCircuitBreaker();
        $baseUrl = $this->getBaseUrl();

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryTimes, $this->retrySleepMs)
                ->withOptions([
                    'cert' => $certPemPath,
                    'ssl_key' => $keyPemPath,
                ])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$baseUrl}/Eventos", [
                    'pedidoEventoXmlGZipB64' => $xmlBase64Gzipped,
                ]);

            self::$consecutiveFailures = 0;

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            self::$consecutiveFailures++;
            throw NfseEmissionException::adnTimeout($e->getMessage(), $e);
        }
    }

    public function getParametrosMunicipais(string $codigoIbge, string $certPemPath, string $keyPemPath): ?array
    {
        $baseUrl = $this->getBaseUrl();

        $response = Http::timeout($this->timeout)
            ->withOptions([
                'cert' => $certPemPath,
                'ssl_key' => $keyPemPath,
            ])
            ->get("{$baseUrl}/parametros_municipais/{$codigoIbge}");

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    protected function getBaseUrl(): string
    {
        $ambiente = config('nfse.ambiente', 2);

        return config("nfse.adn_url.{$ambiente}");
    }

    protected function checkCircuitBreaker(): void
    {
        if (self::$consecutiveFailures >= $this->circuitBreakerThreshold) {
            throw new NfseEmissionException(
                'Circuit breaker ativado: muitas falhas consecutivas na comunicação com o ADN. Tente novamente em alguns minutos.',
                stage: 'circuit_breaker',
                retryable: true
            );
        }
    }

    public static function resetCircuitBreaker(): void
    {
        self::$consecutiveFailures = 0;
    }
}
