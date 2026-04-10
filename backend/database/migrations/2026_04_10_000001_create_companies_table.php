<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('cnpj', 14)->unique();
            $table->string('razao_social', 255);
            $table->string('nome_fantasia', 255)->nullable();
            $table->string('inscricao_municipal', 20)->nullable();
            $table->string('inscricao_estadual', 20)->nullable();
            $table->string('logradouro', 255);
            $table->string('numero', 20);
            $table->string('complemento', 100)->nullable();
            $table->string('bairro', 100);
            $table->string('codigo_ibge', 7);
            $table->char('uf', 2);
            $table->string('cep', 8);
            $table->string('telefone', 15)->nullable();
            $table->string('email', 255)->nullable();
            $table->smallInteger('regime_tributario')->default(1);
            $table->smallInteger('reg_esp_trib')->default(0);
            $table->string('dps_serie', 5)->default('00001');
            $table->bigInteger('dps_next_number')->default(1);
            $table->smallInteger('ambiente')->default(2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
