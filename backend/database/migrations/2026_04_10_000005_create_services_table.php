<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('codigo_lc116', 10);
            $table->string('codigo_nbs', 10)->nullable();
            $table->string('descricao', 500);
            $table->decimal('aliquota_iss', 5, 4);
            $table->boolean('is_favorite')->default(false);
            $table->timestamps();

            $table->index('company_id', 'idx_services_company');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
