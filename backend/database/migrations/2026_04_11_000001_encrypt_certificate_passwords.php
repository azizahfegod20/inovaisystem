<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $appKey = config('app.key');

        if (empty($appKey)) {
            throw new RuntimeException(
                'APP_KEY não está definida. Defina APP_KEY no .env antes de executar esta migration.'
            );
        }

        DB::table('certificates')
            ->whereNotNull('pfx_password')
            ->where('pfx_password', '!=', '')
            ->orderBy('id')
            ->chunk(100, function ($certificates) {
                foreach ($certificates as $cert) {
                    // Skip if already encrypted (Laravel encrypted strings are base64 with a specific prefix)
                    if ($this->isEncrypted($cert->pfx_password)) {
                        continue;
                    }

                    try {
                        $encrypted = Crypt::encryptString($cert->pfx_password);

                        DB::table('certificates')
                            ->where('id', $cert->id)
                            ->update(['pfx_password' => $encrypted]);
                    } catch (Throwable $e) {
                        Log::error('Falha ao criptografar senha do certificado', [
                            'certificate_id' => $cert->id,
                            'company_id' => $cert->company_id,
                            'error' => $e->getMessage(),
                        ]);

                        throw $e;
                    }
                }

                Log::info('Certificate passwords encryption progress', [
                    'last_id' => $certificates->last()->id,
                ]);
            });

        Log::info('Certificate passwords encryption completed');
    }

    public function down(): void
    {
        $appKey = config('app.key');

        if (empty($appKey)) {
            throw new RuntimeException(
                'APP_KEY não está definida. Não é possível descriptografar sem a APP_KEY original.'
            );
        }

        DB::table('certificates')
            ->whereNotNull('pfx_password')
            ->where('pfx_password', '!=', '')
            ->orderBy('id')
            ->chunk(100, function ($certificates) {
                foreach ($certificates as $cert) {
                    if (! $this->isEncrypted($cert->pfx_password)) {
                        continue;
                    }

                    try {
                        $decrypted = Crypt::decryptString($cert->pfx_password);

                        DB::table('certificates')
                            ->where('id', $cert->id)
                            ->update(['pfx_password' => $decrypted]);
                    } catch (Throwable $e) {
                        Log::error('Falha ao descriptografar senha do certificado no rollback', [
                            'certificate_id' => $cert->id,
                            'error' => $e->getMessage(),
                        ]);

                        throw $e;
                    }
                }
            });

        Log::info('Certificate passwords decryption completed (rollback)');
    }

    protected function isEncrypted(string $value): bool
    {
        $decoded = base64_decode($value, true);

        if ($decoded === false) {
            return false;
        }

        // Laravel Crypt uses AES-256-CBC with a specific serialization format
        return str_contains($decoded, 's:');
    }
};
