<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('id_dps', 42)->unique();
            $table->bigInteger('dps_number');
            $table->string('dps_serie', 5);
            $table->string('chave_acesso', 50)->nullable();
            $table->bigInteger('numero_nfse')->nullable();
            $table->decimal('valor_servico', 15, 2);
            $table->decimal('valor_deducoes', 15, 2)->default(0);
            $table->decimal('valor_desconto', 15, 2)->default(0);
            $table->decimal('valor_liquido', 15, 2);
            $table->decimal('aliquota_iss', 5, 4);
            $table->decimal('valor_iss', 15, 2);
            $table->boolean('iss_retido')->default(false);
            $table->decimal('valor_ir', 15, 2)->default(0);
            $table->decimal('valor_csll', 15, 2)->default(0);
            $table->decimal('valor_cofins', 15, 2)->default(0);
            $table->decimal('valor_pis', 15, 2)->default(0);
            $table->decimal('valor_inss', 15, 2)->default(0);
            $table->text('descricao_servico');
            $table->string('xml_sent_path', 500)->nullable();
            $table->string('xml_response_path', 500)->nullable();
            $table->string('pdf_path', 500)->nullable();
            $table->timestamp('data_emissao');
            $table->timestamp('data_cancelamento')->nullable();
            $table->text('motivo_cancelamento')->nullable();
            $table->foreignId('invoice_replaced_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->timestamps();

            $table->index('company_id', 'idx_invoices_company');
            $table->unique('id_dps', 'idx_invoices_id_dps');
            $table->unique(['company_id', 'dps_serie', 'dps_number'], 'idx_invoices_company_serie_number');
        });

        // Partial unique index para chave_acesso (PostgreSQL)
        if (config('database.default') === 'pgsql') {
            \Illuminate\Support\Facades\DB::statement(
                'CREATE UNIQUE INDEX idx_invoices_chave ON invoices (chave_acesso) WHERE chave_acesso IS NOT NULL'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
