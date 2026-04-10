<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->char('tipo_documento', 1);
            $table->string('documento', 14);
            $table->string('razao_social', 255);
            $table->string('nome_fantasia', 255)->nullable();
            $table->string('inscricao_municipal', 20)->nullable();
            $table->string('logradouro', 255);
            $table->string('numero', 20);
            $table->string('complemento', 100)->nullable();
            $table->string('bairro', 100);
            $table->string('codigo_ibge', 7);
            $table->char('uf', 2);
            $table->string('cep', 8);
            $table->string('email', 255)->nullable();
            $table->string('telefone', 15)->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'documento'], 'idx_customers_company_doc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
